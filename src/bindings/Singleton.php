<?php namespace spitfire\provider\bindings;

use Closure;
use spitfire\provider\BindingInterface;
use spitfire\provider\Container;

class Singleton extends Factory
{
	
	/**
	 * 
	 * @var object
	 */
	private $instance;
	
	/**
	 * @return object
	 */
	public function instance() : object
	{
		if ($this->instance === null) {
			$this->instance = parent::instance();
		}
		
		return $this->instance;
	}
	
}