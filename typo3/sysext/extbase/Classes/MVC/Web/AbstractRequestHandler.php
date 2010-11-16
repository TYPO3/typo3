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
 * A request handler which can handle web requests.
 *
 */
abstract class Tx_Extbase_MVC_Web_AbstractRequestHandler implements Tx_Extbase_MVC_RequestHandlerInterface {

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var Tx_Extbase_MVC_Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var Tx_Extbase_MVC_Web_RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var Tx_Extbase_MVC_Controller_FlashMessages
	 */
	protected $flashMessages;

	/**
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectFlashMessages(Tx_Extbase_MVC_Controller_FlashMessages $flashMessages) {
		$this->flashMessages = $flashMessages;
	}

	/**
	 * @param Tx_Extbase_MVC_Dispatcher $dispatcher
	 * @return void
	 */
	public function injectDispatcher(Tx_Extbase_MVC_Dispatcher $dispatcher) {
		$this->dispatcher = $dispatcher;
	}

	/**
	 * @param Tx_Extbase_MVC_Web_RequestBuilder $requestBuilder
	 * @return void
	 */
	public function injectRequestBuilder(Tx_Extbase_MVC_Web_RequestBuilder $requestBuilder) {
		$this->requestBuilder = $requestBuilder;
	}

	/**
	 * This request handler can handle any web request.
	 *
	 * @return boolean If the request is a web request, TRUE otherwise FALSE
	 */
	public function canHandleRequest() {
		return TRUE;
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler.
	 */
	public function getPriority() {
		return 100;
	}

}
?>