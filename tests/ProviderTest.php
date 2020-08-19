<?php

use PHPUnit\Framework\TestCase;
use spitfire\provider\Provider;


class A {}
class B {
    public $a;
    public function __construct(A $a)
    {
        #Doing nothing is okay here
        $this->a = $a;
    }
}
class C {
    public $a;
    public $b;

    public function __construct(A $a, B $b)
    {
        #Doing nothing is okay here
        $this->a = $a;
        $this->b = $b;
    }
}

class ProviderTest extends TestCase
{

    public function testGet() {
        $provider = new Provider();
        $b = $provider->get(B::class);

        $this->assertInstanceOf(B::class, $b);
        $this->assertInstanceOf(A::class, $b->a);
    }

    public function testMake() {
        $provider = new Provider();

        $a = new A();
        $c = $provider->make(C::class, ['a' => $a]);

        $this->assertInstanceOf(C::class, $c);
        $this->assertInstanceOf(B::class, $c->b);
        $this->assertInstanceOf(A::class, $c->b->a);
        $this->assertEquals($a, $c->a);
    }

}