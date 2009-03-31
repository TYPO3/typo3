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
 * Creates a request an dispatches it to the controller which was specified
 * by TS Setup, Flexform and returns the content to the v4 framework.
 *
 * @package TYPO3
 * @subpackage extbase
 * @version $ID:$
 */
class Tx_ExtBase_Dispatcher {

	/**
	 * @var array An array of registered classes (class files with path)
	 */
	protected $registeredClassNames;

	/**
	 * Constructs this dispatcher
	 *
	 */
	public function __construct() {
		spl_autoload_register(array($this, 'autoLoadClasses'));
	}

	/**
	 * Creates a request an dispatches it to a controller.
	 *
	 * @param String $content The content
	 * @param array|NULL $configuration The TS configuration array
	 * @return String $content The processed content
	 */
	public function dispatch($content, $configuration) {
		if (!is_array($configuration)) {
			throw new Exception('Could not dispatch the request. Please configure your plugin in the TS Setup.', 1237879677);
		}
		$requestBuilder = t3lib_div::makeInstance('Tx_ExtBase_MVC_Web_RequestBuilder');
		$request = $requestBuilder->build($configuration);
		$response = t3lib_div::makeInstance('Tx_ExtBase_MVC_Web_Response');
		$controller = $this->getPreparedController($request);
		$persistenceSession = t3lib_div::makeInstance('Tx_ExtBase_Persistence_Session');
		try {
			$controller->processRequest($request, $response);
		} catch (Tx_ExtBase_Exception_StopAction $ignoredException) {
		}
		// var_dump($persistenceSession);
		$persistenceSession->commit();
		$persistenceSession->clear();
		if (count($response->getAdditionalHeaderData()) > 0) {
			$GLOBALS['TSFE']->additionalHeaderData[$request->getExtensionName()] = implode("\n", $response->getAdditionalHeaderData());
		}
		// TODO Handle $response->getStatus()
		$response->sendHeaders();
		return $response->getContent();
	}

	/**
	 * Builds and returns a controller
	 *
	 * @param Tx_ExtBase_MVC_Web_Request $request
	 * @return Tx_ExtBase_MVC_Controller_ControllerInterface The prepared controller
	 */
	protected function getPreparedController(Tx_ExtBase_MVC_Web_Request $request) {
		$controllerObjectName = $request->getControllerObjectName();
		$controller = t3lib_div::makeInstance($controllerObjectName);
		if (!$controller instanceof Tx_ExtBase_MVC_Controller_ControllerInterface) {
			throw new Tx_ExtBase_Exception_InvalidController('Invalid controller "' . $request->getControllerObjectName() . '". The controller must implement the Tx_ExtBase_MVC_Controller_ControllerInterface.', 1202921619);
		}
		$controller->injectSettings($this->getSettings($request));
		return $controller;
	}

	/**
	 * Builds the settings by overlaying TS Setup with FlexForm values of the extension
	 * and returns them as a plain array (with no trailing dots).
	 *
	 * @param Tx_ExtBase_MVC_Web_Request $request
	 * @return array The settings array
	 */
	protected function getSettings(Tx_ExtBase_MVC_Web_Request $request) {
		$extensionName = $request->getExtensionName();
		$configurationSources = array();
		$configurationSources[] = t3lib_div::makeInstance('Tx_ExtBase_Configuration_Source_TypoScriptSource');
		if (!empty($this->cObj->data['pi_flexform'])) {
			$configurationSource = t3lib_div::makeInstance('Tx_ExtBase_Configuration_Source_FlexFormSource');
			$configurationSource->setFlexFormContent($this->cObj->data['pi_flexform']);
			$configurationSources[] = $configurationSource;
		}
		$configurationManager = t3lib_div::makeInstance('Tx_ExtBase_Configuration_Manager', $configurationSources);
		$configurationManager->loadGlobalSettings($extensionName);
		return $configurationManager->getSettings($extensionName);
	}

	/**
	 * Loads php files containing classes or interfaces found in the classes directory of
	 * an extension.
	 *
	 * @param string $className: Name of the class/interface to load
	 * @uses t3lib_extMgm::extPath()
	 * @return void
	 */
	// TODO Remove autoloader as soon as we do not need it anymore
	public function autoLoadClasses($className) {
		if (empty($this->registeredClassNames[$className])) {
			$classNameParts = explode('_', $className);
			if ($classNameParts[0] === 'ux') {
				array_shift($classNameParts);
			}
			if (count($classNameParts) > 2 && $classNameParts[0] === 'Tx') {
				$classFilePathAndName = t3lib_extMgm::extPath(strtolower($classNameParts[1])) . 'Classes/';
				$classFilePathAndName .= implode(array_slice($classNameParts, 2, -1), '/') . '/';
				$classFilePathAndName .= array_pop($classNameParts) . '.php';
			}
			if (isset($classFilePathAndName) && file_exists($classFilePathAndName)) {
				require_once($classFilePathAndName);
				$this->registeredClassNames[$className] = $classFilePathAndName;
			}
		}
	}

}
?>