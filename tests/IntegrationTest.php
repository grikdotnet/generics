<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use function PHPUnit\Framework\assertFalse;

class IntegrationTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $composer = current(\Composer\Autoload\ClassLoader::getRegisteredLoaders());
        $composer->addPsr4('samples\\',__DIR__.'/samples');
    }

    protected function setUp(): void
    {
        new grikdotnet\generics\Enable;
    }

    public function testCollection()
    {
        $collection = new (\samples\Collection::T(\samples\Rate::class));
        $collection[] = new \samples\Rate(1,840);
        $this->isInstanceOf('\samples\Collection‹⧵samples⧵Rate›',$collection);
        $collection = new (\samples\Collection::T("\samples\Rate"));
        $this->isInstanceOf(\samples\Collection‹⧵samples⧵Rate›::class,$collection);

        $this->expectException(\TypeError::class);
        $collection[] = '42';
    }

    public function testScalarType()
    {
        $collection = new (\samples\Collection::T('int'));
        $this->isInstanceOf(\samples\Collection‹int›::class,$collection);
        $collection[] = 42;
        $this->expectException(\grikdotnet\generics\TypeError::class);
        $collection[] = "abc";
    }

    public function testMultipleGenericArguments()
    {
        $collection = new (\samples\Collection2::T('int',\samples\Rate::class));
        $this->isInstanceOf(\samples\Collection2‹int›‹⧵samples⧵Rate›::class,$collection);
        $collection[42] = new \samples\Rate(1,840);
        $this->expectException(\TypeError::class);
        $collection[1] = null;
    }

    public function testConcreteTypeParam()
    {
        $concreteCollection = new (\samples\Collection::T(\samples\Rate::class));
        $concreteCollection[] = new \samples\Rate(1,840);
        $model = new \samples\Model;
        $model->syntax1($concreteCollection);
        $model->syntax2($concreteCollection);

        $r = new ReflectionClass($model);
        $t = $r->getMethod('syntax1')->getParameters()[0]->getType()->getName();
        $this->assertEquals('samples\Collection‹⧵samples⧵Rate›',$t);
        $t = $r->getMethod('syntax2')->getParameters()[0]->getType()->getName();
        $this->assertEquals('samples\Collection‹⧵samples⧵Rate›',$t);

        $unTypesCollection = new \samples\Collection();
        try{
            $model->syntax1($unTypesCollection);
            assertFalse(true,"The TypeError was not thrown for an invalid generic type");
        } catch (\TypeError $e) {
            $this->assertInstanceOf(\TypeError::class,$e);
        }
        $this->expectException(\TypeError::class);
        $model->syntax2($unTypesCollection);

    }

    public function testMultipleConcreteParametersSyntax1()
    {
        $concreteCollection = new (\samples\Collection2::T('int','float'));
        $model = new \samples\Model;
        $model->multiparam1($concreteCollection);
        $model->multiparam2($concreteCollection);

        $r = new ReflectionClass($model);
        $t = $r->getMethod('multiparam1')->getParameters()[0]->getType()->getName();
        $this->assertEquals(\samples\Collection2‹int›‹float›::class,$t);

        $t = $r->getMethod('multiparam2')->getParameters()[0]->getType()->getName();
        $this->assertEquals(\samples\Collection2‹int›‹float›::class,$t);

        $unTypesCollection = new \samples\Collection();
        $this->expectException(\TypeError::class);
        $model->multiparam1($unTypesCollection);
    }

    public function testArrowSyntax()
    {
        $factory = new \samples\ArrowFactory;
        $collection = $factory->createCollection(42);
        $this->isInstanceOf(\samples\Collection‹int›::class,$collection);

        $nnc = $factory->createNoNamespace();
        $this->isInstanceOf(\samples\Collection‹⧵NoNamespace›::class,$nnc);

        $this->expectException(\grikdotnet\generics\TypeError::class);
        $collection[] = "abc";
    }
}

class NoNamespace
{
}
