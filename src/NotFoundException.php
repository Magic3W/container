<?php namespace spitfire\provider;

use Exception;

/**
 * Provider throws this exception whenever a service cannot be
 * instanced and the container is unable to locate a valid service.
 * 
 * @author César de la Cal Bretschneider
 */
class NotFoundException extends Exception implements \Psr\Container\NotFoundExceptionInterface
{
	
}
