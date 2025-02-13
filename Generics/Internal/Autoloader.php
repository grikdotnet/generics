<?php declare(strict_types=1);

namespace Generics\Internal;

use Composer\Autoload\ClassLoader;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php8;

/**
 * @internal
 */
readonly class Autoloader{
    /**
     * @var array<ClassLoader>
     */
    private array $loaders;
    private Php8 $parser;

    public function __construct(private Container $container, ?ClassLoader $composer = null)
    {
        if ($composer) {
            $this->loaders = [$composer];
        } else {
            $this->loaders = ClassLoader::getRegisteredLoaders();
        }
        if (!$this->loaders) {
            throw new \RuntimeException('Could not obtain a Composer loader');
        }
        $this->parser = new Php8(new Lexer);
        spl_autoload_register($this, true,true);
    }

    public function __invoke(string $class): false
    {
        foreach ($this->loaders as $l) {
            if ($path = $l->findFile($class)) {
                break;
            }
        }
        if (!($path ?? false)) {
            return false;
        }
        if (str_starts_with($path, 'generic://') || str_starts_with($class, 'Generics\\') || preg_match('~^\w+://~',$path)) {
            return false;
        }
        if (str_starts_with($path, 'file://')) {
            $path = substr($path,7);
        }
        if ( !($content = $this->getFileContents($path)) || !str_contains($content,'Generics\\') ){
            return false;
        }
        $visitor = $this->parse($path, $content);
        foreach ($visitor->classes as $classAggregate){
            $this->container->addClassTokens($classAggregate);
        }
        if ($visitor->instantiations->hasTokens()) {
            $this->createConcreteClasses($visitor->instantiations);

            $view = new ConcreteInstantiationSubstitutionView($visitor->instantiations,$content);
            $new_code = $view->substituteInstantiations();
            $virtual_file = 'generic://'.$path;
            $this->container->addVirtualSourceCode($virtual_file,$new_code,$path);
            $this->loadVirtual($virtual_file);
        }

        return false;
    }

    public function getFileContents(string $path): string|false
    {
        if (isset($this->container->files[$path])) {
            return $this->container->files[$path];
        }
        if (file_exists($path) && is_readable($path)) {
            return $this->container->files[$path] = file_get_contents($path);
        }
        return false;
    }


    private function parse(string $path, string $content): GenericsVisitor
    {
        $errorHandler = new Collecting;
        $ast = $this->parser->parse($content, $errorHandler);
        $visitor = new GenericsVisitor($path, $content);
        $traverser = new NodeTraverser($visitor);
        $traverser->traverse($ast);
        return $visitor;
    }

    private function loadVirtual(string $vortual_file): void
    {
        static $includer;
        isset($includer) || $includer = \Closure::bind(static function($file) {
            include $file;
        }, null, null);
        $includer($vortual_file);
    }

    private function createConcreteClasses(ConcreteInstantiationAggregate $instantiations)
    {
        foreach ($instantiations as $i) {
            if (!$this->container->getClassTokens($i->class_name)) {
                $this($i->class_name);
            }
        }
    }

}