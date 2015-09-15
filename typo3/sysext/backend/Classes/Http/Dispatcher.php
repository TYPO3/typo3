<?php
namespace TYPO3\CMS\Backend\Http;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Core\Http\DispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;

/**
 * Dispatcher which resolves a route to call a controller and method (but also a callable)
 */
class Dispatcher implements DispatcherInterface {

	/**
	 * Main method to resolve the route and checks the target of the route, and tries to call it.
	 *
	 * @param ServerRequestInterface $request the current server request
	 * @param ResponseInterface $response the prepared response
	 * @return ResponseInterface the filled response by the callable / controller/action
	 * @throws RouteNotFoundException if the route was not found
	 * @throws \InvalidArgumentException if the defined target for the route is invalid
	 */
	public function dispatch(ServerRequestInterface $request, ResponseInterface $response) {
		/** @var Route $route */
		$router = GeneralUtility::makeInstance(Router::class);
		$route = $router->matchRequest($request);
		$request = $request->withAttribute('route', $route);
		if (!$this->isValidRequest($request)) {
			throw new RouteNotFoundException('Invalid request for route "' . $route->getPath() . '"', 1425389455);
		}

		$targetIdentifier = $route->getOption('target');
		$target = $this->getCallableFromTarget($targetIdentifier);
		return call_user_func_array($target, array($request, $response));
	}

	/**
	 * Wrapper method for static form protection utility
	 *
	 * @return \TYPO3\CMS\Core\FormProtection\AbstractFormProtection
	 */
	protected function getFormProtection() {
		return FormProtectionFactory::get();
	}

	/**
	 * Checks if the request token is valid. This is checked to see if the route is really
	 * created by the same instance. Should be called for all routes in the backend except
	 * for the ones that don't require a login.
	 *
	 * @param \Psr\Http\Message\ServerRequestInterface $request
	 * @return bool
	 * @see \TYPO3\CMS\Backend\Routing\UriBuilder where the token is generated.
	 */
	protected function isValidRequest($request) {
		$token = (string)(isset($request->getParsedBody()['token']) ? $request->getParsedBody()['token'] : $request->getQueryParams()['token']);
		$route = $request->getAttribute('route');
		return ($route->getOption('access') === 'public' || $this->getFormProtection()->validateToken($token, 'route', $route->getOption('_identifier')));
	}

	/**
	 * Creates a callable out of the given parameter, which can be a string, a callable / closure or an array
	 * which can be handed to call_user_func_array()
	 *
	 * @param array|string|callable $target the target which is being resolved.
	 * @return callable
	 * @throws \InvalidArgumentException
	 */
	protected function getCallableFromTarget($target) {
		if (is_array($target)) {
			return $target;
		}

		if (is_object($target) && $target instanceof \Closure) {
			return $target;
		}

		// Only a class name is given
		if (is_string($target) && strpos($target, ':') === FALSE) {
			$target = GeneralUtility::makeInstance($target);
			if (method_exists($target, '__invoke')) {
				return $target;
			}
		}

		// Check if the target is a concatenated string of "className::actionMethod"
		if (is_string($target) && strpos($target, '::') !== FALSE) {
			list($className, $methodName) = explode('::', $target, 2);
			$targetObject = GeneralUtility::makeInstance($className);
			return [$targetObject, $methodName];
		}

		// This needs to be checked at last as a string with object::method is recognize as callable
		if (is_callable($target)) {
			return $target;
		}

		throw new \InvalidArgumentException('Invalid target for "' . $target. '", as it is not callable.', 1425381442);
	}
}