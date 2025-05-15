<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

class TypeErrorTest extends TestCase{

    public function testGenericsTypeError(){
        $object = new B;
        $this->expectException(\grikdotnet\generics\TypeError::class);
        $expected_message = 'B::foo: Argument #1 ($y) must be of type ACME\Foo, int given';
        $this->expectExceptionMessage($expected_message);
        $object->foo(1,2);
    }

    #[WithoutErrorHandler]
    public function testGenericsTypeErrorTrace(){
        $expected_message = 'B::foo: Argument #1 ($y) must be of type ACME\Foo, int given';
        $expected_trace = [
            'file' => __FILE__,
            'line' => __LINE__+7,
            'function' => 'foo',
            'class' => 'B',
            'type' => '->',
        ];
        $object = new B;
        try {
            $object->foo(1,2);
        }catch (\TypeError $e){}
        $this->assertEquals($expected_message, $e->getMessage());
        $this->assertEquals($expected_trace, $e->getTrace()[0]);
    }
}


class A{function foo($x,$y){}}
class B extends A{
    function foo($x,$y){
        try{return (fn(\ACME\Foo $y)=>
            parent::foo(...func_get_args()))($y);
        }catch(\TypeError $e){throw \grikdotnet\generics\TypeError::fromTypeError($e);}
    }
}