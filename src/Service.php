<?php namespace spitfire\provider;

use Closure;

/**
 * A service is a reference to a class and a series of parameters that can be 
 * instanced with Provider.
 * 
 * @author César de la Cal Bretschneider
 */
class Service
{

    private $classname;
    private $parameters;

    public function __construct($classname, $parameters)
    {
        $this->classname = $classname;
        $this->parameters = $parameters;
    }

    public function instance() {
        $parameters = array_map(function ($e) {
            if ($e instanceof Reference) { return $e->resolve(); }
            if ($e instanceof Closure)   { return $e(); }
            if (is_string($e)) { return new $e; }
            if (is_object($e)) { return $e; }

            throw new \Exception("Invalid service parameter definition");
        }, $this->parameters);

        return new $this->classname(...$parameters);
    }
}