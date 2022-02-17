<?php namespace spitfire\provider\bindings;

use spitfire\provider\BindingInterface;
use spitfire\provider\Container;

/**
 * A reference links a Provider instance with a service name. This 
 * creates a sort of promise to a service that can be resolved at 
 * a later stage.
 * 
 * @template T of object
 * @implements BindingInterface<T>
 * @author César de la Cal Bretschneider
 */
class Reference implements BindingInterface
{
	
	/**
	 * The key identifying the class or service within provider.
	 * 
	 * @var class-string<T>
	 */
	private $key;
	
	/**
	 * 
	 * @param class-string<T> $key
	 */
	public function __construct($key)
	{
		$this->key = $key;
	}
	
	/**
	 * Performs a lookup on the linked container to locate the dependency or
	 * service and returns it.
	 * 
	 * @return T
	 */
	public function instance(Container $container) : object
	{
		return $container->get($this->key);
	}
}
