<?php namespace spitfire\provider\bindings;

use spitfire\provider\BindingInterface;
use spitfire\provider\Container;

/**
 * A reference links a Provider instance with a service name. This 
 * creates a sort of promise to a service that can be resolved at 
 * a later stage.
 * 
 * @author César de la Cal Bretschneider
 */
class Reference implements BindingInterface
{

    /**
     * @var Container
     */
    private $container;

    /**
     * The key identifying the class or service within provider.
     * 
     * @var string
     */
    private $key;

	/**
	 * 
	 * @param Container $container
	 * @param string $key
	 */
    public function __construct(Container $container, $key)
    {
        $this->container = $container;
        $this->key = $key;
    }

    /**
     * Performs a lookup on the linked container to locate the dependency or
     * service and returns it.
     */
	public function instance() : object
	{
        return $this->container->get($this->key);
	}
	
}
