<?php namespace spitfire\provider\bindings;

use Closure;
use spitfire\provider\Container;

/**
 *
 * @template T of object
 * @extends Factory<T>
 */
class Singleton extends Factory
{
	
	/**
	 *
	 * @var T
	 */
	private $instance;
	
	/**
	 * @return T
	 */
	public function instance(Container $provider) : object
	{
		if ($this->instance === null) {
			$this->instance = parent::instance($provider);
		}
		
		return $this->instance;
	}
}
