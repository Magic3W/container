<?php namespace spitfire\provider;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * Provider is a dependency injection mechanism. It allows your
 * application to request an instance of a certain class and it
 * will 'autowire' it so all the depencencies needed for the 
 * object to function are already provided. Hence the name
 * 
 * @author César de la Cal Bretschneider
 */
class Provider implements \Psr\Container\ContainerInterface
{

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
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\Container\ContainerExceptionInterface
     */
    public function get($id) {

        $param = $this->items[$id]?? null;

        if ($param instanceof Service)   { return $param->instance(); }
        if ($param instanceof Closure)   { return $param(); }
        if (is_string($param)) { return new $this->get($param); }
        if (is_object($param)) { return $param; }
        
        try {
            #Autowiring logic
            $reflection = new ReflectionClass($id);
            $method     = $reflection->getMethod('__construct');
            $required   = $method->getParameters();


            $parameters = array_map(function (ReflectionParameter$e) {
                $class = $e->getClass()->getName();
                
                return $this->get($class);
            }, $required);
            
            return new $id(...$parameters);
        }
        catch (ReflectionException $e) {
            return new $id;
        }
    }

    /**
     * The set method allows the application to define a service for a certain key.
     * 
     * @param string $id   The key / classname the service should be located at
     * @param mixed  $item The service. May be an instance of a class, a string containing a classname, or a service
     */
    public function set($id, $item) {
        $this->items[$id] = $item;
        return $this;
    }

    /**
     * Checks if the provider can find a service that it can assemble. This does
     * not imply that a call to get will be smooth, get may still run into a 
     * \Psr\Container\ContainerExceptionInterface
     * 
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
     * @param string $id
     * @param mixed[] $parameters
     */
    public function make($id, $parameters) {
        $service = new Service($this, $id, $parameters);
        return $service->instance();
    }

    /**
     * Call makes it possible for applications to pass a closure with a certain
     * set of requirements, similar to how Javascript DI works, and our container
     * will provide the right arguments for the task.
     * 
     * @param Callable $fn
     * @return mixed The result of the function
     */
    public function call($fn) {
        //TODO : Implement
    }

    /**
     * Creates a reference to a service inside this container.
     * 
     * @param string $id         The identifier for the service
     * @param mixed  $parameters The identifier for the service
     * @return Service 
     */
    public function service($id, $parameters) {
        return new Service($this, $id, $parameters);
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