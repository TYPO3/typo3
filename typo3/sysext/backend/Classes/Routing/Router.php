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

use TYPO3\CMS\Backend\Routing\Generator\UrlGenerator;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\Exception\ResourceNotFoundException;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Implementation of a class for registering routes, used throughout the Bootstrap
 * to register all sorts of Backend Routes, and to fetch the main Collection in order
 * to resolve a route (see ->match()).
 *
 * For the TYPO3 Backend there is currently only one "main" RouteCollection
 * collecting all routes and caches them away.
 *
 * See the main methods currently relevant:
 * ->generate()
 * ->match()
 *
 * Ideally, the Router is solely instantiated and accessed via the Bootstrap but is currently
 * also used inside BackendUtility for generating URLs. Although this serves as single entrypoint for the routing
 * logic currently, it should only be used within the RequestHandler, Bootstrap and the BackendUtility.
 *
 * See \TYPO3\CMS\Backend\RequestHandler for more details on route matching() and Bootstrap->initializeBackendRouting().
 *
 * The architecture is highly inspired by the Symfony Routing Component.
 * The general integration approach into the TYPO3 Backend is to only
 * fetch the minimal necessary code and stay as close as possible to the Symfony
 * implementation to have maximum interchangeability at a later point.
 */
class Router implements SingletonInterface {

	/**
	 * All routes used in the Backend
	 *
	 * @var array|null
	 */
	protected $routes = array();

	/**
	 * Available options
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * @var PackageManager
	 */
	protected $packageManager = NULL;

	/**
	 * @param PackageManager $packageManager
	 */
	public function __construct(PackageManager $packageManager) {
		$this->packageManager = $packageManager;
		$this->loadFromPackages();
	}

	/**
	 * Loads all routes registered inside all packages
	 * In the future, the Route objects could be stored directly in the Cache
	 */
	protected function loadFromPackages() {
		// See if the Routes.php from the Packages have been built together already
		$cacheIdentifier = 'BackendRoutesFromPackages' . sha1((TYPO3_version . PATH_site . 'BackendRoutesFromPackages'));

		/** @var $codeCache \TYPO3\CMS\Core\Cache\Frontend\PhpFrontend */
		$codeCache = $this->getCacheManager()->getCache('cache_core');
		$routesFromPackages = array();
		if ($codeCache->has($cacheIdentifier)) {
			// substr is necessary, because the php frontend wraps php code around the cache value
			$routesFromPackages = unserialize(substr($codeCache->get($cacheIdentifier), 6, -2));
		} else {

			// Loop over all packages and check for a Configuration/Backend/Routes.php file
			$packages = $this->packageManager->getActivePackages();
			foreach ($packages as $package) {
				$routesFileNameForPackage = $package->getConfigurationPath() . 'Backend/Routes.php';
				if (file_exists($routesFileNameForPackage)) {
					$definedRoutesInPackage = require $routesFileNameForPackage;
					if (is_array($definedRoutesInPackage)) {
						$routesFromPackages += $definedRoutesInPackage;
					}
				}
			}
			// Store the data from all packages in the cache
			$codeCache->set($cacheIdentifier, serialize($routesFromPackages));
		}

		// Build Route objects from the data
		foreach ($routesFromPackages as $name => $options) {
			$path = $options['path'];
			unset($options['path']);
			$route = GeneralUtility::makeInstance(Route::class, $path, $options);
			$this->routes[$name] = $route;
		}
	}

	/**
	 * Sets multiple options for this router at once
	 *
	 * @param array $options An array of options
	 */
	public function setOptions(array $options) {
		$this->options = $options;
	}

	/**
	 * Sets a single option
	 *
	 * @param string $key The key
	 * @param mixed $value The value
	 */
	public function setOption($key, $value) {
		$this->options[$key] = $value;
	}

	/**
	 * Gets the value of an option
	 *
	 * @param string $key The key
	 * @return mixed The value
	 */
	public function getOption($key) {
		return $this->options[$key];
	}

	/**
	 * Generates a URL or path for a specific route based on the given parameters.
	 * This call needs to be publically accessable in the future for doing routing through the Router.
	 * @see Generator/UrlGenerator->generate for more details
	 *
	 * @param string $name The name of the route
	 * @param array $parameters An array of parameters
	 * @param bool|string $referenceType The type of URL to be generated
	 * @return string The generated URL
	 * @throws RouteNotFoundException If the named route doesn't exist
	 * @api
	 */
	public function generate($name, $parameters = array(), $referenceType = UrlGenerator::ABSOLUTE_PATH) {
		$urlGenerator = GeneralUtility::makeInstance(UrlGenerator::class, $this->routes);
		return $urlGenerator->generate($name, $parameters, $referenceType);
	}

	/**
	 * Tries to match a URL path with a set of routes.
	 * Should go into its own Matcher class later on
	 *
	 * @param string $pathInfo The path info to be parsed
	 * @return Route the first Route object found
	 * @throws ResourceNotFoundException If the resource could not be found
	 * @api
	 */
	public function match($pathInfo) {
		foreach ($this->routes as $routeName => $route) {
			// This check is done in a simple way as there are no parameters yet (get parameters only)
			if ($route->getPath() === $pathInfo) {
				// Store the name of the Route in the _identifier option so the token can be checked against that
				$route->setOption('_identifier', $routeName);
				return $route;
			}
		}
		throw new ResourceNotFoundException('The requested resource "' . htmlspecialchars($pathInfo) . '" was not found.', 1425389240);
	}

	/**
	 * Create and returns an instance of the CacheManager
	 *
	 * @return CacheManager
	 */
	protected function getCacheManager() {
		return GeneralUtility::makeInstance(CacheManager::class);
	}
}
