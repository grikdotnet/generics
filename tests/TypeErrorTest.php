<?php declare(strict_types=1);

use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\WithoutErrorHandler;
use PHPUnit\Framework\TestCase;

class TypeErrorTest extends TestCase{

    public function testGenericsTypeError(){
        $object = new B;
        $this->expectException(\Generics\TypeError::class);
        $expected_message = 'B::foo: Argument #1 ($y) must be of type ACME\Foo, int given';
        $this->expectExceptionMessage($expected_message);
        $object->foo(1,2);
    }

    #[WithoutErrorHandler]
    #[DoesNotPerformAssertions]
    public function testGenericsTypeErrorTrace(){
        $object = new B;
            $object->foo(1,2);
    }
}


class A{function foo($x,$y){}}
class B{
    function foo($x,$y){
        try{return (fn(\ACME\Foo $y)=>
            parent::foo(...func_get_args()))($y);
        }catch(\TypeError $e){throw \Generics\TypeError::fromTypeError($e);}
    }
}