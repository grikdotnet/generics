<?php

use Generics\Internal\ClassAggregate;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Client;
use Generics\Internal\Parameter;

class OpcacheTest extends TestCase {
    protected Client $client;

    private string $base_url = '/tests/server/';

    protected function setUp(): void
    {
        $this->client = new Client([
            'base_uri' => 'http://localhost:8080',
            'timeout'  => 0.2,
        ]);
        try{
            $response = $this->client->get($this->base_url.'reset.php');
        }catch (\GuzzleHttp\Exception\GuzzleException $e){
            $response = false;
        }
        if (!$response || $response->getStatusCode() != 200 || $response->getBody() != 'ok') {
            $this->markTestSkipped('Server is not available. Run the `docker-compose up` in the docker/ folder');
        }
    }

    protected function request(string $url): string
    {
        $response = $this->client->get($this->base_url.$url);
        if ($response->getStatusCode() !== 200) {
            return 'Response error';
        }
        return $response->getBody()->getContents();
    }

    public function testZendOpcache()
    {
        $response = $this->request('zend_opcache_create.php');
        $this->assertEquals('true',$response);
        $response = $this->request('zend_opcache_check.php');
        $this->assertEquals('true', $response);
    }

    public function testOpcacheClass()
    {
        $this->request('opcache_write.php');
        $response = $this->request('opcache_check.php');
        $this->assertEquals('true', $response);
    }

    public function testArrayConversion()
    {
        $classAggregate = new ClassAggregate('test');
        $classAggregate->setClassname('Foo');
        $classAggregate->setIsTemplate();
        $classAggregate->addMethodAggregate(
            $methodAggregate = new \Generics\Internal\MethodAggregate(
                offset: 60,
                length: 156,
                name: '__construct',
            )
        );
        $methodAggregate->setWildcardReturn();
        $methodAggregate->addParameter(new Parameter(offset: 138, length:7,  name: 'x', type:'int &'));
        $methodAggregate->addParameter(new Parameter(offset: 162, length:6, name: 'param', is_wildcard: true));
        $methodAggregate->addParameter(new Parameter(offset: 170, length:13, name: 'y', type:'?\ACME\Bar'));

        $as_array = $classAggregate->toArray();
        $unserialized = ClassAggregate::fromArray($as_array);

        self::assertEquals($classAggregate,$unserialized);
    }


}
