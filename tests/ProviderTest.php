<?php

use PHPUnit\Framework\TestCase;
use spitfire\provider\Container;
use spitfire\provider\NotFoundException;

class A
{
	public $a;
}
class B
{
	public $a;
	public function __construct(A $a)
	{
		#Doing nothing is okay here
		$this->a = $a;
	}
	
	public function method(A $a, string $str)
	{
		return $this->a->a . $str == $a->a;
	}
}
class C
{
	public $a;
	public $b;

	public function __construct(A $a, B $b)
	{
		#Doing nothing is okay here
		$this->a = $a;
		$this->b = $b;
	}
}
class E
{

	public function __construct(D $d)
	{
		return $d;
	}
}
class F
{

	public $a;
	
	public function __construct(A $a, string $hello)
	{
		$a->a = $hello;
		$this->a = $a;
	}
}

class G
{
	public function __construct(string $t = 'hello', A $a)
	{
	}
}

class H
{
	public $t;
	
	public function __construct($t = 'hello', A $a)
	{
		$this->t = $t;
	}
}

class ProviderTest extends TestCase
{

	public function testGet()
	{
		$provider = new Container();
		$b = $provider->get(B::class);

		$this->assertInstanceOf(B::class, $b);
		$this->assertInstanceOf(A::class, $b->a);
	}

	public function testMake()
	{
		$provider = new Container();

		$a = new A();
		$c = $provider->assemble(C::class, ['a' => $a]);

		$this->assertInstanceOf(C::class, $c);
		$this->assertInstanceOf(B::class, $c->b);
		$this->assertInstanceOf(A::class, $c->b->a);
		$this->assertEquals($a, $c->a);
	}

	public function testCall()
	{
		$provider = new Container();

		$c = $provider->call(function (C $c) {
			$this->assertInstanceOf(C::class, $c);
			$this->assertInstanceOf(B::class, $c->b);
			$this->assertInstanceOf(A::class, $c->b->a);
		});
	}

	public function testCallOnObjects() 
	{
		$provider = new Container();
		$test = $provider->get(B::class);

		$c = $provider->callMethod($test, 'method', ['str' => 'Hello world']);
		$this->assertEquals(false, $c);
	}

	public function testInvalidDependency()
	{
		$provider = new Container();

		$this->expectException(NotFoundException::class);
		$e = $provider->get(E::class);
	}
	
	public function testFactories()
	{
		$invoked  = false;
		$provider = new Container();
		$provider->factory(A::class, function () use (&$invoked) {
			$invoked = true;
			return new A();
		});
		$provider->get(A::class);
		
		$this->assertEquals(true, $invoked);
	}
	
	public function testFactoriesInherited()
	{
		$invoked  = false;
		$provider = new Container();
		$provider->factory(A::class, function () use (&$invoked) {
			$invoked = true;
			return new A();
		});
		$provider->get(B::class);
		
		$this->assertEquals(true, $invoked);
	}
	
	public function testSingleton()
	{
		$provider = new Container();
		$provider->singleton(A::class, function () {
			return new A();
		});
		
		$b1 = $provider->get(B::class);
		$b2 = $provider->get(B::class);
		
		$this->assertEquals(false, $b1 === $b2);
		$this->assertEquals(true, $b1->a === $b2->a);
	}
	
	public function testPartials()
	{
		$provider = new Container();
		$provider->service(F::class)->with('hello', 'world');
		
		$f = $provider->get(F::class);
		$this->assertEquals('world', $f->a->a);
	}
	
	/**
	 * When the service container receives a single instance of an 
	 * object to map to a certain service name, the object should always
	 * be returned whenever the application requests the service.
	 */
	public function testInstances()
	{
		$a = new A;
		$a->a = time();
		
		$provider = new Container();
		$provider->set(A::class, $a);
		
		$f = $provider->get(A::class);
		$this->assertEquals($a->a, $f->a);
	}
	
	/**
	 * When a class, method or function contains default values, we should
	 * avoid autowiring them if they are either unavailable or a scalar.
	 */
	public function testDefaults()
	{
		$provider = new Container();
		$g = $provider->get(G::class);
		
		$this->assertInstanceOf(G::class, $g);
	}
	
	/**
	 * When a class, method or function contains default values, we should
	 * avoid autowiring them if they are either unavailable or a scalar.
	 */
	public function testDefaults2()
	{
		$provider = new Container();
		$provider->set(G::class, G::class);
		
		$g = $provider->get(G::class);
		
		$this->assertInstanceOf(G::class, $g);
	}
	
	
	public function testDefaults3()
	{
		$provider = new Container();
		$h = $provider->get(H::class);
		
		$this->assertInstanceOf(H::class, $h);
	}
	
	
	public function testInjectingDefaults()
	{
		$provider = new Container();
		$provider->service(H::class)->with('t', 'test');
		
		$h = $provider->get(H::class);
		
		$this->assertInstanceOf(H::class, $h);
		$this->assertEquals('test', $h->t);
	}
	
	/**
	 * This test is a bit more intricate than the others. Here we do the following:
	 * 
	 * * We define that objects of class B are to instanced with an A that contains
	 *   a property a with the value 'hello'
	 * * We also instance a object of type C, which contains a B and an A
	 * * We expect that all the objects of type B have a object of type A with the 
	 *   property a set to 'hello'. Including those inside a C
	 * * We expect the objects of type A to have their property a to be empty if 
	 *   they are contained within a object of type C
	 */
	public function testInjectingDefaults2()
	{
		$a = new A;
		$a->a = 'hello';
		
		$provider = new Container();
		$provider->service(B::class)->needs(A::class, $a);
		
		$b = $provider->get(B::class);
		$c = $provider->get(C::class);
		
		$this->assertInstanceOf(B::class, $b);
		$this->assertEquals('hello', $b->a->a);
		$this->assertEquals('hello', $c->b->a->a);
		$this->assertEquals('', $c->a->a);
	}
}
