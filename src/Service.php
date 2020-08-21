<?php namespace spitfire\provider;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * A service is a reference to a class and a series of parameters that can be 
 * instanced with Provider.
 * 
 * @author César de la Cal Bretschneider
 */
class Service
{

    private $provider;

    /**
     * The name of the class to be instanced
     * 
     * @var string
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
     */
    public function __construct(Provider$provider, $classname, $parameters)
    {
        $this->provider = $provider;
        $this->classname = $classname;
        $this->parameters = $parameters;
    }

    /**
     * Creates a new instance of the service.
     */
    public function instance() {

        try {
            # Autowiring logic for the arguments
            $reflection = new ReflectionClass($this->classname);
            $method     = $reflection->getMethod('__construct');
            $required   = $method->getParameters();

            $parameters = array_map(function (ReflectionParameter$e) {
                $name  = $e->getName();
                $param = $this->parameters[$name]?? null;

                if ($param instanceof Reference) { return $param->resolve(); }
                if ($param instanceof Closure)   { return $param(); }
                if (is_object($param)) { return $param; }
                
                try {
                    return $this->provider->get($e->getClass()->getName());
                }
                catch (ReflectionException $e) {
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
