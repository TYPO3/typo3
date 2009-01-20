<?php
declare(ENCODING = 'utf-8');


/*                                                                        *
 * This script belongs to the FLOW3 framework.                            *
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
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:$
 */

/**
 * A composite of controller arguments
 *
 * @package FLOW3
 * @subpackage MVC
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Arguments extends ArrayObject {

	/**
	 * @var F3_FLOW3_Object_FactoryInterface A reference to the object factory
	 */
	protected $objectFactory;

	/**
	 * @var F3_FLOW3_Object_ManagerInterface A reference to the object manager
	 */
	protected $objectManager;

	/**
	 * @var array Names of the arguments contained by this object
	 */
	protected $argumentNames = array();

	/**
	 * Constructs this Arguments object
	 *
	 * @param F3_FLOW3_Object_FactoryInterface $objectFactory
	 * @param F3_FLOW3_Object_ManagerInterface $objectManager
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct(F3_FLOW3_Object_FactoryInterface $objectFactory, F3_FLOW3_Object_ManagerInterface $objectManager) {
		$this->objectFactory = $objectFactory;
		$this->objectManager = $objectManager;
		parent::__construct();
	}

	/**
	 * Adds or replaces the argument specified by $value. The argument's name is taken from the
	 * argument object itself, therefore the $offset does not have any meaning in this context.
	 *
	 * @param mixed $offset Offset - not used here
	 * @param mixed $value The argument.
	 * @return void
	 * @throws InvalidArgumentException if the argument is not a valid Controller Argument object
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
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
	 * If $dataType is an object registered at the Object Manager, it sets the default property converter to map this property.
	 *
	 * @param string $name Name of the argument
	 * @param string $dataType Name of one of the built-in data types
	 * @param boolean $isRequired TRUE if this argument should be marked as required
	 * @return TX_EXTMVC_Controller_Argument The new argument
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addNewArgument($name, $dataType = 'Text', $isRequired = FALSE) {
		$argument = $this->objectFactory->create('F3_FLOW3_MVC_Controller_Argument', $name, $dataType);
		$argument->setRequired($isRequired);

		if ($this->objectManager->isObjectRegistered($dataType)) {
			$propertyConverter = $this->objectFactory->create('F3_FLOW3_Property_Converter_DomainObjectConverter', $dataType);
			$argument->setPropertyConverter($propertyConverter)->setPropertyConverterInputFormat('array');
		}
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasArgument($argumentName) {
		return $this->offsetExists($argumentName);
	}

	/**
	 * Returns the names of all arguments contained in this object
	 *
	 * @return array Argument names
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getArgumentNames() {
		return array_keys($this->argumentNames);
	}

	/**
	 * Returns the short names of all arguments contained in this object that have one.
	 *
	 * @return array Argument short names
	 * @author Robert Lemke <robert@typo3.org>
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
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __call($methodName, array $arguments) {
		if (F3_PHP6_Functions::substr($methodName, 0, 3) !== 'set') throw new LogicException('Unknown method "' . $methodName . '".', 1210858451);

		$firstLowerCaseArgumentName = $this->translateToLongArgumentName(F3_PHP6_Functions::strtolower($methodName{3}) . F3_PHP6_Functions::substr($methodName, 4));
		$firstUpperCaseArgumentName = $this->translateToLongArgumentName(F3_PHP6_Functions::ucfirst(F3_PHP6_Functions::substr($methodName, 3)));

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
	 * @author Robert Lemke <robert@typo3.org>
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