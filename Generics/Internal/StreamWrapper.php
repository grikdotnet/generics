<?php declare(strict_types=1);

namespace Generics\Internal;

use UnexpectedValueException;

/**
 * @internal
 */
final class StreamWrapper
{
    private static Container $container;
    private VirtualFile $file;
    private int $position = 0;
    private string $path = '';

    /**
     * @param Container $container
     * @return void
     */
    public static function register(
        Container $container,
    ): void
    {
        self::$container = $container;
        stream_wrapper_register('generic', __CLASS__);
    }

    public function stream_open($path, ...$options): bool
    {
        if (!str_starts_with($path, 'generic://')) {
            throw new UnexpectedValueException("Wrong usage for the Generics Stream Wrapper");
        }
        if (!isset(self::$container->vfiles[$path])){
            return false;
        }
        $this->file = self::$container->vfiles[$path];
        return true;
    }

    /**
     * Register the wrapper again when finished reading the file
     */
    public function stream_close(): void
    {
        unset($this->file);
    }

    /**
     * include() reads files by 8192 bytes
     *
     * @param int $count
     * @return string
     */
    public function stream_read(int $count): string
    {
        $ret = substr($this->file->content, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen($this->file->content);
    }

    public function stream_stat(): array| false
    {
        $stat = stat($this->file->reference_path);
        if (isset($stat['size'])) {
            $stat['size'] = strlen($this->file->content);
        }
        return $stat;
    }

    public function url_stat()
    {
        throw new \RuntimeException("Operation is not supported by Generics stream wrapper");
    }

    /**
     * Include() calls it to set read buffer to 8192, ignoring
     * @return bool
     */
    public function stream_set_option(): bool
    {
        return true;
    }

    public function stream_flush()
    {
        return true;
    }

    public function __call(string $name, array $args): void
    {
        throw new \RuntimeException($name."() operation is not supported by Generics stream wrapper");
    }
}
