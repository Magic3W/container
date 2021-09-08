<?php

use PHPUnit\Framework\TestCase;
use spitfire\provider\Container;

/**
 * 
 * @depends ProviderTest::testGet
 */
class NestedProviderTest extends TestCase
{
	
	public function testExtend()
	{
		$parent = new Container();
		$child  = new Container($parent);
		
		$parentA = new A;
		$parentA->a = 'parent';
		
		$parent->set(A::class, $parentA);
		
		$parentB = $parent->get(B::class);
		$childB  = $child->get(B::class);
		
		$this->assertEquals('parent', $parentB->a->a);
		$this->assertEquals('parent', $childB->a->a);
	}
	
	public function testOverride()
	{
		$parent = new Container();
		$child  = new Container($parent);
		
		$parentA = new A;
		$parentA->a = 'parent';
		
		$childA = new A;
		$childA->a = 'child';
		
		$parent->set(A::class, $parentA);
		$child->set(A::class, $childA);
		
		$parentB = $parent->get(B::class);
		$childB  = $child->get(B::class);
		
		$this->assertEquals('parent', $parentB->a->a);
		$this->assertEquals('child',  $childB->a->a);
	}
	
	public function testProtypes()
	{
		$parent = new Container();
		$child  = new Container($parent);
		
		
		$this->assertEquals($parent, $parent->get(Container::class));
		$this->assertEquals($child,  $child->get(Container::class));
	}
}