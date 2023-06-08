<?php

use PHPUnit\Framework\TestCase;
use spitfire\provider\Container;

/**
 * Contains issues raised by infection while working on some autowiring.
 */
class R230608Test extends TestCase
{
	
	public function testSetReturnsItself()
	{
		$container = new Container();
		$this->assertInstanceOf(Container::class, $container->set(self::class, $this));
	}
}