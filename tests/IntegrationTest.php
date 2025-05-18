<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

$composer = current(\Composer\Autoload\ClassLoader::getRegisteredLoaders());
$composer->addPsr4('samples\\',__DIR__.'/samples');
new grikdotnet\generics\Enable;

class IntegrationTest extends TestCase
{
    public function testCollection()
    {
        $collection = new (\samples\Collection::T(\samples\Rate::class));
        $collection[] = new \samples\Rate(1,840);
        $this->isInstanceOf('\samples\Collection‹⧵samples⧵Rate›',$collection);

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

    public function testConcreteTypeParameter()
    {
        $concreteCollection = new (\samples\Collection::T(\samples\Rate::class));
        $concreteCollection[] = new \samples\Rate(1,840);
        $model = new \samples\Model;
        $model->process($concreteCollection);

        $collection = new \samples\Collection();
        $collection[] = new \samples\Rate(1,840);
        $this->expectException(\TypeError::class);
        $model->process($collection);
    }

}
