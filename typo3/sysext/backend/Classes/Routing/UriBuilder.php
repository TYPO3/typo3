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

namespace TYPO3\CMS\Backend\Routing;

use Psr\Http\Message\UriInterface;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;
use TYPO3\CMS\Backend\Routing\Exception\MethodNotAllowedException;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\Exception\RouteTypeNotAllowedException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\RequestContextFactory;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Main UrlGenerator for creating URLs for the Backend. Generates a URL based on
 * an identifier defined by Configuration/Backend/Routes.php of an extension,
 * and adds some more parameters to the URL.
 *
 * Currently only available and useful when called from Router->generate() as the information
 * about possible routes needs to be handed over.
 */
class UriBuilder implements SingletonInterface
{
    /**
     * Generates an absolute URL
     */
    public const ABSOLUTE_URL = 'url';

    /**
     * Generates an absolute path
     */
    public const ABSOLUTE_PATH = 'absolute';

    /**
     * Generates an absolute url for URL sharing
     */
    public const SHAREABLE_URL = 'share';

    /**
     * @var array<non-empty-string, UriInterface>
     */
    protected array $generated = [];

    protected RequestContext|null $requestContext = null;
    /**
     * Loads the router to fetch the available routes from the Router to be used for generating routes
     */
    public function __construct(
        protected readonly Router $router,
        protected readonly FormProtectionFactory $formProtectionFactory,
        protected readonly RequestContextFactory $requestContextFactory
    ) {}

    /**
     * @internal
     */
    public function setRequestContext(RequestContext $requestContext): void
    {
        $this->requestContext = $requestContext;
    }

    /**
     * Generates a URL or path for a specific route based on the given route.
     * Currently used to link to the current script, it is encouraged to use "buildUriFromRoute" if possible.
     *
     * If there is no route with the given name, the generator throws the RouteNotFoundException.
     *
     * @param string $pathInfo The path to the route
     * @param array $parameters An array of parameters
     * @param string $referenceType The type of reference to be generated (one of the constants)
     * @return UriInterface The generated Uri
     * @throws RouteNotFoundException If the named route doesn't exist
     */
    public function buildUriFromRoutePath($pathInfo, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $route = $this->router->match($pathInfo);
        return $this->buildUriFromRoute($route->getOption('_identifier'), $parameters, $referenceType);
    }

    /**
     * Creates a link to a page with a route targetted as a redirect, if a "deep link" is possible.
     * Currently works just fine for URLs built for "main" and "login" pages.
     *
     * @param RouteRedirect|null $redirect
     * @throws RouteNotFoundException
     * @internal this is experimental API used for creating logins to redirect to a different route
     */
    public function buildUriWithRedirect(string $name, array $parameters = [], RouteRedirect $redirect = null, string $referenceType = self::ABSOLUTE_PATH): UriInterface
    {
        if ($redirect === null) {
            return $this->buildUriFromRoute($name, $parameters, $referenceType);
        }

        try {
            $redirect->resolve($this->router);
        } catch (RouteNotFoundException|RouteTypeNotAllowedException|MethodNotAllowedException $e) {
            return $this->buildUriFromRoute($name, $parameters, $referenceType);
        }
        $parameters['redirect'] = $redirect->getName();
        if ($redirect->hasParameters()) {
            $parameters['redirectParams'] = $redirect->getFormattedParameters();
        }
        return $this->buildUriFromRoute($name, $parameters, $referenceType);
    }

    /**
     * Generates a URL or path for a specific route based on the given parameters.
     * When the route is configured with "access=public" then the token generation is left out.
     *
     * If there is no route with the given name, the generator throws the RouteNotFoundException.
     *
     * @param string $name The name of the route
     * @param array $parameters An array of parameters
     * @param string $referenceType The type of reference to be generated (one of the constants)
     * @return UriInterface The generated Uri
     * @throws RouteNotFoundException If the named route doesn't exist
     */
    public function buildUriFromRoute($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $cacheIdentifier = 'route' . $name . serialize($parameters) . $referenceType;
        if (isset($this->generated[$cacheIdentifier])) {
            return $this->generated[$cacheIdentifier];
        }

        $route = $this->router->getRoute((string)$name);
        if ($route === null) {
            throw new RouteNotFoundException('Unable to generate a URL for the named route "' . $name . '" because this route was not found.', 1476050190);
        }

        $parameters = array_merge(
            $route->getOption('parameters') ?? [],
            $parameters
        );

        // If the route is not shareable and doesn't have the "public" option set, a token must be generated.
        if ($referenceType !== self::SHAREABLE_URL && (!$route->hasOption('access') || $route->getOption('access') !== 'public')) {
            $parameters = [
                'token' => $this->formProtectionFactory->createForType('backend')->generateToken('route', $name),
            ] + $parameters;
        }

        $this->generated[$cacheIdentifier] = $this->buildUri($name, $parameters, (string)$referenceType);
        return $this->generated[$cacheIdentifier];
    }

    /**
     * Internal method building a Uri object, merging the GET parameters array into a flat queryString
     *
     * @param string $routeName The route name in the collection
     * @param array $parameters An array of GET parameters
     * @param string $referenceType The type of reference to be generated (one of the constants)
     */
    protected function buildUri(string $routeName, array $parameters, string $referenceType): UriInterface
    {
        if (isset($this->requestContext)) {
            $requestContext = $this->requestContext;
        } elseif (isset($GLOBALS['TYPO3_REQUEST'])) {
            $requestContext = $this->requestContextFactory->fromBackendRequest($GLOBALS['TYPO3_REQUEST']);
        } elseif (!Environment::isCli()) {
            $requestContext = $this->requestContextFactory->fromBackendRequest(ServerRequestFactory::fromGlobals()->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE));
        } else {
            $requestContext = new RequestContext('/typo3/');
        }
        $urlGenerator = new UrlGenerator($this->router->getRouteCollection(), $requestContext);
        $url = $urlGenerator->generate($routeName, $parameters, $referenceType === self::ABSOLUTE_PATH ? UrlGeneratorInterface::ABSOLUTE_PATH : UrlGeneratorInterface::ABSOLUTE_URL);
        return new Uri($url);
    }
}
