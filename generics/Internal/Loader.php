<?php declare(strict_types=1);

namespace grikdotnet\generics\Internal;

use Composer\Autoload\ClassLoader;
use grikdotnet\generics\Internal\service\ComposerAdapter;
use grikdotnet\generics\Internal\service\Opcache;
use grikdotnet\generics\Internal\service\PharWrapperAdapter;
use grikdotnet\generics\Internal\tokens\FileAggregate;
use grikdotnet\generics\Internal\view\ConcreteView;
use grikdotnet\generics\Internal\view\Transformer;
use PhpParser\ErrorHandler\Collecting;
use PhpParser\Lexer;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php8;

/**
 * THe root object with high-level logic to parse wildcard and generate concrete classes
 *
 * @internal
 */
class Loader{
    private static self $instance;

    private ComposerAdapter $composer;
    private Opcache $opcache;

    public function __construct(private readonly Container $container, ?ClassLoader $composer = null)
    {
        if (isset(self::$instance)) {
            return;
        }
        $loaders = $composer ? [$composer] : ClassLoader::getRegisteredLoaders();
        if (!$loaders) {
            throw new \RuntimeException('Could not obtain a Composer loader');
        }
        $this->composer = new ComposerAdapter(...$loaders);
        self::$instance = $this;
        $this->opcache = new Opcache;
        spl_autoload_register($this->autoloader(...), true,true);
        if ($this->opcache->is_available) {
            $this->opcache->linkCachedTokens($this->container);
            register_shutdown_function(fn()=>$this->opcache->store($container));
        }
    }

    /**
     * Called from the PHP core.
     * Reads the source file, parses, transforms, and stores to Zend OpCache
     *
     * @param class-string $class
     * @return bool
     */
    public function autoloader(string $class): bool
    {
        // if the class is a known concrete type, create it
        if (null !== $iToken = $this->container->getInstantiationTokens($class)) {
            return $this->createConcreteClass($iToken->type,$iToken->concrete_types);
        }
        //find class location via Composer
        if (!($path = $this->composer->findClassFile($class))) {
            return false;
        }
        $tokens = $content = false;

        if ($this->opcache->is_available) {
            if ($tokens = $this->container->getFileTokens($path)) {
                if ($this->opcache->includeAugmentedFile($path)) {
                    //the augmented content and tokens are found in the cache
                    return class_exists($class,false);
                }
            } elseif (in_array($path,$this->container->skip_files)) {
                return false;
            }
        }

        if ( !($content = $this->getFileContents($path)) ) {
            $this->container->addToSkipFiles($path);
            return false;
        }

        if (!$tokens) {
            $tokens = $this->parse($path, $content);
            if ($tokens->isEmpty()) {
                $this->container->addToSkipFiles($path);
                // nothing to do with this file
                return false;
            }
            $this->container->addFileTokens($tokens);
        }
        $augmented = Transformer::augment($content,$tokens);
        PharWrapperAdapter::include($path,$augmented);

        return class_exists($class,false);
    }

    /**
     * Creates concrete classes based on the wildcard templates
     *
     * @param class-string $wildcard_class
     * @param string[] $types
     * @return bool
     */
    public function createConcreteClass(string $wildcard_class, array $types): bool
    {
        $wildcard_class = ltrim($wildcard_class,'\\');
        $concrete_class_name = ConcreteView::makeConcreteName($wildcard_class, $types);
        if ($this->opcache->is_available) {
            if ($this->opcache->loadVirtualClass($concrete_class_name)) {
                return true;
            }
        }
        $classAggregate = $this->container->getClassTokens($wildcard_class);
        if ($classAggregate  === null) {
            //read and parse the wildcard source file
            if (!($path = $this->composer->findClassFile($wildcard_class))) {
                return false;
            }
            if (in_array($path,$this->container->skip_files)) {
                return false;
            }
            if (null === $tokens = $this->container->getFileTokens($path)) {
                if ( !($content = $this->getFileContents($path)) ) {
                    $this->container->addToSkipFiles($path);
                    return false;
                }
                $tokens = $this->parse($path, $content);
                if ($tokens->isEmpty()) {
                    $this->container->addToSkipFiles($path);
                    // nothing to do with this file
                    return false;
                }
                $this->container->addFileTokens($tokens);
            }
            if (!isset($tokens->classAggregates[$wildcard_class])) {
                return false;
            }
            $classAggregate = $tokens->classAggregates[$wildcard_class];
        }

        $View = new ConcreteView($classAggregate);
        $class_declaration = $View->generateConcreteDeclaration($types);
        if ($this->opcache->is_available) {
            $this->opcache->includeVirtualClass($concrete_class_name,$class_declaration);
        } else {
            eval($class_declaration);
        }

        return class_exists($concrete_class_name,false);
    }

    /**
     * An adapter for the parser
     *
     * @param string $path
     * @param string $content
     * @return FileAggregate
     */
    private function parse(string $path, string $content): FileAggregate
    {
        $errorHandler = new Collecting;
        $parser = new Php8(new Lexer);
        $ast = $parser->parse($content, $errorHandler);
        //the Visitor class is actually a model with logic
        $nameResolver = new \PhpParser\NodeVisitor\NameResolver;
        $visitor = new GenericsVisitor($path, $content);
        $traverser = new NodeTraverser();
        $traverser->addVisitor($nameResolver);
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getFileTokens();
    }


    /**
     * @param string $path
     * @return string|false
     */
    private function getFileContents(string $path): string|false
    {
        if (file_exists($path) && is_readable($path)) {
            $content = file_get_contents($path);
            if ( !$content || !str_contains($content,'Generics\\') ) {
                return false;
            }
            return $content;
        }
        return false;
    }

}
