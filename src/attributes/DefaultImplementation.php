<?php namespace spitfire\provider\attributes;

use Attribute;
use ReflectionClass;

#[Attribute(Attribute::TARGET_CLASS)]
class DefaultImplementation
{
	
	/**
	 *
	 * @var class-string
	 */
	private string $implementation;
	
	/**
	 *
	 * @param class-string $implementation
	 */
	public function __construct(string $implementation)
	{
		$this->implementation = $implementation;
	}
	
	/**
	 *
	 * @return class-string
	 */
	public function getImplementation() : string
	{
		return $this->implementation;
	}
	
	/**
	 *
	 * @param class-string $interface
	 * @return DefaultImplementation
	 */
	public static function for(string $interface) :? DefaultImplementation
	{
		
		$reflection = new ReflectionClass($interface);
		$attribute  = $reflection->getAttributes(DefaultImplementation::class);
		
		if (!isset($attribute[0])) {
			return null;
		}
		
		return $attribute[0]->newInstance();
	}
}
