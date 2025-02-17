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
class Autoloader{
    /**
     * @var array<ClassLoader>
     */
    private array $loaders;
    private Php8 $parser;

    private static self $instance;

    public function __construct(private readonly Container $container, private readonly ?ClassLoader $composer = null)
    {
        if (isset(self::$instance)) {
            return;
        }
        $this->parser = new Php8(new Lexer);
        spl_autoload_register($this, true,true);
        self::$instance = $this;
    }

    public function __invoke(string $class): bool
    {
        if (!($path = $this->findClassFile($class))) {
            return false;
        }
        if ( !($content = $this->getFileContent($path)) ){
            return false;
        }
        $visitor = $this->parse($path, $content);
        foreach ($visitor->classes as $classAggregate){
            $this->container->addClassTokens($classAggregate);
        }
        if ($visitor->instantiations->hasTokens()) {
            foreach ($visitor->instantiations as $i) {
                if (!$this->container->getClassTokens($i->class_name)) {
                    $this($i->class_name);
                }
            }
            $view = new ConcreteInstantiationSubstitutionView($visitor->instantiations,$content);
            $new_code = $view->substituteInstantiations();
            $virtual_file = 'generic://'.$path;
            $this->container->addVirtualFile($virtual_file,$new_code,$path);
            $this->loadVirtual($virtual_file);
            return true;
        }
        return false;
    }

    private function findClassFile(string $class): string | false
    {
        if (str_starts_with($class, 'Generics\\')) {
            return false;
        }
        $path = false;
        if ($this->composer) {
            $path = $this->composer->findFile($class);
        } else {
            foreach (ClassLoader::getRegisteredLoaders() as $l) {
                if ($path = $l->findFile($class)) {
                    break;
                }
            }
        }
        if (!$path) {
            return false;
        }
        if (str_starts_with($path, 'generic://') || preg_match('~^\w+://~',$path)) {
            return false;
        }
        if (str_starts_with($path, 'file://')) {
            $path = substr($path,7);
        }
        return $path;
    }

    private function getFileContent(string $path): string|false
    {
        if (isset($this->container->files[$path])) {
            return $this->container->files[$path];
        }
        if (file_exists($path) && is_readable($path)) {
            $content = file_get_contents($path);
            if ( !$content || !str_contains($content,'Generics\\') ) {
                return false;
            }
            return $this->container->files[$path] = $content;
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

    private function loadVirtual(string $virtual_file): void
    {
        static $includer;
        isset($includer) || $includer = \Closure::bind(static function($file) {
            include $file;
        }, null, null);
        $includer($virtual_file);
    }

    public function loadTemplateClass(string $class): void
    {
        if ($this->container->isClassTemplate($class)){
            return;
        }
        $path = (new \ReflectionClass($class))->getFileName();
        if (!is_readable($path)) {
            throw new \InvalidArgumentException("Invalid file for class $class: ".$path);
        }
        if ( !($content = $this->getFileContent($path)) ) {
            throw new \RuntimeException('The generic class '.$class.' could not be read from '.$path);
        }
        $visitor = $this->parse($path,$content);
        foreach ($visitor->classes as $classAggregate){
            $this->container->addClassTokens($classAggregate);
        }
        if (!$this->container->isClassTemplate($class)) {
            throw new \RuntimeException('Could not parse '.$class.' as a generic template from '.$path);
        }
    }

    public function createConcreteClassForTrait($class, $type): string
    {
        if (($classAggregate = $this->container->getClassTokens($class)) === null
            || !isset($this->container->files[$classAggregate->filename])
        ) {
            throw new \RuntimeException('The generic class '.$class.' should be loaded');
        }
        $source = $this->container->files[$classAggregate->filename];
        $token = new ConcreteInstantiationToken(0,0,$class,$type);
        $View = new ConcreteClassDeclarationView($classAggregate, $source);
        $class_declaration = $View->generateConcreteDeclaration($token);
        $concrete_class = $View->generateConcreteClassName($token);
        $this->container->addVirtualClassCode(
            $concrete_class,
            new VirtualFile($concrete_class,$class_declaration,$classAggregate->filename)
        );
        $this->loadVirtual('generic://'.$concrete_class);
        return $concrete_class;
    }

}