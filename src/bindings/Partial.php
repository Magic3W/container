<?php namespace spitfire\provider\bindings;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
use spitfire\provider\attributes\DefaultImplementation;
use spitfire\provider\Autowire;
use spitfire\provider\BindingInterface;
use spitfire\provider\Container;
use spitfire\provider\NotFoundException;

/**
 * A partial is a reference to a class and a series of parameters that can be
 * instanced with Provider.
 *
 * It's called partial because, with the given resources, an instance of the
 * class cannot be assembled. Instead, the container will need to provide missing
 * pieces.
 *
 * @template T of object
 * @implements BindingInterface<T>
 * @author César de la Cal Bretschneider
 */
class Partial implements BindingInterface
{
	
	/**
	 * The name of the class to be instanced
	 *
	 * @var class-string<T>
	 */
	private $classname;
	
	/**
	 * The parameters passed to the class' constructor.
	 *
	 * @var mixed
	 */
	private $parameters;
	
	/**
	 * Create a service. This allows the application to resolve the
	 * parameters for the given service.
	 *
	 * @param class-string<T> $classname
	 * @param mixed[] $parameters
	 */
	public function __construct($classname, $parameters)
	{
		$this->classname = $classname;
		$this->parameters = $parameters;
	}
	
	/**
	 * Allows to generate expressive
	 *
	 * @param string $name
	 * @param object $payload
	 * @throws NotFoundException
	 * @return Partial<T>
	 */
	public function needs($name, $payload)
	{
		if (class_exists($name)) {
			$reflection = new ReflectionClass($this->classname);
			$method     = $reflection->getMethod('__construct');
			$params     = $method->getParameters();
			$found      = false;
			
			foreach ($params as $param) {
				$type = $param->getType();
				
				if ($type instanceof ReflectionNamedType && $type->getName()) {
					$this->parameters[$param->getName()] = $payload;
					$found = true;
				}
			}
			
			if ($found) {
				return $this;
			}
			
			throw new NotFoundException(sprintf('Class %s does not depend on %s', $this->classname, $name));
		} else {
			throw new NotFoundException(sprintf('Class %s does not exist', $name));
		}
	}
	
	/**
	 * The with method allows the user to determine defaults to be applied to a
	 * certain class' parameters.
	 *
	 * @param string $name
	 * @param mixed $payload
	 * @throws NotFoundException
	 * @return Partial<T>
	 */
	public function with($name, $payload) : Partial
	{
		$this->parameters[$name] = $payload;
		return $this;
	}
	
	/**
	 * Creates a new instance of the service.
	 *
	 * @param Container $container
	 * @return T
	 */
	public function instance(Container $container): object
	{
		
		/**
		 * The service is not available, the class was not found, if we try to instance
		 * the class, we will get a fatal error.
		 */
		if (!class_exists((string)$this->classname)) {
			throw new NotFoundException(sprintf("Service %s was not found", $this->classname));
		}
		
		try {
			# Autowiring logic for the arguments
			$reflection = new ReflectionClass($this->classname);
			
			assert(
				$reflection->isSubclassOf($this->classname) || $reflection->getName() === $this->classname,
				sprintf("Expected %s, found %s", $reflection->getName(), $this->classname)
			);
			
			return (new Autowire($container))->class($reflection, $this->parameters);
		}
		/**
		 * If the class does not have a constructor the reflection of __construct
		 * will fail. This is a trivial issue, since we do accept classes without
		 * an explicit constructor we can resolve this issue by instancing the class
		 * without parameters.
		 */
		catch (ReflectionException $e) {
			return new $this->classname;
		}
	}
}
