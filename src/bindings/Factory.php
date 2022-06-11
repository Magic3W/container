<?php namespace spitfire\provider\bindings;

use Closure;
use spitfire\provider\BindingInterface;
use spitfire\provider\Container;

/**
 *
 * @template T of object
 * @implements BindingInterface<T>
 */
class Factory implements BindingInterface
{
	
	/**
	 *
	 * @var callable():T
	 */
	private $factory;
	
	/**
	 *
	 * @param callable():T $factory
	 */
	public function __construct(callable $factory)
	{
		$this->factory = $factory;
	}
	
	/**
	 *
	 * @return T
	 */
	public function instance(Container $container) : object
	{
		return $container->call($this->factory);
	}
}
