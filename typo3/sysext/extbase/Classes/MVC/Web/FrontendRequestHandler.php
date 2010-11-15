<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Jochen Rau <jochen.rau@typoplanet.de>
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of FLOW3.
 *  All credits go to the v5 team.
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
 * A request handler which can handle web requests invoked by the frontend.
 *
 */
class Tx_Extbase_MVC_Web_FrontendRequestHandler extends Tx_Extbase_MVC_Web_AbstractRequestHandler {

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Handles the web request. The response will automatically be sent to the client.
	 *
	 * @return Tx_Extbase_MVC_Web_Response
	 */
	public function handleRequest() {
		$request = $this->requestBuilder->build();

		// Request hash service
		$requestHashService = $this->objectManager->get('Tx_Extbase_Security_Channel_RequestHashService'); // singleton
		$requestHashService->verifyRequest($request);

		if ($this->isCacheable($request->getControllerName(), $request->getControllerActionName())) {
			$request->setIsCached(TRUE);
		} else {
			$contentObject = $this->configurationManager->getContentObject();
			if ($contentObject->getUserObjectType() === tslib_cObj::OBJECTTYPE_USER) {
				$contentObject->convertToUserIntObject();
				// tslib_cObj::convertToUserIntObject() will recreate the object, so we have to stop the request here
				return;
			}
			$request->setIsCached(FALSE);
		}
		$response = $this->objectManager->create('Tx_Extbase_MVC_Web_Response');

		$this->dispatcher->dispatch($request, $response);

		return $response;
	}

	/**
	 * This request handler can handle any web request.
	 *
	 * @return boolean If the request is a web request, TRUE otherwise FALSE
	 */
	public function canHandleRequest() {
		return TYPO3_MODE === 'FE';
	}

	/**
	 * Determines whether the current action can be cached
	 *
	 * @param string $controllerName
	 * @param string $actionName
	 * @return boolean TRUE if the given action should be cached, otherwise FALSE
	 */
	protected function isCacheable($controllerName, $actionName) {
		$frameworkConfiguration = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
		if (isset($frameworkConfiguration['switchableControllerActions'][$controllerName]['nonCacheableActions'])
			&& in_array($actionName, t3lib_div::trimExplode(',', $frameworkConfiguration['switchableControllerActions'][$controllerName]['nonCacheableActions']))) {
				return FALSE;
			}
		return TRUE;
	}

}
?>