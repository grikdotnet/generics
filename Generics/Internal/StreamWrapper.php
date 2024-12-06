<?php declare(strict_types=1);

namespace Generics\Internal;

use Error;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php8;
use UnexpectedValueException;

final class StreamWrapper
{
    private static Container $container;
    private static Php8 $parser;
    private ?string $file_content;
    private int $position = 0;
    private string $path = '';

    /**
    * @return void
     */
    public static function register(
        Container $container,
    ): void
    {
        self::$container = $container;
        self::$parser = new Php8(new Lexer);
        stream_wrapper_register('generic', __CLASS__);
    }

    public function stream_open($path, ...$options): bool
    {
        if (!str_starts_with($path, 'generic://')) {
            throw new UnexpectedValueException("Wrong usage for the Generics Stream Wrapper");
        }
        $path = substr($path, 10);

        $this->file_content = self::$container->cache->getFileCache($path) ?: file_get_contents($path);

        if ($this->file_content === false) {
            return false;
        }
        $this->path = $path;
        $this->position = 0;

        $errorHandler = new Collecting;
        try {
            $ast = self::$parser->parse($this->file_content, $errorHandler);
            $traverser = new NodeTraverser(new GenericsVisitor($this->file_content, self::$container));
            $traverser->traverse($ast);
        }catch (Error $e) {
            print_r($e);
        }

        return true;
    }

    /**
     * Register the wrapper again when finished reading the file
     */
    public function stream_close(): void
    {
        self::$container->cache->dropFileCache($this->path);
        unset($this->file_content);
    }

    /**
     * include() reads files by 8192 bytes
     *
     * @param int $count
     * @return string
     */
    public function stream_read(int $count): string
    {
        $ret = substr($this->file_content, $this->position, $count);
        $this->position += strlen($ret);
        return $ret;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen($this->file_content);
    }

    public function stream_stat(): array| false
    {
        $stat = stat($this->path);
        if (isset($stat['size'])) {
            $stat['size'] = strlen($this->file_content);
        }
        return $stat;
    }

    public function url_stat()
    {
        /** @todo Implement the method */
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
