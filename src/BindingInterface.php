<?php namespace spitfire\provider;

interface BindingInterface
{
	
	/**
	 * 
	 * @return object
	 */
	public function instance() : object;
}