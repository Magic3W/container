<?php namespace spitfire\provider\attributes;

use Attribute;
use ReflectionClass;

/**
 * @template T
 */
#[Attribute(Attribute::TARGET_CLASS)]
class DefaultImplementation
{
	
	/**
	 *
	 * @var class-string<T>
	 */
	private string $implementation;
	
	/**
	 *
	 * @param class-string<T> $implementation
	 */
	public function __construct(string $implementation)
	{
		$this->implementation = $implementation;
	}
	
	/**
	 *
	 * @return class-string<T>
	 */
	public function getImplementation() : string
	{
		return $this->implementation;
	}
	
	/**
	 *
	 * @template P of object
	 * @param class-string<P> $interface
	 * @return DefaultImplementation<P>
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
