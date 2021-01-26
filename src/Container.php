<?php namespace spitfire\provider;

use Closure;
use Psr\Container\NotFoundExceptionInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionNamedType;
use ReflectionParameter;
use spitfire\provider\bindings\Factory;
use spitfire\provider\bindings\Partial;
use spitfire\provider\bindings\Reference;
use spitfire\provider\bindings\Service;
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
	 * 
	 * @var BindingInterface[]
	 */
    private $items = [];
    
    /**
     * The get method allows applications to retrieve a service the container
     * provides. Please note that attempts to retrieve a service unknown to the
     * container will result in the container attempting to assemble the required
     * class regardless.
     * 
     * Please note that you MUST NOT provide user input to this method, this
     * is very dangerous.
     * 
	 * @param string $id
     * @throws NotFoundException
     */
    public function get($id) {
        /**
         * Check if there is a service registered for this
         */
        if (isset($this->items[$id]) && $this->items[$id] instanceof BindingInterface) {
            return $this->items[$id]->instance();
        }
        
        /**
         * The service is not preregistered, the container will fallback to attempting
         * to locate the service with an autowiring automatism.
         */
        try {
			if (!class_exists($id)) {
				throw new NotFoundException('Attempting to autowire something that is not a class');
			}

            #Autowiring logic
            $method     = (new ReflectionClass($id))->getMethod('__construct');
            $required   = $method->getParameters();
			
			
            $parameters = array_map(function (ReflectionParameter$e) {
				#Get the named type to build. It is impossible for us to build anonymous types
				$type = $e->getType();
				if (!($type instanceof ReflectionNamedType)) { throw new NotFoundException('Unnamed types cannot be resolved by provider'); }
				
				try { return $this->get($type->getName()); }
                catch (ReflectionException $e) { throw new NotFoundException($e->getMessage()); }
            }, $required);
            
        }
        catch (ReflectionException $e) {
            $parameters = [];
        }
        catch (NotFoundException $ex) {
            throw new NotFoundException(sprintf("Service %s has missing dependencies", $id), 2008210958, $ex);
        }

        /**
         * The service is not available, the class was not found, if we try to instance
         * the class, we will get a fatal error.
         */
        if (!class_exists($id)) {
            throw new NotFoundException(sprintf("Service %s was not found", $id));
        }

        return new $id(...$parameters);
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

        if (is_string($item) && class_exists($item)) {
            $item = new Partial($this, $item, []);
		}
		
		if (is_object($item)) {
			$item = new Singleton($this, function () use ($item) { return $item; });
		}

        $this->items[$id] = $item;
        return $this;
	}
	
	
	/**
	 * 
	 * @param string $id
	 * @return Partial
	 */
	public function service(string $id) : Partial
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
		$this->items[$id] = new Factory($this, $callable);
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
		$this->items[$id] = new Singleton($this, $callable);
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
        if (array_key_exists($id, $this->items)) { return true; }

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
        return $service->instance();
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
			if (!($type instanceof ReflectionNamedType)) { throw new NotFoundException('Unnamed types cannot be resolved by provider'); }
			
            return $this->get($type->getName());
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
			if (array_key_exists($e->getName(), $params)) { return $params[$e->getName()]; }
			
			#Get the named type to build. It is impossible for us to build anonymous types
			$type = $e->getType();
			if (!($type instanceof ReflectionNamedType)) { throw new NotFoundException('Unnamed types cannot be resolved by provider'); }
			
            return $this->get($type->getName());
        }, $reflection->getParameters());

        return $object->$method(...$parameters);
    }

    /**
     * Creates a reference to a service inside this container.
     * 
     * @param string $id The identifier for the service
     * @return Reference to the service
     */
    public function reference($id) {
        return new Reference($this, $id);
    }

}
