<?php namespace spitfire\provider\bindings;

use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionParameter;
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
 * @author César de la Cal Bretschneider
 */
class Partial implements BindingInterface
{

	/**
	 * 
	 * @var Container
	 */
	private $provider;
	
	/**
	 * The name of the class to be instanced
	 * 
	 * @var class-string
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
	 * @param Container $provider
	 * @param class-string $classname
	 * @param mixed[] $parameters
	 */
	public function __construct(Container $provider, $classname, $parameters)
	{
		$this->provider = $provider;
		$this->classname = $classname;
		$this->parameters = $parameters;
	}

	/**
	 * Allows to generate expressive
	 * 
	 * @param string $name
	 * @param mixed $payload
	 * @throws NotFoundException
	 * @return Partial
	 */
	public function needs($name, $payload)
	{
		if (class_exists($name)) {

			$reflection = new ReflectionClass($this->classname);
			$method     = $reflection->getMethod('__construct');
			$params     = $method->getParameters();

			foreach ($params as $param) {
				$type = $param->getType();

				if ($type instanceof ReflectionNamedType && $type->getName()) {
					$this->parameters[$param->getName()] = $payload;
					return $this;
				}
			}

			throw new NotFoundException(sprintf('Class %s does not depend on %s', $this->classname, $name));
		} else {
			$this->parameters[$name] = $payload;
			return $this;
		}
	}

	/**
	 * Creates a new instance of the service.
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
			$method     = $reflection->getMethod('__construct');
			$required   = $method->getParameters();

			$parameters = array_map(function (ReflectionParameter $e) use ($container) {
				$name  = $e->getName();

				if (array_key_exists($name, $this->parameters)) {
					return $this->parameters[$name] instanceof BindingInterface ? $this->parameters[$name]->instance($container) : $this->parameters[$name];
				}

				try {
					$type = $e->getType();
						
					if (!($type instanceof ReflectionNamedType)) { 
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
					assert(class_exists($name));

					return $container->get($name);
				} catch (ReflectionException $e) {
					throw new NotFoundException($e->getMessage());
				}
			}, $required);

			return $reflection->newInstance(...$parameters);
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
