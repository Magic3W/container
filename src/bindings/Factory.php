<?php namespace spitfire\provider\bindings;

use Closure;
use spitfire\provider\BindingInterface;
use spitfire\provider\Container;

class Factory implements BindingInterface
{
	
	/**
	 * 
	 * @var Closure
	 */
	private $factory;
	
	public function __construct(Closure $factory)
	{
		$this->factory = $factory;
	}
	
	public function instance(Container $container) : object
	{
		return $container->call($this->factory);
	}
	
}