<?php declare(strict_types=1);

use grikdotnet\generics\Internal\tokens\ClassAggregate;
use grikdotnet\generics\Internal\tokens\MethodHeaderAggregate;
use grikdotnet\generics\Internal\tokens\Parameter;
use PHPUnit\Framework\TestCase;

final class ConcreteInstanceGenerationTest extends TestCase
{

    public function testConcreteClassGeneration()
    {
        $template = '<?php
        #[\Generics\T]
        class Foo{
            public function __construct(
                int $x, #[\Generics\T] $param, string $y, $z
            ){}
        }';
        $classTokens = new ClassAggregate('Foo');
        $methodAggregate = new MethodHeaderAggregate(
            offset: 60,
            length: 103,
            name: '__construct',
            headline: 'public function __construct(int $x, #[\Generics\T] $param, string $y, $z)'
        );
        $methodAggregate->addParameter(new Parameter
            (offset: 105,length: 6,name: 'x',type: 'int'));
        $methodAggregate->addParameter(new Parameter
            (offset: 113,length: 22,name: 'param',is_wildcard: true));
        $methodAggregate->addParameter(new Parameter
            (offset: 136,length: 9,name: 'y', type: 'string'));
        $methodAggregate->addParameter(new Parameter
            (offset: 147,length: 2,name: 'z'));
        $classTokens->addMethodAggregate($methodAggregate);
        $classTokens->setIsTemplate();

        //$instantiation_code
        //    $c = (#[Generics\T(\ACME\Bar)] fn() => new Foo($x))(); ';
        $concrete_type = ["\ACME\Bar"];

        $expected_declaration = 'class Foo‹⧵ACME⧵Bar› extends Foo{'.
            'public function __construct(int $x, #[\Generics\T] $param, string $y, $z){'.
                'try{'.
                    'return (fn(int $x,\ACME\Bar $param,string $y,$z)=>parent::__construct(...func_get_args()))'.
                        '($x,$param,$y,$z);'.
                '}catch(\TypeError $e){throw \grikdotnet\generics\TypeError::fromTypeError($e);}'.
            '}}';

        $View = new \grikdotnet\generics\Internal\view\ConcreteView($classTokens);
        $class_declaration = $View->generateConcreteDeclaration($concrete_type);
        self::assertEquals($expected_declaration, $class_declaration);
    }

    /**
     * @TODO Implement Wildcard Return Types
    public function testConcreteClassGenerationWithReturnType()
    {
        $template = '<?php
        #[\Generics\T]
        class Foo{
            #[\Generics\ReturnT]
            public function __construct(int $x, #[\Generics\T] $param, string $y, $z)
            {}
        }';
        $classTokens = new ClassAggregate('template','Foo');
        $methodAggregate = new MethodAggregate(
            offset: 93,
            length: 73,
            name: '__construct',
        );
        $methodAggregate->addParameter(new \grikdotnet\generics\Internal\Parameter(
        ));
        $methodAggregate->setWildcardReturn();

        $classTokens->addMethodAggregate($methodAggregate);
        $classTokens->setIsTemplate();
        $classTokens->current();

        $instantiation_code = '<?php namespace ACME;
            $c = (#[Generics\T(\ACME\Bar)] fn() => new Foo($x))(); ';
        $concreteType = '\Acme\Bar';
        $instantiationToken = new ConcreteInstantiationToken(
            class_name: "Foo",
            offset: 67,
            parameter_type: "\ACME\Bar"
        );

        $expected_declaration = 'class Foo‹⧵ACME⧵Bar› extends Foo{'.
            'public function __construct( int $x, #[\Generics\T] $param, string $y, $z ){'.
            'try{'.
            'return (fn(\ACME\Bar $param)=>parent::__construct(...func_get_args()))($param);}'.
            '}catch(\TypeError $e){throw new \grikdotnet\generics\TypeError($e);}'.
        '}';

        $View = new \grikdotnet\generics\Internal\ConcreteClassDeclarationView($classTokens, $template);
        $class_declaration = $View->generateConcreteDeclaration($instantiationToken);
        self::assertEquals($expected_declaration, $class_declaration);
    }
*/

}
