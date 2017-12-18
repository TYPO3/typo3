<?php
namespace TYPO3\CMS\Backend\Routing;

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

use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

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
    const ABSOLUTE_URL = 'url';

    /**
     * Generates an absolute path
     */
    const ABSOLUTE_PATH = 'absolute';

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var array
     */
    protected $generated = [];

    /**
     * Loads the router to fetch the available routes from the Router to be used for generating routes
     * @param Router|null $router
     */
    public function __construct(Router $router = null)
    {
        $this->router = $router ?? GeneralUtility::makeInstance(Router::class);
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
     * @return Uri The generated Uri
     * @throws RouteNotFoundException If the named route doesn't exist
     */
    public function buildUriFromRoutePath($pathInfo, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $router = GeneralUtility::makeInstance(Router::class);
        $route = $router->match($pathInfo);
        return $this->buildUriFromRoute($route->getOption('_identifier'), $parameters, $referenceType);
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
     * @return Uri The generated Uri
     * @throws RouteNotFoundException If the named route doesn't exist
     */
    public function buildUriFromRoute($name, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $cacheIdentifier = 'route' . $name . serialize($parameters) . $referenceType;
        if (isset($this->generated[$cacheIdentifier])) {
            return $this->generated[$cacheIdentifier];
        }
        if (!isset($this->router->getRoutes()[$name])) {
            throw new RouteNotFoundException('Unable to generate a URL for the named route "' . $name . '" because this route was not found.', 1476050190);
        }

        $route = $this->router->getRoutes()[$name];
        $parameters = array_merge(
            $route->getOptions()['parameters'] ?? [],
            $parameters
        );

        // If the route has the "public" option set, no token is generated.
        if ($route->getOption('access') !== 'public') {
            $parameters = [
                'token' => FormProtectionFactory::get('backend')->generateToken('route', $name)
            ] + $parameters;
        }

        // Add the Route path as &route=XYZ
        $parameters = [
            'route' => $route->getPath()
        ] + $parameters;

        $this->generated[$cacheIdentifier] = $this->buildUri($parameters, $referenceType);
        return $this->generated[$cacheIdentifier];
    }

    /**
     * Generate a URI for a backend module, does not check if a module is available though
     *
     * @param string $moduleName The name of the module
     * @param array $parameters An array of parameters
     * @param string $referenceType The type of reference to be generated (one of the constants)
     *
     * @return Uri The generated Uri
     * @deprecated since TYPO3 v9.3, will be removed in TYPO3 v10.0. Use buildUriFromRoute() instead.
     */
    public function buildUriFromModule($moduleName, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        trigger_error('UriBuilder->buildUriFromModule() will be removed in TYPO3 v10.0, use buildUriFromRoute() instead.', E_USER_DEPRECATED);
        $cacheIdentifier = 'module' . $moduleName . serialize($parameters) . $referenceType;
        if (isset($this->generated[$cacheIdentifier])) {
            return $this->generated[$cacheIdentifier];
        }
        $parameters = [
            'route' => $moduleName,
            'token' => FormProtectionFactory::get('backend')->generateToken('route', $moduleName)
        ] + $parameters;
        $this->generated[$cacheIdentifier] = $this->buildUri($parameters, $referenceType);
        return $this->generated[$cacheIdentifier];
    }

    /**
     * Internal method building a Uri object, merging the GET parameters array into a flat queryString
     *
     * @param array $parameters An array of GET parameters
     * @param string $referenceType The type of reference to be generated (one of the constants)
     *
     * @return Uri
     */
    protected function buildUri($parameters, $referenceType)
    {
        $uri = 'index.php' . HttpUtility::buildQueryString($parameters, '?');
        if ($referenceType === self::ABSOLUTE_PATH) {
            $uri = PathUtility::getAbsoluteWebPath(Environment::getBackendPath() . '/' . $uri);
        } else {
            $uri = GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR') . $uri;
        }
        return GeneralUtility::makeInstance(Uri::class, $uri);
    }
}
