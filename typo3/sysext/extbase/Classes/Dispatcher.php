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
 * @package Extbase
 * @subpackage extbase
 * @version $ID:$
 */
class Tx_Extbase_Dispatcher {

	/**
	 * Creates a request an dispatches it to a controller.
	 *
	 * @param string $content The content
	 * @param array|NULL $configuration The TS configuration array
	 * @return string $content The processed content
	 */
	public function dispatch($content, $configuration) {
		if (!is_array($configuration)) {
			t3lib_div::sysLog('Extbase was not able to dispatch the request. No configuration.', 'extbase', t3lib_div::SYSLOG_SEVERITY_ERROR);
			return $content;
		}
		$requestBuilder = t3lib_div::makeInstance('Tx_Extbase_MVC_Web_RequestBuilder');
		$request = $requestBuilder->initialize($configuration);
		$request = $requestBuilder->build();
		$response = t3lib_div::makeInstance('Tx_Extbase_MVC_Web_Response');
		$persistenceSession = t3lib_div::makeInstance('Tx_Extbase_Persistence_Session'); // singleton

		$dispatchLoopCount = 0;
		while (!$request->isDispatched()) {
			if ($dispatchLoopCount++ > 99) throw new TxExtbase_MVC_Exception_InfiniteLoop('Could not ultimately dispatch the request after '  . $dispatchLoopCount . ' iterations.', 1217839467);
			$controller = $this->getPreparedController($request);
			try {
				$controller->processRequest($request, $response);
			} catch (Tx_Extbase_Exception_StopAction $ignoredException) {
			} catch (Tx_Extbase_Exception_InvalidArgumentValue $exception) {
				$persistenceSession->clear();
				return '';
			}
		}

		$persistenceSession->commit();
		$persistenceSession->clear();
		if (count($response->getAdditionalHeaderData()) > 0) {
			$GLOBALS['TSFE']->additionalHeaderData[$request->getControllerExtensionName()] = implode("\n", $response->getAdditionalHeaderData());
		}
		$response->sendHeaders();
		return $response->getContent();
	}

	/**
	 * Builds and returns a controller
	 *
	 * @param Tx_Extbase_MVC_Web_Request $request
	 * @return Tx_Extbase_MVC_Controller_ControllerInterface The prepared controller
	 */
	protected function getPreparedController(Tx_Extbase_MVC_Web_Request $request) {
		$controllerObjectName = $request->getControllerObjectName();
		$controller = t3lib_div::makeInstance($controllerObjectName);
		if (!$controller instanceof Tx_Extbase_MVC_Controller_ControllerInterface) {
			throw new Tx_Extbase_Exception_InvalidController('Invalid controller "' . $request->getControllerObjectName() . '". The controller must implement the Tx_Extbase_MVC_Controller_ControllerInterface.', 1202921619);
		}
		$propertyMapper = t3lib_div::makeInstance('Tx_Extbase_Property_Mapper');
		$controller->injectPropertyMapper($propertyMapper);
		$controller->injectSettings($this->getSettings($request));
		$URIHelper = t3lib_div::makeInstance('Tx_Extbase_MVC_View_Helper_URIHelper');
		$URIHelper->setRequest($request);
		$controller->injectURIHelper($URIHelper);
		$reflectionService = t3lib_div::makeInstance('Tx_Extbase_Reflection_Service');
		$validatorResolver = t3lib_div::makeInstance('Tx_Extbase_Validation_ValidatorResolver');
		$validatorResolver->injectReflectionService($reflectionService);
		$controller->injectValidatorResolver($validatorResolver);
		$controller->injectReflectionService($reflectionService);
		return $controller;
	}

	/**
	 * Builds the settings by overlaying TS Setup with FlexForm values of the extension
	 * and returns them as a plain array (with no trailing dots).
	 *
	 * @param Tx_Extbase_MVC_Web_Request $request
	 * @return array The settings array
	 */
	protected function getSettings(Tx_Extbase_MVC_Web_Request $request) {
		$extensionName = $request->getControllerExtensionName();
		$configurationSources = array();
		$configurationSources[] = t3lib_div::makeInstance('Tx_Extbase_Configuration_Source_TypoScriptSource');
		if (!empty($this->cObj->data['pi_flexform'])) {
			$configurationSource = t3lib_div::makeInstance('Tx_Extbase_Configuration_Source_FlexFormSource');
			$configurationSource->setFlexFormContent($this->cObj->data['pi_flexform']);
			$configurationSources[] = $configurationSource;
		}
		$configurationManager = t3lib_div::makeInstance('Tx_Extbase_Configuration_Manager', $configurationSources);
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
	public static function autoloadClass($className) {
		$classNameParts = explode('_', $className);
		$extensionKey = t3lib_div::camelCaseToLowerCaseUnderscored($classNameParts[1]);
		if (t3lib_extMgm::isLoaded($extensionKey)) {
			if ($classNameParts[0] === 'ux') {
				array_shift($classNameParts);
			}
			$className = implode('_', $classNameParts);
			if (count($classNameParts) > 2 && $classNameParts[0] === 'Tx') {
				$classFilePathAndName = t3lib_extMgm::extPath(t3lib_div::camelCaseToLowerCaseUnderscored($classNameParts[1])) . 'Classes/';
				$classFilePathAndName .= implode(array_slice($classNameParts, 2, -1), '/') . '/';
				$classFilePathAndName .= array_pop($classNameParts) . '.php';
			}
			if (isset($classFilePathAndName) && file_exists($classFilePathAndName)) {
				require_once($classFilePathAndName);
			}
		}
	}

}
?>