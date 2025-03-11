<?php declare(strict_types=1);

namespace Generics\Internal;

/**
 * @internal
 */
class Opcache {
    /**
     * The key to save tokens in opcache
     */
    const KEY = 'Generics/cache@'.__DIR__;
    /**
     * A shared variable to transfer data to a wrapper
     * @var string
     */
    static protected string $data_to_write = '';
    static public function isAvailable(): bool
    {
        return extension_loaded('Zend OPcache') && ini_get('opcache.enable');
    }

    static public function read(): array
    {
        if (opcache_is_script_cached(self::KEY)) {
            if (is_array($data = include self::KEY)) {
                return $data;
            }
        }
        return [];
    }

    static public function write(array $data): void
    {
        if (!self::isAvailable()) {
            return;
        }
        self::$data_to_write = '<?php return '. var_export($data,true).';';

        $wrapper = new class extends Opcache {
            public $context;
            private int $position = 0;
            public function stream_read(int $count) {
                if (strlen(Opcache::$data_to_write) < $count) {
                    $this->position = $count;
                    return Opcache::$data_to_write;
                }
                $this->position += $count;
                return substr(Opcache::$data_to_write,$this->position,$count);
            }
            public function stream_eof() {
                return $this->position >= strlen(Opcache::$data_to_write);
            }
            public function url_stat() {
                return [
                    'size' => strlen(Opcache::$data_to_write),
                    'mtime' => time()-360,
                ];
            }
            public function stream_stat() {
                return $this->url_stat();
            }
            public function stream_set_option() {
                return true;
            }
            public function stream_open() {
                return true;
            }
            public function stream_close() {}
        };
        stream_wrapper_unregister('file');
        stream_wrapper_register('file', $wrapper::class);
        opcache_compile_file(self::KEY);
        stream_wrapper_unregister('file');
        stream_wrapper_restore('file');
    }
}