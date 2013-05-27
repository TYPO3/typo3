<?php
namespace TYPO3\CMS\Extbase\Mvc\Cli;

/***************************************************************
 *  Copyright notice
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
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
 * The generic command line interface request handler for the MVC framework.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestHandler implements \TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Dispatcher
	 */
	protected $dispatcher;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\RequestBuilder
	 */
	protected $requestBuilder;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\FlashMessageContainer
	 */
	protected $flashMessageContainer;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\Controller\FlashMessageContainer $flashMessageContainer
	 * @return void
	 */
	public function injectFlashMessageContainer(\TYPO3\CMS\Extbase\Mvc\Controller\FlashMessageContainer $flashMessageContainer) {
		$this->flashMessageContainer = $flashMessageContainer;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\Dispatcher $dispatcher
	 * @return void
	 */
	public function injectDispatcher(\TYPO3\CMS\Extbase\Mvc\Dispatcher $dispatcher) {
		$this->dispatcher = $dispatcher;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\Cli\RequestBuilder $requestBuilder
	 * @return void
	 */
	public function injectRequestBuilder(\TYPO3\CMS\Extbase\Mvc\Cli\RequestBuilder $requestBuilder) {
		$this->requestBuilder = $requestBuilder;
	}

	/**
	 * Handles the request
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function handleRequest() {
		$request = $this->requestBuilder->build();
		/** @var $response \TYPO3\CMS\Extbase\Mvc\Cli\Response */
		$response = $this->objectManager->create('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Response');
		$this->dispatcher->dispatch($request, $response);
		$response->send();
	}

	/**
	 * This request handler can handle any command line request.
	 *
	 * @return boolean If the request is a command line request, TRUE otherwise FALSE
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function canHandleRequest() {
		return PHP_SAPI === 'cli';
	}

	/**
	 * Returns the priority - how eager the handler is to actually handle the
	 * request.
	 *
	 * @return integer The priority of the request handler.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getPriority() {
		return 90;
	}
}

?>