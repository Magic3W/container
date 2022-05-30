<?php namespace spitfire\provider;

/**
 *
 * @template T of object
 */
interface BindingInterface
{
	
	/**
	 *
	 * @param Container $container
	 * @return T
	 */
	public function instance(Container $container) : object;
}
