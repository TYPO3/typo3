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
 * A controller argument
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class TX_EXTMVC_Controller_Argument {

	/**
	 * Name of this argument
	 * @var string
	 */
	protected $name = '';

	/**
	 * Short name of this argument
	 * @var string
	 */
	protected $shortName = NULL;

	/**
	 * Data type of this argument's value
	 * @var string
	 */
	protected $dataType = 'Text';

	/**
	 * TRUE if this argument is required
	 * @var boolean
	 */
	protected $isRequired = FALSE;

	/**
	 * Actual value of this argument
	 * @var object
	 */
	protected $value = NULL;

	/**
	 * The argument is valid
	 * @var boolean
	 */
	protected $isValid = TRUE;

	/**
	 * Uid for the argument, if it has one
	 * @var string
	 */
	protected $uid = NULL;

	/**
	 * Constructs this controller argument
	 *
	 * @param string $name Name of this argument
	 * @param string $dataType The data type of this argument
	 * @param F3_FLOW3_Object_ManagerInterface The object manager
	 * @throws InvalidArgumentException if $name is not a string or empty
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($name, $dataType = 'Text') {
		if (!is_string($name) || strlen($name) < 1) throw new InvalidArgumentException('$name must be of type string, ' . gettype($name) . ' given.', 1187951688);
		$this->name = $name;
		$this->setDataType($dataType);
	}

	/**
	 * Returns the name of this argument
	 *
	 * @return string This argument's name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Sets the short name of this argument.
	 *
	 * @param string $shortName A "short name" - a single character
	 * @return TX_EXTMVC_Controller_Argument $this
	 * @throws InvalidArgumentException if $shortName is not a character
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setShortName($shortName) {
		if ($shortName !== NULL && (!is_string($shortName) || strlen($shortName) != 1)) throw new InvalidArgumentException('$shortName must be a single character or NULL', 1195824959);
		$this->shortName = $shortName;
		return $this;
	}

	/**
	 * Returns the short name of this argument
	 *
	 * @return string This argument's short name
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function getShortName() {
		return $this->shortName;
	}

	/**
	 * Sets the data type of this argument's value
	 *
	 * @param string $dataType: Name of the data type
	 * @return TX_EXTMVC_Controller_Argument $this
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setDataType($dataType) {
		$this->dataType = ($dataType != '' ? $dataType : 'Text');
		return $this;
	}

	/**
	 * Returns the data type of this argument's value
	 *
	 * @return string The data type
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getDataType() {
		return $this->dataType;
	}

	/**
	 * Marks this argument to be required
	 *
	 * @param boolean $required TRUE if this argument should be required
	 * @return TX_EXTMVC_Controller_Argument $this
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setRequired($required) {
		$this->isRequired = $required;
		return $this;
	}

	/**
	 * Returns TRUE if this argument is required
	 *
	 * @return boolean TRUE if this argument is required
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isRequired() {
		return $this->isRequired;
	}

	/**
	 * Sets the value of this argument.
	 *
	 * @param mixed $value: The value of this argument
	 * @return TX_EXTMVC_Controller_Argument $this
	 * @throws TX_EXTMVC_Exception_InvalidArgumentValue if the argument is not a valid object of type $dataType
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setValue($value) {
		$this->value = $value;
		return $this;
	}

	/**
	 * Returns the value of this argument
	 *
	 * @return object The value of this argument - if none was set, NULL is returned
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Checks if this argument has a value set.
	 *
	 * @return boolean TRUE if a value was set, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isValue() {
		return $this->value !== NULL;
	}

	/**
	 * Set the validity status of the argument
	 *
	 * @param boolean TRUE if the argument is valid, FALSE otherwise
	 * @return void
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function setValidity($isValid) {
		$this->isValid = $isValid;
	}

	/**
	 * Returns TRUE when the argument is valid
	 *
	 * @return boolean TRUE if the argument is valid
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function isValid() {
		return $this->isValid;
	}
	
	/**
	 * Set the uid for the argument.
	 *
	 * @param string $uid The uid for the argument.
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setUid($uid) {
		$this->uid = $uid;
	}

	/**
	 * Get the uid of the argument, if it has one.
	 *
	 * @return string Uid of the argument. If none set, returns NULL.
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getUid() {
		return $this->uid;
	}

	/**
	 * Returns a string representation of this argument's value
	 *
	 * @return string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __toString() {
		return (string)$this->value;
	}
}
?>