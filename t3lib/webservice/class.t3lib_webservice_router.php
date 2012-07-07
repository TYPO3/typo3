<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Thomas Maroschik <tmaroschik@dfau.de>
 *		2012 Nicolas Forgerit <nicolas.forgerit@gmail.com>
 *  	All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * The router resolves the corresponding scripts from the request URI
 * 
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 * @author Nicolas Forgerit <nicolas.forgerit@gmail.com>
 */
class t3lib_webservice_router implements t3lib_Singleton {

	/**
	 * This array contains all the registered routes
	 */
	protected $routes = array();

	protected function resolveExtension($requestString) {
		$parts = explode('/', $requestString);
		if ($parts[1] === '') return $parts[4];
		else return $parts[2];
	}

	/**
	 * Walks through all configured routes and calls their respective resolves-method.
	 * When a matching route is found, the corresponding URI is returned.
	 *
	 * @param string $requestString
	 * @param array $routes
	 * @return array $resolvedRoute
	 * @throws InvalidArgumentException
	 */
	public function resolveRoute($requestString) {

		return array(
			'extensionName' => $this->resolveExtension($requestString),		
		);
	}

	/**
	 * Sets routes
	 *
	 * @param array $routes
	 */
	public function setRoutes(array $routes) {
		$this->routes = $routes;
		return $this;
	}

	/**
	 * Adds a route
	 *
	 * @param array $route
	 */
	public function addRoute($uriPattern, $webserviceClassName) {
		$this->routes[$uriPattern] = $webserviceClassName;
		return $this;
	}

	/**
	 * Removes a route
	 *
	 * @param string $uriPattern
	 */
	public function removeRoute($uriPattern) {
		unset($this->routes[$uriPattern]);
		return $this;
	}

	/**
	 * Returns routes
	 *
	 * @return array An array routes
	 */
	public function getRoutes() {
		return $this->routes;
	}

}

?>
