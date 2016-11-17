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
use TYPO3\CMS\Backend\Routing\Exception\InvalidRequestTokenException;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\Dispatcher;
use TYPO3\CMS\Core\Http\DispatcherInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Dispatcher which resolves a route to call a controller and method (but also a callable)
 */
class RouteDispatcher extends Dispatcher implements DispatcherInterface
{
    /**
     * Main method to resolve the route and checks the target of the route, and tries to call it.
     *
     * @param ServerRequestInterface $request the current server request
     * @param ResponseInterface $response the prepared response
     * @return ResponseInterface the filled response by the callable / controller/action
     * @throws InvalidRequestTokenException if the route was not found
     * @throws \InvalidArgumentException if the defined target for the route is invalid
     */
    public function dispatch(ServerRequestInterface $request, ResponseInterface $response)
    {
        /** @var Router $router */
        $router = GeneralUtility::makeInstance(Router::class);
        /** @var Route $route */
        $route = $router->matchRequest($request);
        $request = $request->withAttribute('route', $route);
        if (!$this->isValidRequest($request)) {
            throw new InvalidRequestTokenException('Invalid request for route "' . $route->getPath() . '"', 1425389455);
        }

        $targetIdentifier = $route->getOption('target');
        $target = $this->getCallableFromTarget($targetIdentifier);
        return call_user_func_array($target, [$request, $response]);
    }

    /**
     * Wrapper method for static form protection utility
     *
     * @return \TYPO3\CMS\Core\FormProtection\AbstractFormProtection
     */
    protected function getFormProtection()
    {
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
    protected function isValidRequest($request)
    {
        $route = $request->getAttribute('route');
        if ($route->getOption('access') === 'public') {
            return true;
        } elseif ($route->getOption('ajax')) {
            $token = (string)(isset($request->getParsedBody()['ajaxToken']) ? $request->getParsedBody()['ajaxToken'] : $request->getQueryParams()['ajaxToken']);
            return $this->getFormProtection()->validateToken($token, 'ajaxCall', $route->getOption('_identifier'));
        } else {
            $token = (string)(isset($request->getParsedBody()['token']) ? $request->getParsedBody()['token'] : $request->getQueryParams()['token']);
            return $this->getFormProtection()->validateToken($token, 'route', $route->getOption('_identifier'));
        }
    }
}
