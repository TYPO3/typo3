<?php

/*
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Widget request handler, which handles the request if
 * f3-fluid-widget-id is found.
 *
 * This Request Handler gets the WidgetRequestBuilder injected.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Fluid_Core_Widget_WidgetRequestHandler extends Tx_Extbase_MVC_Web_AbstractRequestHandler {

	/**
	 * @var Tx_Fluid_Core_Widget_AjaxWidgetContextHolder
	 */
	protected $ajaxWidgetContextHolder;

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
	 * @param Tx_Fluid_Core_Widget_AjaxWidgetContextHolder $ajaxWidgetContextHolder
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function injectAjaxWidgetContextHolder(Tx_Fluid_Core_Widget_AjaxWidgetContextHolder $ajaxWidgetContextHolder) {
		$this->ajaxWidgetContextHolder = $ajaxWidgetContextHolder;
	}

	/**
	 * Injects the request handler
	 *
	 * @param Tx_Fluid_Core_Widget_WidgetRequestBuilder $requestBuilder
	 * @return void
	 */
	public function injectRequestBuilder(Tx_Fluid_Core_Widget_WidgetRequestBuilder $requestBuilder) {
		$this->requestBuilder = $requestBuilder;
	}

	/**
	 * Handles the web request. The response will automatically be sent to the client.
	 *
	 * @return Tx_Extbase_MVC_Web_Response
	 */
	public function handleRequest() {
		$request = $this->requestBuilder->build();
		if (isset($this->cObj->data) && is_array($this->cObj->data)) {
			$request->setContentObjectData($this->cObj->data);
		}
		$response = $this->objectManager->create('Tx_Extbase_MVC_Web_Response');

		$this->dispatcher->dispatch($request, $response);
		return $response;
	}

	/**
	 * @return boolean TRUE if it is an AJAX widget request
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function canHandleRequest() {
		$rawGetArguments = t3lib_div::_GET();
		return isset($rawGetArguments['fluid-widget-id']);
	}

	/**
	 * This request handler has a higher priority than the default request handler.
	 *
	 * @return integer
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getPriority() {
		return 200;
	}
}

?>