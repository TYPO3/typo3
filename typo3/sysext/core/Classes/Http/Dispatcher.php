<?php

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Http;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Dispatcher which resolves a target, which was given to the request to call a controller and method (but also a callable)
 * where the request contains a "target" as attribute.
 *
 * Used in eID Frontend Requests, see EidHandler
 */
class Dispatcher implements DispatcherInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Main method that fetches the target from the request and calls the target directly
     *
     * @param ServerRequestInterface $request the current server request
     * @return ResponseInterface the filled response by the callable/controller/action
     * @throws \InvalidArgumentException if the defined target is invalid
     */
    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        $targetIdentifier = $request->getAttribute('target');
        $target = $this->getCallableFromTarget($targetIdentifier);
        $arguments = [$request];
        return $target(...$arguments);
    }

    /**
     * Creates a callable out of the given parameter, which can be a string, a callable / closure or an array
     * which can be invoked as a function.
     *
     * @param array|string|callable $target the target which is being resolved.
     * @return callable
     * @throws \InvalidArgumentException
     */
    protected function getCallableFromTarget($target)
    {
        if (is_array($target)) {
            return $target;
        }

        if (is_object($target) && $target instanceof \Closure) {
            return $target;
        }

        // Only a class name is given
        if (is_string($target) && !str_contains($target, ':')) {
            $targetObject = $this->container->has($target) ? $this->container->get($target) : GeneralUtility::makeInstance($target);
            if (!method_exists($targetObject, '__invoke')) {
                throw new \InvalidArgumentException('Object "' . $target . '" doesn\'t implement an __invoke() method and cannot be used as target.', 1442431631);
            }
            return $targetObject;
        }

        // Check if the target is a concatenated string of "className::actionMethod"
        if (is_string($target) && str_contains($target, '::')) {
            [$className, $methodName] = explode('::', $target, 2);
            $targetObject = $this->container->has($className) ? $this->container->get($className) : GeneralUtility::makeInstance($className);
            return [$targetObject, $methodName];
        }

        // Closures needs to be checked at last as a string with object::method is recognized as callable
        if (is_callable($target)) {
            return $target;
        }

        throw new \InvalidArgumentException('Invalid target for "' . $target . '", as it is not callable.', 1425381442);
    }
}
