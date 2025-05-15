<?php declare(strict_types=1);

namespace grikdotnet\generics\Internal\service;

/**
 * Provides the stream wrapper compatible with Opcache
 * @internal
 */
class PharWrapperAdapter {
    private const STREAM = 'phar';
    /**
     * @var true
     */
    public static bool $dance_with_wrapper;
    private static Object $wrapper;
    private static bool $is_registered = false;

    public static function registerPermanent(): void
    {
        if (self::$is_registered || isset(self::$dance_with_wrapper)) {
            return;
        }
        self::$dance_with_wrapper = false;
        stream_wrapper_register(self::STREAM, self::getWrapper()::class);
        self::$is_registered = true;
    }

    public static function write(string $path, string $data): void
    {
        if (!self::$is_registered) {
            self::register();
        }
        if (!str_starts_with($path, self::STREAM.'://')) {
            $path = self::STREAM.'://'.$path;
        }
        $w = self::getWrapper();
        $w::$data = $data;
        opcache_compile_file($path);
        $w::$data = '';
        if (self::$dance_with_wrapper) {
            self::unregister();
        }
    }

    public static function include(string $path, string $data): void
    {
        if (!self::$is_registered) {
            self::register();
        }
        if (!str_starts_with($path, self::STREAM.'://')) {
            $path = self::STREAM.'://'.$path;
        }
        $w = self::getWrapper();
        $w::$data = $data;
        (\Closure::bind(static function($file) {
            include $file;
        },null,null))($path);
        $w::$data = '';
        if (self::$dance_with_wrapper) {
            self::unregister();
        }
    }

    private static function register(): void
    {
        if (self::$is_registered) {
            return;
        }
        if (!isset(self::$dance_with_wrapper)) {
            self::$dance_with_wrapper = in_array(self::STREAM,stream_get_wrappers(),true);
        }
        if (self::$dance_with_wrapper) {
            stream_wrapper_unregister(self::STREAM);
        }
        stream_wrapper_register(self::STREAM, self::getWrapper()::class);
        self::$is_registered = true;
    }

    private static function unregister(): void
    {
        stream_wrapper_unregister(self::STREAM);
        stream_wrapper_restore(self::STREAM);
        self::$is_registered = false;
    }

    private static function getWrapper(): Object
    {
        return self::$wrapper ?? self::$wrapper = new class() {
            /**
             * A property used by the PHP engine
             */
            public $context;
            /**
             * Storage for the data to serve
             * @var string
             */
            public static string $data = '';
            /**
             * Position to serve data in chunks when data is bigger than 8kb
             * @var int
             */
            private int $position = 0;

            public function __construct()
            {
            }

            public function stream_open()
            {
                return !empty(self::$data);
            }

            public function stream_close()
            {
                self::$data = '';
                $this->position = 0;
            }
            public function stream_read(int $count)
            {
                if (strlen(self::$data) < $count) {
                    $this->position = $count;
                    return self::$data;
                }
                $this->position += $count;
                return substr(self::$data,$this->position,$count);
            }

            public function stream_eof()
            {
                return $this->position >= strlen(self::$data);
            }
            public function url_stat()
            {
                return self::$data === '' ? false :[
                    'size' => strlen(self::$data),
                    'mtime' => time()-360,
                ];
            }
            public function stream_stat()
            {
                return $this->url_stat();
            }

            public function stream_set_option()
            {
                return true;
            }
        };
    }
}
