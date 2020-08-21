<?php namespace spitfire\provider;

/**
 * A reference links a Provider instance with a service name. This 
 * creates a sort of promise to a service that can be resolved at 
 * a later stage.
 * 
 * @author César de la Cal Bretschneider
 */
class Reference
{

    /**
     * @var Provider
     */
    private $container;

    /**
     * The key identifying the class or service within provider.
     * 
     * @var string
     */
    private $key;

    public function __construct($container, $key)
    {
        $this->container = $container;
        $this->key = $key;
    }

    /**
     * Performs a lookup on the linked container to locate the dependency or
     * service and returns it.
     */
    public function resolve() {
        return $this->container->get($this->key);
    }
}
