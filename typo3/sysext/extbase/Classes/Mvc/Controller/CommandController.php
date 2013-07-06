<?php
namespace TYPO3\CMS\Extbase\Mvc\Controller;

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
 * A controller which processes requests from the command line
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class CommandController implements \TYPO3\CMS\Extbase\Mvc\Controller\CommandControllerInterface {

	const MAXIMUM_LINE_LENGTH = 79;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\Request
	 */
	protected $request;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\Response
	 */
	protected $response;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Controller\Arguments
	 */
	protected $arguments;

	/**
	 * Name of the command method
	 *
	 * @var string
	 */
	protected $commandMethodName = '';

	/**
	 * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
	 */
	protected $reflectionService;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
	 * @return void
	 */
	public function injectReflectionService(\TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService) {
		$this->reflectionService = $reflectionService;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
		$this->arguments = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Controller\\Arguments');
	}

	/**
	 * Checks if the current request type is supported by the controller.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The current request
	 * @return boolean TRUE if this request type is supported, otherwise FALSE
	 */
	public function canProcessRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request) {
		return $request instanceof \TYPO3\CMS\Extbase\Mvc\Cli\Request;
	}

	/**
	 * Processes a command line request.
	 *
	 * @param \TYPO3\CMS\Extbase\Mvc\RequestInterface $request The request object
	 * @param \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response The response, modified by this controller
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException
	 * @return void
	 * @api
	 */
	public function processRequest(\TYPO3\CMS\Extbase\Mvc\RequestInterface $request, \TYPO3\CMS\Extbase\Mvc\ResponseInterface $response) {
		if (!$this->canProcessRequest($request)) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\UnsupportedRequestTypeException(get_class($this) . ' does not support requests of type "' . get_class($request) . '".', 1300787096);
		}
		$this->request = $request;
		$this->request->setDispatched(TRUE);
		$this->response = $response;
		$this->commandMethodName = $this->resolveCommandMethodName();
		$this->initializeCommandMethodArguments();
		$this->mapRequestArgumentsToControllerArguments();
		$this->callCommandMethod();
	}

	/**
	 * Resolves and checks the current command method name
	 *
	 * Note: The resulting command method name might not have the correct case, which isn't a problem because PHP is
	 * case insensitive regarding method names.
	 *
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchCommandException
	 * @return string Method name of the current command
	 */
	protected function resolveCommandMethodName() {
		$commandMethodName = $this->request->getControllerCommandName() . 'Command';
		if (!is_callable(array($this, $commandMethodName))) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchCommandException('A command method "' . $commandMethodName . '()" does not exist in controller "' . get_class($this) . '".', 1300902143);
		}
		return $commandMethodName;
	}

	/**
	 * Initializes the arguments array of this controller by creating an empty argument object for each of the
	 * method arguments found in the designated command method.
	 *
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException
	 * @return void
	 */
	protected function initializeCommandMethodArguments() {
		$methodParameters = $this->reflectionService->getMethodParameters(get_class($this), $this->commandMethodName);
		foreach ($methodParameters as $parameterName => $parameterInfo) {
			$dataType = NULL;
			if (isset($parameterInfo['type'])) {
				$dataType = $parameterInfo['type'];
			} elseif ($parameterInfo['array']) {
				$dataType = 'array';
			}
			if ($dataType === NULL) {
				throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentTypeException('The argument type for parameter $' . $parameterName . ' of method ' . get_class($this) . '->' . $this->commandMethodName . '() could not be detected.', 1306755296);
			}
			$defaultValue = isset($parameterInfo['defaultValue']) ? $parameterInfo['defaultValue'] : NULL;
			$this->arguments->addNewArgument($parameterName, $dataType, $parameterInfo['optional'] === FALSE, $defaultValue);
		}
	}

	/**
	 * Maps arguments delivered by the request object to the local controller arguments.
	 *
	 * @return void
	 */
	protected function mapRequestArgumentsToControllerArguments() {
		foreach ($this->arguments as $argument) {
			$argumentName = $argument->getName();
			if ($this->request->hasArgument($argumentName)) {
				$argument->setValue($this->request->getArgument($argumentName));
			} elseif ($argument->isRequired()) {
				$exception = new \TYPO3\CMS\Extbase\Mvc\Exception\CommandException('Required argument "' . $argumentName . '" is not set.', 1306755520);
				$this->forward('error', 'TYPO3\\CMS\\Extbase\\Command\\HelpCommandController', array('exception' => $exception));
			}
		}
	}

	/**
	 * Forwards the request to another command and / or CommandController.
	 *
	 * Request is directly transferred to the other command / controller
	 * without the need for a new request.
	 *
	 * @param string $commandName
	 * @param string $controllerObjectName
	 * @param array $arguments
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
	 * @return void
	 */
	protected function forward($commandName, $controllerObjectName = NULL, array $arguments = array()) {
		$this->request->setDispatched(FALSE);
		$this->request->setControllerCommandName($commandName);
		if ($controllerObjectName !== NULL) {
			$this->request->setControllerObjectName($controllerObjectName);
		}
		$this->request->setArguments($arguments);
		$this->arguments->removeAll();
		throw new \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException();
	}

	/**
	 * Calls the specified command method and passes the arguments.
	 *
	 * If the command returns a string, it is appended to the content in the
	 * response object. If the command doesn't return anything and a valid
	 * view exists, the view is rendered automatically.
	 *
	 * @return void
	 */
	protected function callCommandMethod() {
		$preparedArguments = array();
		foreach ($this->arguments as $argument) {
			$preparedArguments[] = $argument->getValue();
		}
		$commandResult = call_user_func_array(array($this, $this->commandMethodName), $preparedArguments);
		if (is_string($commandResult) && strlen($commandResult) > 0) {
			$this->response->appendContent($commandResult);
		} elseif (is_object($commandResult) && method_exists($commandResult, '__toString')) {
			$this->response->appendContent((string) $commandResult);
		}
	}

	/**
	 * Outputs specified text to the console window
	 * You can specify arguments that will be passed to the text via sprintf
	 *
	 * @see http://www.php.net/sprintf
	 * @param string $text Text to output
	 * @param array $arguments Optional arguments to use for sprintf
	 * @return void
	 */
	protected function output($text, array $arguments = array()) {
		if ($arguments !== array()) {
			$text = vsprintf($text, $arguments);
		}
		$this->response->appendContent($text);
	}

	/**
	 * Outputs specified text to the console window and appends a line break
	 *
	 * @param string $text Text to output
	 * @param array $arguments Optional arguments to use for sprintf
	 * @return string
	 * @see output()
	 */
	protected function outputLine($text = '', array $arguments = array()) {
		return $this->output($text . PHP_EOL, $arguments);
	}

	/**
	 * Exits the CLI through the dispatcher
	 * An exit status code can be specified @see http://www.php.net/exit
	 *
	 * @param integer $exitCode Exit code to return on exit
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
	 * @return void
	 */
	protected function quit($exitCode = 0) {
		$this->response->setExitCode($exitCode);
		throw new \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException();
	}

	/**
	 * Sends the response and exits the CLI without any further code execution
	 * Should be used for commands that flush code caches.
	 *
	 * @param integer $exitCode Exit code to return on exit
	 * @return void
	 */
	protected function sendAndExit($exitCode = 0) {
		$this->response->send();
		die($exitCode);
	}
}

?>