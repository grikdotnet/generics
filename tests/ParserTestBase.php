<?php declare(strict_types=1);

use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php8;
use PHPUnit\Framework\TestCase;
use Generics\Internal\tokens\FileAggregate;

abstract class ParserTestBase extends TestCase
{
    protected Php8 $parser;
    protected NodeTraverser $traverser;

    public function setUp(): void
    {
        $this->parser = new Php8(new Lexer);
        $this->traverser = new NodeTraverser;
        $this->traverser->addVisitor(new PhpParser\NodeVisitor\NameResolver);
    }

    public function tearDown(): void
    {
        unset($this->traverser);
    }

    protected function traverse(string $code): FileAggregate
    {
        $ast = $this->parser->parse($code, new Collecting);
        $visitor = new \Generics\Internal\GenericsVisitor('',$code);
        $this->traverser->addVisitor($visitor);
        $this->traverser->traverse($ast);
        return $visitor->getFileTokens();
    }
}
