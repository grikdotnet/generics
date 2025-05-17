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
        $rate = new \samples\Rate(1,840);
        $collection[] = $rate;
        $this->assertContainsOnlyInstancesOf('\samples\Collection‹samples⧵Rate›',$collection);

        $this->expectException(\grikdotnet\generics\TypeError::class);
        $collection[] = 'abc';
    }

    public function testScalarType()
    {
        $collection = new (\samples\Collection::T('int'));
        $this->assertContainsOnlyInstancesOf('\samples\Collection‹int›',$collection);
        $collection[] = 42;
        $this->expectException(\grikdotnet\generics\TypeError::class);
        $collection[] = "abc";
    }
}
