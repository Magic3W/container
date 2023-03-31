<?php

use PHPUnit\Framework\TestCase;
use spitfire\provider\Container;

class CallWithDefaultStringsTest extends TestCase
{
	
	public function testWithDefaultString()
	{
		$container = new Container();
		
		$callable = function (string $test = 'Hello world') {
			return $test;
		};
		
		$this->assertEquals(
			'Hello world',
			$container->call($callable)
		);
		
		$this->assertEquals(
			'Bye world',
			$container->call($callable, ['test' => 'Bye world'])
		);
	}
	
	public function testWithDefaultStringAndOtherType()
	{
		$container = new Container();
		
		$callable = function (Container $container, string $test = 'Hello world') {
			return $test;
		};
		
		$this->assertEquals(
			'Hello world',
			$container->call($callable)
		);
		
		$this->assertEquals(
			'Bye world',
			$container->call($callable, ['test' => 'Bye world'])
		);
	}
}