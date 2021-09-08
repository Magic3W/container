<?php namespace spitfire\provider;

interface BindingInterface
{
	
	/**
	 * 
	 * @param Container $container
	 * @return object
	 */
	public function instance(Container $container) : object;
}