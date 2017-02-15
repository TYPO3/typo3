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
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Main UrlGenerator for creating URLs for the Backend. Generates a URL based on
 * an identifier defined by Configuration/Backend/Routes.php of an extension,
 * and adds some more parameters to the URL.
 *
 * Currently only available and useful when called from Router->generate() as the information
 * about possible routes needs to be handed over.
 */
class UriBuilder
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
     * @var Route[]
     */
    protected $routes;

    /**
     * Fetches the available routes from the Router to be used for generating routes
     */
    protected function loadBackendRoutes()
    {
        $router = GeneralUtility::makeInstance(Router::class);
        $this->routes = $router->getRoutes();
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
        $this->loadBackendRoutes();
        if (!isset($this->routes[$name])) {
            throw new RouteNotFoundException('Unable to generate a URL for the named route "' . $name . '" because this route was not found.');
        }

        $route = $this->routes[$name];

        // The Route is an AJAX route, so the parameters are different in order
        // for the AjaxRequestHandler to be triggered
        if ($route->getOption('ajax')) {
            // If the route has the "public" option set, no token is generated.
            if ($route->getOption('access') !== 'public') {
                $parameters = [
                    'ajaxToken' => FormProtectionFactory::get('backend')->generateToken('ajaxCall', $name)
                ] + $parameters;
            }

            // Add the Route path as &ajaxID=XYZ
            $parameters = [
                'ajaxID' => $route->getPath()
            ] + $parameters;
        } else {
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
        }

        return $this->buildUri($parameters, $referenceType);
    }

    /**
     * Generate a URI for a backend module, does not check if a module is available though
     *
     * @param string $moduleName The name of the module
     * @param array $parameters An array of parameters
     * @param string $referenceType The type of reference to be generated (one of the constants)
     *
     * @return Uri The generated Uri
     */
    public function buildUriFromModule($moduleName, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $parameters = [
            'M' => $moduleName,
            'moduleToken' => FormProtectionFactory::get('backend')->generateToken('moduleCall', $moduleName)
        ] + $parameters;
        return $this->buildUri($parameters, $referenceType);
    }

    /**
     * Returns the Ajax URL for a given AjaxID including a CSRF token.
     *
     * This method is only called by the core and must not be used by extensions.
     * Ajax URLs of all registered backend Ajax handlers are automatically published
     * to JavaScript inline settings: TYPO3.settings.ajaxUrls['ajaxId']
     *
     * @param string $ajaxIdentifier the ajaxID (used as GET parameter)
     * @param array $parameters An array of parameters
     * @param string $referenceType The type of reference to be generated (one of the constants)
     *
     * @return Uri The generated Uri
     */
    public function buildUriFromAjaxId($ajaxIdentifier, $parameters = [], $referenceType = self::ABSOLUTE_PATH)
    {
        $parameters = [
            'ajaxID' => $ajaxIdentifier
        ] + $parameters;
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['BE']['AJAX'][$ajaxIdentifier]['csrfTokenCheck'])) {
            $parameters['ajaxToken'] = FormProtectionFactory::get('backend')->generateToken('ajaxCall', $ajaxIdentifier);
        }
        return $this->buildUri($parameters, $referenceType);
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
        $uri = 'index.php?' . ltrim(GeneralUtility::implodeArrayForUrl('', $parameters, '', false, true), '&');
        if ($referenceType === self::ABSOLUTE_PATH) {
            $uri = PathUtility::getAbsoluteWebPath(PATH_typo3 . $uri);
        } else {
            $uri = GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR') . $uri;
        }
        return GeneralUtility::makeInstance(Uri::class, $uri);
    }
}
