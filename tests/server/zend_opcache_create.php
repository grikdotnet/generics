<?php

use Generics\Internal\StreamWrapper;

$wrapper = new class {
    public $context;
    private string $code =
        '<?php class Virtual{}'
    ;
    private array $stat;

    public function __construct()
    {
        $this->stat = [
            'size' => strlen($this->code),
            'mtime' => time()-360,
            ];
    }

    public function stream_open(string $path, ...$options): bool
    {return true;}
    public function stream_close(): void{}
    public function stream_read(int $count) {
        return $this->code;
    }
    public function stream_eof(): bool
    {
        return true;
    }

    public function url_stat()
    {
        return $this->stat;
    }
    public function stream_stat()
    {
        return $this->stat;
    }
    public function stream_set_option(){return true;}
};

$virtual_path = 'Virtual@generics/cache';

$loader = fn () => include $virtual_path;

stream_wrapper_unregister('file');
stream_wrapper_register('file', $wrapper::class);

opcache_compile_file($virtual_path);

stream_wrapper_unregister('file');
stream_wrapper_restore('file');

var_export(opcache_is_script_cached($virtual_path));
