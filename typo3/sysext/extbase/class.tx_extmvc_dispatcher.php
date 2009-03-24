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
 * Creates a request an dispatches it to the controller which was specified by TS Setup, Flexform,
 * or Extension Configuration (ExtConf), and returns the content to the v4 framework.
 *
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
 */
class TX_EXTMVC_Dispatcher {

	/**
	 * @var TX_EXTMVC_Configuration_Manager A reference to the configuration manager
	 */
	protected $configurationManager;

	/**
	 * @var TX_EXTMVC_Web_RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var ArrayObject The raw GET parameters
	 */
	protected $getParameters;

	/**
	 * @var ArrayObject The raw POST parameters
	 */
	protected $postParameters;

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
	 * @uses t3lib_div::_GET()
	 * @uses t3lib_div::makeInstance()
	 * @uses t3lib_div::GParrayMerged()
	 * @uses t3lib_div::getIndpEnv()
	 * @return String $content The processed content
	 */
	public function dispatch($content, $configuration) {
		$parameters = t3lib_div::_GET();
		$extensionKey = $configuration['extension'];
		$controllerName = $configuration['controller'];
		$allowedActions = t3lib_div::trimExplode(',', $configuration['allowedActions']);
		if (isset($parameters['action']) && in_array($parameters['action'], $allowedActions)) {
			$actionName = stripslashes($parameters['action']);
		} else {
			$actionName = $configuration['action'];
		}
		if (empty($extensionKey) || empty($controllerName) || empty($allowedActions)) {
			throw new Exception('Could not dispatch the request. Please configure your plugin in the TS Setup.', 1237879677);
		}

		$request = $this->buildRequest($extensionKey, $controllerName, $actionName);
		$controller = t3lib_div::makeInstance($request->getControllerObjectName());
		if (!$controller instanceof TX_EXTMVC_Controller_AbstractController) throw new TX_EXTMVC_Exception_InvalidController('Invalid controller "' . $request->getControllerObjectName() . '". The controller must be a valid request handling controller.', 1202921619);

		if (!$controller->isCachableAction($actionName) && $this->cObj->getUserObjectType() === tslib_cObj::OBJECTTYPE_USER) {
			// FIXME Caching does not work because it's by default a USER object, so the dispatcher is never called
			// SK: does caching already work?
			$this->cObj->convertToUserIntObject();
			return $content;
		}

		$arguments = t3lib_div::makeInstance('TX_EXTMVC_Controller_Arguments');
		// SK: strtolower($extensionKey) is wrong I think, as all underscores need to be removed as well.
		// SK: Example: tt_news -> tx_ttnews
		foreach (t3lib_div::GParrayMerged('tx_' . strtolower($extensionKey)) as $key => $value) {
			$request->setArgument($key, $value);
		}

		$response = t3lib_div::makeInstance('TX_EXTMVC_Web_Response');
		$controller->injectSettings($this->getSettings($extensionKey));

		$session = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Session');
		try {
			$controller->processRequest($request, $response);
		} catch (TX_EXTMVC_Exception_StopAction $ignoredException) {
		}
		// debug($session);
		$session->commit();
		$session->clear();
		
		if (count($response->getAdditionalHeaderData()) > 0) {
			$GLOBALS['TSFE']->additionalHeaderData[$request->getControllerExtensionKey()] = implode("\n", $response->getAdditionalHeaderData());
		}
		// TODO Handle $response->getStatus()
		// SK: Call sendHeaders() on the response
		// JR: I don't think we need this, because the header will be sent by TYPO3
		return $response->getContent();
	}
	
	protected function getSettings($extensionKey) {
		$configurationSources = array();
		$configurationSources[] = t3lib_div::makeInstance('TX_EXTMVC_Configuration_Source_TS');
		if (!empty($this->cObj->data['pi_flexform'])) {
			$configurationSource = t3lib_div::makeInstance('TX_EXTMVC_Configuration_Source_FlexForm');
			$configurationSource->setFlexFormContent($this->cObj->data['pi_flexform']);
			$configurationSources[] = $configurationSource;
		}
		$configurationManager = t3lib_div::makeInstance('TX_EXTMVC_Configuration_Manager', $configurationSources);
		$configurationManager->loadGlobalSettings($extensionKey);
		return $configurationManager->getSettings($extensionKey);
	}
	
	protected function buildRequest($extensionKey, $controllerName, $actionName) {
		$request = t3lib_div::makeInstance('TX_EXTMVC_Web_Request');
		$request->setControllerExtensionKey($extensionKey);
		$request->setControllerName($controllerName);
		$request->setControllerActionName($actionName);
		$request->setRequestURI(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'));
		$request->setBaseURI(t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
		return $request;
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
	protected function autoLoadClasses($className) {
		if (empty($this->registeredClassNames[$className])) {
			$classNameParts = explode('_', $className);
			if ($classNameParts[0] === 'ux') {
				array_shift($classNameParts);
			}
			if (count($classNameParts) > 2 && $classNameParts[0] === 'TX') {
				$classFilePathAndName = t3lib_extMgm::extPath(strtolower($classNameParts[1])) . 'Classes/';
				$classFilePathAndName .= implode(array_slice($classNameParts, 2, -1), '/') . '/';
				$classFilePathAndName .= implode('_', $classNameParts) . '.php';
			}
			if (isset($classFilePathAndName) && file_exists($classFilePathAndName)) {
				require_once($classFilePathAndName);
				$this->registeredClassNames[$className] = $classFilePathAndName;
			}
		}
	}

}
?>