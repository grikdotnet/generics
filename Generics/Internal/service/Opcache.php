<?php declare(strict_types=1);

namespace Generics\Internal\service;

use Generics\Internal\Container;

/**
 * @internal
 */
class Opcache {
    public readonly bool $is_available;

    /**
     * The key to save tokens in opcache
     */
    private const GENERIC_TOKENS_KEY = 'phar:///generics/cache@'.__DIR__;
    private const PREFIX = 'phar:///generics@';

    private static ?\Closure $includer;

    public function __construct()
    {
        $this->is_available = php_sapi_name() !== 'cli'
                && ini_get('opcache.enable')
                && ini_get('opcache.revalidate_freq') > 0;
        if (!$this->is_available) {
            return;
        }
        if (extension_loaded('Phar')) {
            PharWrapperAdapter::$dance_with_wrapper = true;
        } else {
            PharWrapperAdapter::registerPermanent();
        }
    }

    /**
     * Link tokens from opcache to the container
     *
     * @param Container $container
     * @return bool
     */
    public function linkCachedTokens(Container $container): bool
    {
        if ($this->is_available && opcache_is_script_cached(self::GENERIC_TOKENS_KEY)) {
            if (is_array($data = @include self::GENERIC_TOKENS_KEY)) {
                $container->setCache($data[0],$data[1],$data[2]);
                return true;
            }
        }
        return false;
    }

    /**
     * @param Container $container
     * @return void
     */
    public function store(Container $container): void
    {
        if (!$this->is_available || !$container->isModified()) {
            return;
        }

        if ($container->areNewTokens()) {
            $data[0] = array_map(fn ($c) => $c->toArray(), $container->fileAggregates);
            $data[1] = array_map(fn ($c) => $c->toArray(), $container->classAggregates);
            $data[2] = $container->skip_files;
            $data_to_write = '<?php return '. var_export($data,true).';';
            PharWrapperAdapter::write(self::GENERIC_TOKENS_KEY, $data_to_write);
        }

        foreach ($container->vfiles as $vfile) {
            $filename = self::PREFIX.$vfile->reference_path;
            if (!opcache_is_script_cached($filename)){
                $data = '<?php return '.var_export($vfile->content,true).';';
                PharWrapperAdapter::write($filename, $data);
            }
        }
    }

    /**
     * Called when a class with generic is loaded
     *
     * @param string $path
     * @return bool
     * @noinspection PhpInconsistentReturnPointsInspection
     */
    public function includeAugmentedFile(string $path): bool
    {
        if (!$this->is_available) {
            return false;
        }
        $path = self::PREFIX.$path;
        if (opcache_is_script_cached($path)) {
            $previous = set_error_handler(
                function(...$err) use (&$previous,&$cache_miss,$path) {
                    if ($err[0] === E_WARNING && (
                        str_starts_with($err[1],'include('.$path) ||
                        str_starts_with($err[1],'include(): Failed opening \''.$path)
                    )) {
                        $cache_miss = true;
                    } elseif(is_callable($previous)) {
                        call_user_func($previous,...$err);
                    } else {
                        return false;
                    }
                }
            );
            (self::$includer ?? self::$includer =\Closure::bind(static function($file) {
                include $file;
            },null,null))($path);
            restore_error_handler();
        } else {
            return false;
        }
        if ($cache_miss) {
            return false;
        }
        return true;
    }

    /**
     * @param string $virtual_class_name
     * @return bool
     */
    public function loadVirtualClass(string $virtual_class_name): bool
    {
        $key = self::PREFIX.__DIR__.'/'. $virtual_class_name;
        if (opcache_is_script_cached($key)) {
            @include $key;
        }
        return class_exists($virtual_class_name,false);
    }

    public function includeVirtualClass(string $virtual_class_name, string $declaration): void
    {
        $key = self::PREFIX.__DIR__.'/'. $virtual_class_name;
        PharWrapperAdapter::include($key,'<?php '.$declaration);
    }

}
