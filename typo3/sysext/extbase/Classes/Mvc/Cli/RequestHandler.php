<?php
namespace TYPO3\CMS\Extbase\Mvc\Cli;

/**
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
/**
 * The generic command line interface request handler for the MVC framework.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RequestHandler implements \TYPO3\CMS\Extbase\Mvc\RequestHandlerInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Dispatcher
	 * @inject
	 */
	protected $dispatcher;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\RequestBuilder
	 * @inject
	 */
	protected $requestBuilder;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
	 * @inject
	 */
	protected $environmentService;

	/**
	 * Handles the request
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\ResponseInterface
	 */
	public function handleRequest() {
		$commandLine = isset($_SERVER['argv']) ? $_SERVER['argv'] : array();
		$callingScript = array_shift($commandLine);
		if ($callingScript !== $_SERVER['_']) {
			$callingScript = $_SERVER['_'] . ' ' . $callingScript;
		}
		$request = $this->requestBuilder->build($commandLine, $callingScript . ' extbase');
		/** @var $response \TYPO3\CMS\Extbase\Mvc\Cli\Response */
		$response = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Response');
		$this->dispatcher->dispatch($request, $response);
		$response->send();
		return $response;
	}

	/**
	 * This request handler can handle any command line request.
	 *
	 * @return boolean If the request is a command line request, TRUE otherwise FALSE
	 */
	public function canHandleRequest() {
		return $this->environmentService->isEnvironmentInCliMode();
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
