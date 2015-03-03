<?php
namespace TYPO3\CMS\Backend\Routing\Generator;

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
use TYPO3\CMS\Backend\Routing\RouteCollection;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Main UrlGenerator for creating URLs for the Backend. Generates a URL based on
 * an identifier defined by Configuration/Backend/Routes.php of an extension,
 * and adds some more parameters to the URL.
 *
 * Currently only available and useful when called from Router->generate() as the information
 * about possible routes needs to be handed over.
 *
 * The architecture is highly inspired by the Symfony Routing Component.
 * The general integration approach into the TYPO3 Backend is to only
 * fetch the minimal necessary code and stay as close as possible to the Symfony
 * implementation to have maximum interchangeability at a later point.
 */
class UrlGenerator {

	/**
	 * Generates an absolute URL
	 */
	const ABSOLUTE_URL = 'url';

	/**
	 * Generates an absolute path
	 */
	const ABSOLUTE_PATH = 'absolute';

	/**
	 * Generates a relative path
	 * It is discouraged to use this method, as $BACK_PATH is still needed currently
	 */
	const RELATIVE_PATH = 'relative';

	/**
	 * @var array
	 */
	protected $routes;

	/**
	 * Constructor which always receives the available routes
	 *
	 * @param array $routes
	 */
	public function __construct(array $routes) {
		$this->routes = $routes;
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
	 * @return string The generated URL
	 * @throws RouteNotFoundException If the named route doesn't exist
	 */
	public function generate($name, $parameters = array(), $referenceType = self::ABSOLUTE_PATH) {
		if (!isset($this->routes[$name])) {
			throw new RouteNotFoundException('Unable to generate a URL for the named route "' . $name . '" because this route was not found.');
		}
		$route = $this->routes[$name];
		$path = $route->getPath();

		// If the route has the "public" option set, no token is generated.
		if ($route->getOption('access') !== 'public') {
			$parameters = array(
				'token' => FormProtectionFactory::get()->generateToken('route', $name)
			) + $parameters;
		}
		// Build the base URL by adding the Route path as PATH_INFO. No prefix added yet, see below.
		$url = 'index.php' . $path;

		// Add parameters if there are any
		if (!empty($parameters)) {
			$url .= '?' . ltrim(GeneralUtility::implodeArrayForUrl('', $parameters, '', TRUE, TRUE), '&');
		}

		// Build the prefix for the URL
		$prefix = '';
		switch ($referenceType) {
			case self::RELATIVE_PATH:
				$prefix = $GLOBALS['BACK_PATH'];
				break;
			case self::ABSOLUTE_PATH:
				$prefix = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix(GeneralUtility::getIndpEnv('TYPO3_SITE_PATH') . PATH_typo3);
				break;
			case self::ABSOLUTE_URL:
				$prefix = GeneralUtility::getIndpEnv('TYPO3_REQUEST_DIR');
				break;
		}
		return $prefix . $url;
	}
}
