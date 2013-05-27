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
 * Represents a CLI request.
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @api
 */
class Request implements \TYPO3\CMS\Extbase\Mvc\RequestInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var string
	 */
	protected $controllerObjectName;

	/**
	 * @var string
	 */
	protected $controllerCommandName = 'default';

	/**
	 * @var string Name of the extension which is supposed to handle this request.
	 */
	protected $controllerExtensionName = NULL;

	/**
	 * The arguments for this request
	 *
	 * @var array
	 */
	protected $arguments = array();

	/**
	 * @var array
	 */
	protected $exceedingArguments = array();

	/**
	 * If this request has been changed and needs to be dispatched again
	 *
	 * @var boolean
	 */
	protected $dispatched = FALSE;

	/**
	 * @var array
	 */
	protected $commandLineArguments;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\Command | NULL
	 */
	protected $command = NULL;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager A reference to the object manager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Sets the dispatched flag
	 *
	 * @param boolean $flag If this request has been dispatched
	 * @return void
	 */
	public function setDispatched($flag) {
		$this->dispatched = $flag ? TRUE : FALSE;
	}

	/**
	 * If this request has been dispatched and addressed by the responsible
	 * controller and the response is ready to be sent.
	 *
	 * The dispatcher will try to dispatch the request again if it has not been
	 * addressed yet.
	 *
	 * @return boolean TRUE if this request has been disptached successfully
	 */
	public function isDispatched() {
		return $this->dispatched;
	}

	/**
	 * Sets the object name of the controller
	 *
	 * @param string $controllerObjectName The fully qualified controller object name
	 * @return void
	 */
	public function setControllerObjectName($controllerObjectName) {
		$matches = array();
		preg_match('/
			^Tx
			_(?P<extensionName>[^_]+)
			_
			(
				Command
			|
				(?P<subpackageKey>.+)_Controller
			)
			_(?P<controllerName>[a-z_]+)Controller
			$/ix', $controllerObjectName, $matches);
		$this->controllerExtensionName = $matches['extensionName'];
		$this->controllerObjectName = $controllerObjectName;
		$this->command = NULL;
	}

	/**
	 * Returns the object name of the controller
	 *
	 * @return string The controller's object name
	 */
	public function getControllerObjectName() {
		return $this->controllerObjectName;
	}

	/**
	 * Returns the extension name of the specified controller.
	 *
	 * @return string The extension name
	 */
	public function getControllerExtensionName() {
		return $this->controllerExtensionName;
	}

	/**
	 * Sets the name of the command contained in this request.
	 *
	 * Note that the command name must start with a lower case letter and is case sensitive.
	 *
	 * @param string $commandName Name of the command to execute by the controller
	 * @return void
	 */
	public function setControllerCommandName($commandName) {
		$this->controllerCommandName = $commandName;
		$this->command = NULL;
	}

	/**
	 * Returns the name of the command the controller is supposed to execute.
	 *
	 * @return string Command name
	 */
	public function getControllerCommandName() {
		return $this->controllerCommandName;
	}

	/**
	 * Returns the command object for this request
	 *
	 * @return \TYPO3\CMS\Extbase\Mvc\Cli\Command
	 */
	public function getCommand() {
		if ($this->command === NULL) {
			$this->command = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Command', $this->controllerObjectName, $this->controllerCommandName);
		}
		return $this->command;
	}

	/**
	 * Sets the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument to set
	 * @param mixed $value The new value
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException
	 * @return void
	 */
	public function setArgument($argumentName, $value) {
		if (!is_string($argumentName) || $argumentName === '') {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\InvalidArgumentNameException('Invalid argument name.', 1300893885);
		}
		$this->arguments[$argumentName] = $value;
	}

	/**
	 * Sets the whole arguments ArrayObject and therefore replaces any arguments
	 * which existed before.
	 *
	 * @param array $arguments An array of argument names and their values
	 * @return void
	 */
	public function setArguments(array $arguments) {
		$this->arguments = $arguments;
	}

	/**
	 * Returns the value of the specified argument
	 *
	 * @param string $argumentName Name of the argument
	 * @return string Value of the argument
	 * @throws \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException if such an argument does not exist
	 */
	public function getArgument($argumentName) {
		if (!isset($this->arguments[$argumentName])) {
			throw new \TYPO3\CMS\Extbase\Mvc\Exception\NoSuchArgumentException('An argument "' . $argumentName . '" does not exist for this request.', 1300893886);
		}
		return $this->arguments[$argumentName];
	}

	/**
	 * Checks if an argument of the given name exists (is set)
	 *
	 * @param string $argumentName Name of the argument to check
	 * @return boolean TRUE if the argument is set, otherwise FALSE
	 */
	public function hasArgument($argumentName) {
		return isset($this->arguments[$argumentName]);
	}

	/**
	 * Returns an ArrayObject of arguments and their values
	 *
	 * @return array Array of arguments and their values (which may be arguments and values as well)
	 */
	public function getArguments() {
		return $this->arguments;
	}

	/**
	 * Sets the exceeding arguments
	 *
	 * @param array $exceedingArguments Numeric array of exceeding arguments
	 * @return void
	 */
	public function setExceedingArguments(array $exceedingArguments) {
		$this->exceedingArguments = $exceedingArguments;
	}

	/**
	 * Returns additional unnamed arguments (if any) which have been passed through the command line after all
	 * required arguments (if any) have been specified.
	 *
	 * For a command method with the signature ($argument1, $argument2) and for the command line
	 * cli_dispatch.phpsh extbase some-key someaction acme:foo --argument1 Foo --argument2 Bar baz quux
	 * this method would return array(0 => 'baz', 1 => 'quux')
	 *
	 * @return array Numeric array of exceeding argument values
	 */
	public function getExceedingArguments() {
		return $this->exceedingArguments;
	}
}

?>