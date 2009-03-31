<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
*  All rights reserved
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
 * Builds a web request.
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 *
 * @scope prototype
 */
class Tx_ExtBase_MVC_Web_RequestBuilder {

	/**
	 * Builds a web request object from the raw HTTP information
	 *
	 * @return \F3\FLOW3\MVC\Web\Request The web request as an object
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function build($configuration) {
		$pluginKey = $configuration['pluginKey'];
		$extensionName = ($configuration['extensionName'] !== NULL) ? $configuration['extensionName'] : 'ExtBase';
		$controllerConfigurations = is_array($configuration['controllers.']) ? $configuration['controllers.'] : array();
		$defaultControllerConfiguration = current($controllerConfigurations);
		$defaultControllerName = ($defaultControllerConfiguration['controllerName'] !== NULL) ? $defaultControllerConfiguration['controllerName'] : 'Default';
		$defaultControllerActions = t3lib_div::trimExplode(',', $defaultControllerConfiguration['actions']);
		$defaultActionName = (!empty($defaultControllerActions[0])) ? $defaultControllerActions[0] : 'index';
		$allowedControllerActions = array();
		foreach ($controllerConfigurations as $controllerConfiguration) {
			$controllerActions = t3lib_div::trimExplode(',', $controllerConfiguration['actions']);
			foreach ($controllerActions as $actionName) {
				$allowedControllerActions[$controllerConfiguration['controllerName']][] = $actionName;
			}
		}
		$parameters = t3lib_div::_GET('tx_' . strtolower($extensionName) . '_' . strtolower($pluginKey)); // TODO Parameters are unvalidated!
		if (is_string($parameters['controller']) && array_key_exists($parameters['controller'], $allowedControllerActions)) {
			$controllerName = stripslashes($parameters['controller']);
		} elseif ($defaultControllerConfiguration['controllerName'] !== NULL) {
			$controllerName = $defaultControllerName;
		}

		$allowedActions = $allowedControllerActions[$controllerName];

		if (is_string($parameters['action']) && is_array($allowedActions) && in_array($parameters['action'], $allowedActions)) {
			$actionName = filter_var($parameters['action'], FILTER_SANITIZE_STRING);
		} elseif (is_string($defaultControllerConfiguration['actions'])) {;
			$actions = t3lib_div::trimExplode(',', $defaultControllerConfiguration['actions']);
			$actionName = $actions[0];
		}

		$request = t3lib_div::makeInstance('Tx_ExtBase_MVC_Web_Request');
		$request->setPluginKey($pluginKey);
		$request->setExtensionName($extensionName);
		$request->setControllerName($controllerName);
		$request->setControllerActionName($actionName);
		$request->setRequestURI(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'));
		$request->setBaseURI(t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
		foreach (t3lib_div::GParrayMerged('tx_' . strtolower($extensionName) . '_' . strtolower($pluginKey)) as $key => $value) {
			$request->setArgument($key, $value);
		}
		return $request;
	}


}
?>