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
	
	/**
	 * 
	 * @var Container
	 */
	private $container;
	
	public function __construct(Container $container, Closure $factory)
	{
		$this->container = $container;
		$this->factory = $factory;
	}
	
	public function instance() : object
	{
		return $this->container->call($this->factory);
	}
	
}