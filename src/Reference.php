<?php namespace spitfire\provider;

/**
 * A service is a reference to a class and a series of parameters that can be 
 * instanced with Provider.
 * 
 * @author César de la Cal Bretschneider
 */
class Reference
{

    /**
     * @var Provider
     */
    private $container;
    private $key;

    public function __construct($container, $key)
    {
        $this->container = $container;
        $this->key = $key;
    }

    public function resolve() {
        return $this->container->get($this->key);
    }
}