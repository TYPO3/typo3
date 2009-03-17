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

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Controller/TX_EXTMVC_Controller_Argument.php');

/**
 * A composite of controller arguments
 *
 * @package TYPO3
 * @subpackage extmvc
 * @version $ID:$
 * @scope prototype
 */
class TX_EXTMVC_Controller_Arguments extends ArrayObject {

	/**
	 * @var array Names of the arguments contained by this object
	 */
	protected $argumentNames = array();

	/**
	 * Adds or replaces the argument specified by $value. The argument's name is taken from the
	 * argument object itself, therefore the $offset does not have any meaning in this context.
	 *
	 * @param mixed $offset Offset - not used here
	 * @param mixed $value The argument
	 * @return void
	 * @throws InvalidArgumentException if the argument is not a valid Controller Argument object
	 */
	public function offsetSet($offset, $value) {
		if (!$value instanceof TX_EXTMVC_Controller_Argument) throw new InvalidArgumentException('Controller arguments must be valid TX_EXTMVC_Controller_Argument objects.', 1187953786);

		$argumentName = $value->getName();
		parent::offsetSet($argumentName, $value);
		$this->argumentNames[$argumentName] = TRUE;
	}

	/**
	 * Sets an argument, aliased to offsetSet()
	 *
	 * @param mixed $value The value
	 * @return void
	 * @throws InvalidArgumentException if the argument is not a valid Controller Argument object
	 */
	public function append($value) {
		if (!$value instanceof TX_EXTMVC_Controller_Argument) throw new InvalidArgumentException('Controller arguments must be valid TX_EXTMVC_Controller_Argument objects.', 1187953786);
		$this->offsetSet(NULL, $value);
	}

	/**
	 * Unsets an argument
	 *
	 * @param mixed $offset Offset
	 * @return void
	 */
	public function offsetUnset($offset) {
		$translatedOffset = $this->translateToLongArgumentName($offset);
		parent::offsetUnset($translatedOffset);

		unset($this->argumentNames[$translatedOffset]);
		if ($offset != $translatedOffset) {
			unset($this->argumentShortNames[$offset]);
		}
	}

	/**
	 * Returns whether the requested index exists
	 *
	 * @param mixed $offset Offset
	 * @return boolean
	 */
	public function offsetExists($offset) {
		$translatedOffset = $this->translateToLongArgumentName($offset);
		return parent::offsetExists($translatedOffset);
	}

	/**
	 * Returns the value at the specified index
	 *
	 * @param mixed $offset Offset
	 * @return TX_EXTMVC_Controller_Argument The requested argument object
	 * @throws TX_EXTMVC_Exception_NoSuchArgument if the argument does not exist
	 */
	public function offsetGet($offset) {
		$translatedOffset = $this->translateToLongArgumentName($offset);
		if ($translatedOffset === '') throw new TX_EXTMVC_Exception_NoSuchArgument('The argument "' . $offset . '" does not exist.', 1216909923);
		return parent::offsetGet($translatedOffset);
	}

	/**
	 * Creates, adds and returns a new controller argument to this composite object.
	 * If an argument with the same name exists already, it will be replaced by the
	 * new argument object.
	 *
	 * @param string $name Name of the argument
	 * @param string $dataType Name of one of the built-in data types
	 * @param boolean $isRequired TRUE if this argument should be marked as required
	 * @return TX_EXTMVC_Controller_Argument The new argument
	 */
	public function addNewArgument($name, $dataType = 'Text', $isRequired = FALSE) {
		$argument = new TX_EXTMVC_Controller_Argument($name, $dataType);
		$argument->setRequired($isRequired);
		$this->addArgument($argument);
		return $argument;
	}

	/**
	 * Adds the specified controller argument to this composite object.
	 * If an argument with the same name exists already, it will be replaced by the
	 * new argument object.
	 *
	 * Note that the argument will be cloned, not referenced.
	 *
	 * @param TX_EXTMVC_Controller_Argument $argument The argument to add
	 * @return void
	 */
	public function addArgument(TX_EXTMVC_Controller_Argument $argument) {
		$this->offsetSet(NULL, $argument);
	}

	/**
	 * Returns an argument specified by name
	 *
	 * @param string $argumentName Name of the argument to retrieve
	 * @return TX_EXTMVC_Controller_Argument
	 * @throws TX_EXTMVC_Exception_NoSuchArgument
	 */
	public function getArgument($argumentName) {
		if (!$this->offsetExists($argumentName)) throw new TX_EXTMVC_Exception_NoSuchArgument('An argument "' . $argumentName . '" does not exist.', 1195815178);
		return $this->offsetGet($argumentName);
	}

	/**
	 * Checks if an argument with the specified name exists
	 *
	 * @param string $argumentName Name of the argument to check for
	 * @return boolean TRUE if such an argument exists, otherwise FALSE
	 * @see offsetExists()
	 */
	public function hasArgument($argumentName) {
		return $this->offsetExists($argumentName);
	}

	/**
	 * Returns the names of all arguments contained in this object
	 *
	 * @return array Argument names
	 */
	public function getArgumentNames() {
		return array_keys($this->argumentNames);
	}

	/**
	 * Returns the short names of all arguments contained in this object that have one.
	 *
	 * @return array Argument short names
	 */
	public function getArgumentShortNames() {
		$argumentShortNames = array();
		foreach ($this as $argument) {
			$argumentShortNames[$argument->getShortName()] = TRUE;
		}
		return array_keys($argumentShortNames);
	}

	/**
	 * Magic setter method for the argument values. Each argument
	 * value can be set by just calling the setArgumentName() method.
	 *
	 * @param string $methodName Name of the method
	 * @param array $arguments Method arguments
	 * @return void
	 */
	public function __call($methodName, array $arguments) {
		if (substr($methodName, 0, 3) !== 'set') throw new LogicException('Unknown method "' . $methodName . '".', 1210858451);

		$firstLowerCaseArgumentName = $this->translateToLongArgumentName(strtolower($methodName{3}) . substr($methodName, 4));
		$firstUpperCaseArgumentName = $this->translateToLongArgumentName(ucfirst(substr($methodName, 3)));

		if (in_array($firstLowerCaseArgumentName, $this->getArgumentNames())) {
			$argument = parent::offsetGet($firstLowerCaseArgumentName);
			$argument->setValue($arguments[0]);
		} elseif (in_array($firstUpperCaseArgumentName, $this->getArgumentNames())) {
			$argument = parent::offsetGet($firstUpperCaseArgumentName);
			$argument->setValue($arguments[0]);
		}
	}

	/**
	 * Translates a short argument name to its corresponding long name. If the
	 * specified argument name is a real argument name already, it will be returned again.
	 *
	 * If an argument with the specified name or short name does not exist, an empty
	 * string is returned.
	 *
	 * @param string argument name
	 * @return string long argument name or empty string
	 */
	protected function translateToLongArgumentName($argumentName) {

		if (in_array($argumentName, $this->getArgumentNames())) return $argumentName;

		foreach ($this as $argument) {
			if ($argumentName === $argument->getShortName()) return $argument->getName();
		}
		return '';
	}
}
?>