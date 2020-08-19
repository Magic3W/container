<?php namespace spitfire\provider;

use Closure;
use ReflectionClass;
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

        # Autowiring logic for the arguments
        $reflection = new ReflectionClass($this->classname);
        $method     = $reflection->getMethod('__construct');
        $required   = $method->getParameters();

        $parameters = array_map(function (ReflectionParameter$e) {
            $name  = $e->getName();
            $class = $e->getClass()->getName();
            $param = $this->parameters[$name]?? null;

            if ($param instanceof Reference) { return $param->resolve(); }
            if ($param instanceof Closure)   { return $param(); }
            if (is_string($param)) { return $this->provider->get($param); }
            if (is_object($param)) { return $param; }
            
            return $this->provider->get($class);
        }, $required);

        return $reflection->newInstance(...$parameters);
    }
}