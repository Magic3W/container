<?php namespace spitfire\provider;

use Closure;
use ReflectionClass;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use spitfire\provider\bindings\Factory;
use spitfire\provider\bindings\Partial;
use spitfire\provider\bindings\Reference;
use spitfire\provider\bindings\Singleton;

/**
 * Provider is a dependency injection mechanism. It allows your
 * application to request an instance of a certain class and it
 * will 'autowire' it so all the depencencies needed for the 
 * object to function are already provided. Hence the name
 * 
 * @author César de la Cal Bretschneider
 */
class Container implements \Psr\Container\ContainerInterface
{

	/**
	 * The prototype allows our service provider to override certain elements of another
	 * service provider without 
	 * 
	 * @var Container|null
	 */
	private $prototype = null;

	/**
	 * 
	 * @var BindingInterface[]
	 */
	private $items = [];

	public function __construct(Container $prototype = null)
	{
		$this->prototype = $prototype;
		$this->items[Container::class] = new Singleton(function () { return $this; });
	}

	/**
	 * The get method allows applications to retrieve a service the container
	 * provides. Please note that attempts to retrieve a service unknown to the
	 * container will result in the container attempting to assemble the required
	 * class regardless.
	 * 
	 * Please note that you MUST NOT provide user input to this method, this
	 * is very dangerous.
	 * 
	 * @param class-string $id
	 * @throws NotFoundException
	 */
	public function get($id)
	{
		/**
		 * Check if there is a service registered for this
		 */
		if (isset($this->items[$id])) {
			/**
			 * The items array must only contain bindings, for debugging purposes we assert
			 * that this is the case, if it isn't, the application should fail. Applications
			 * in production should not need to perform this check every time.
			 */
			assert($this->items[$id] instanceof BindingInterface);
			return $this->items[$id]->instance($this);
		}

		if ($this->prototype) {
			return $this->prototype->getIn($id, $this);
		}

		/**
		 * The service is not preregistered, the container will fallback to attempting
		 * to locate the service with an autowiring automatism.
		 */
		$partial = new Partial($this, $id, []);
		return $partial->instance($this);
	}

	/**
	 * The get method allows applications to retrieve a service the container
	 * provides. Please note that attempts to retrieve a service unknown to the
	 * container will result in the container attempting to assemble the required
	 * class regardless.
	 * 
	 * Please note that you MUST NOT provide user input to this method, this
	 * is very dangerous.
	 * 
	 * @param class-string $id
	 * @param Container $container
	 * @throws NotFoundException
	 */
	public function getIn($id, Container $container): object
	{
		/**
		 * Check if there is a service registered for this
		 */
		if (isset($this->items[$id])) {
			/**
			 * The items array must only contain bindings, for debugging purposes we assert
			 * that this is the case, if it isn't, the application should fail. Applications
			 * in production should not need to perform this check every time.
			 */
			assert($this->items[$id] instanceof BindingInterface);
			return $this->items[$id]->instance($container);
		}

		if ($this->prototype) {
			return $this->prototype->getIn($id, $container);
		}

		/**
		 * The service is not preregistered, the container will fallback to attempting
		 * to locate the service with an autowiring automatism.
		 */
		$partial = new Partial($this, $id, []);
		return $partial->instance($container);
	}

	/**
	 * The set method allows the application to define a service for a certain key.
	 * 
	 * @param string $id   The key / classname the service should be located at
	 * @param mixed  $item The service. May be an instance of a class, a string containing a classname, or a service
	 * @return Container
	 */
	public function set($id, $item)
	{
		
		if (is_object($item)) {
			$item = new Singleton(function () use ($item) {
				return $item;
			});
		}
		
		if (is_string($item) && class_exists($item)) {
			$item = new Partial($this, $item, []);
		}

		$this->items[$id] = $item;
		return $this;
	}


	/**
	 * 
	 * @param string $id
	 * @return Partial
	 */
	public function service(string $id): Partial
	{
		if (!isset($this->items[$id]) && class_exists($id)) {
			$item = new Partial($this, $id, []);
			$this->items[$id] = $item;
		}
		
		if ($this->items[$id] instanceof Partial) {
			return $this->items[$id];
		}

		throw new NotFoundException('Invalid partial');
	}

	/**
	 * 
	 * @param string $id
	 * @param Closure $callable
	 * @return Container
	 */
	public function factory($id, Closure $callable)
	{
		$this->items[$id] = new Factory($callable);
		return $this;
	}


	/**
	 * 
	 * @param string $id
	 * @param Closure $callable
	 * @return Container
	 */
	public function singleton($id, Closure $callable)
	{
		$this->items[$id] = new Singleton($callable);
		return $this;
	}

	/**
	 * Checks if the provider can find a service that it can assemble. This does
	 * not imply that a call to get will be smooth, get may still run into a 
	 * \Psr\Container\ContainerExceptionInterface
	 * 
	 * @param string $id
	 * @return bool
	 */
	public function has($id)
	{
		/**
		 * If the service to be provided was registered, we can immediately
		 * report back to the user that the service exists
		 */
		if (array_key_exists($id, $this->items)) {
			return true;
		}

		/**
		 * Otherwise, we make sure that the class exists. If the class can be
		 * found, we assume that the service can be constructed
		 */
		return class_exists($id);
	}

	/**
	 * Uses the container as a factory, you provide arguments that may not be
	 * automatically resolved or you wish to override.
	 * 
	 * @param class-string $id
	 * @param mixed[] $parameters
	 * @return object
	 */
	public function assemble($id, $parameters = [])
	{
		$service = new Partial($this, $id, $parameters);
		return $service->instance($this);
	}

	/**
	 * Call makes it possible for applications to pass a closure with a certain
	 * set of requirements, similar to how Javascript DI works, and our container
	 * will provide the right arguments for the task.
	 * 
	 * @param Closure $fn
	 * @return mixed The result of the function
	 */
	public function call(Closure $fn)
	{
		$reflection = new ReflectionFunction($fn);
		$parameters = array_map(function (ReflectionParameter $e) {

			#Get the named type to build. It is impossible for us to build anonymous types
			$type = $e->getType();
			if (!($type instanceof ReflectionNamedType)) {
				throw new NotFoundException('Unnamed types cannot be resolved by provider');
			}

			$name = $type->getName();
			assert(class_exists($name));

			return $this->get($name);
		}, $reflection->getParameters());

		return $fn(...$parameters);
	}

	/**
	 * Invokes a method on an object, providing all the dependencies necessary to 
	 * make the method work.
	 * 
	 * This method is a bad candidate for classes where you already know the name
	 * of the method being executed (since it makes it hard for IDEs to find usages)
	 * but it is a good replacement for any situation where you were forced to use
	 * a construct like `user_func_array` to invoke a method.
	 * 
	 * @param object $object
	 * @param string $method
	 * @param mixed[] $params
	 * @return mixed Passthrough of the data returned by the method
	 */
	public function callMethod(object $object, $method, $params = [])
	{
		$reflection = (new ReflectionClass($object))->getMethod($method);
		$parameters = array_map(function (ReflectionParameter $e) use ($params) {

			#If the parameter was provided as an override by the user, we can just use that
			if (array_key_exists($e->getName(), $params)) {
				return $params[$e->getName()];
			}

			#Get the named type to build. It is impossible for us to build anonymous types
			$type = $e->getType();
			if (!($type instanceof ReflectionNamedType)) {
				throw new NotFoundException('Unnamed types cannot be resolved by provider');
			}

			$name = $type->getName();
			assert(class_exists($name));

			return $this->get($name);
		}, $reflection->getParameters());

		return $object->$method(...$parameters);
	}

	/**
	 * Creates a reference to a service inside this container.
	 * 
	 * @param class-string $id The identifier for the service
	 * @return Reference to the service
	 */
	public function reference($id)
	{
		return new Reference($id);
	}
}
