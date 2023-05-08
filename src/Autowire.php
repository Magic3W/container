<?php namespace spitfire\provider;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use spitfire\provider\attributes\DefaultImplementation;

class Autowire
{
	
	/**
	 * The container to look in for components that may be needed for autowiring
	 * the current component.
	 *
	 * @var Container
	 */
	private Container $container;
	
	public function __construct(Container $container)
	{
		$this->container = $container;
	}
	
	/**
	 *
	 * @template T of Object
	 * @param ReflectionClass<T> $reflection
	 * @param mixed[] $overrides
	 * @return T
	 */
	public function class(ReflectionClass $reflection, array $overrides = []) : object
	{
		
		$method     = $reflection->getMethod('__construct');
		$required   = $method->getParameters();
		
		$parameters = array_map(function (ReflectionParameter $e) use ($overrides) {
			$name  = $e->getName();
			
			if (!array_key_exists($name, $overrides)) {
				return $this->argument($e);
			}
			
			return $overrides[$name] instanceof BindingInterface ?
				$overrides[$name]->instance($this->container) :
				$overrides[$name];
		}, $required);
		
		return $reflection->newInstance(...$parameters);
	}
	
	/**
	 * @return mixed
	 */
	public function argument(ReflectionParameter $e)
	{
		$name  = $e->getName();
		
		try {
			$type = $e->getType();
			
			/**
			 * PHP doesn't require the developer of a class to explicitly determine the
			 * types of the arguments. If this is the case, we cannot help the instancing
			 * of the class beyond using a default if available.
			 */
			if (!($type instanceof ReflectionNamedType)) {
				/**
				 * If the developer didn't explicitly set the type, we check if they provided a
				 * default value that the application can use to invoke the object.
				 *
				 * This is for methods that look like this: `public function __construct($t = 'hello')`
				 *
				 * Notice in the example that $t does not have a type declaration.
				 */
				if ($e->isDefaultValueAvailable()) {
					return $e->getDefaultValue();
				}
				
				throw new NotFoundException('Anonymous types cannot be resolved');
			}
			
			/**
			 * In case we have a built-in type that does not require being set by the user
			 * because a default value is available, we resort to using that.
			 */
			if ($type->isBuiltin() && $e->isDefaultValueAvailable()) {
				return $e->getDefaultValue();
			}
			
			$name = $type->getName();
			
			/**
			 * If the class we're trying to locate is unavailable, we will not continue, since
			 * it will obviously produce no valid result.
			 */
			if (!class_exists($name) && !interface_exists($name)) {
				throw new NotFoundException(sprintf("Service %s was not found", $name));
			}
			
			try {
				return $this->container->get($name);
			}
			catch (NotFoundException $e) {
				$attribute  = DefaultImplementation::for($name);
				
				if ($attribute === null) {
					throw new NotFoundException(sprintf('Service %s was not found', $name), 0, $e);
				}
				
				return $this->container->get($attribute->getImplementation());
			}
		} catch (ReflectionException $e) {
			throw new NotFoundException($e->getMessage());
		}
	}
}
