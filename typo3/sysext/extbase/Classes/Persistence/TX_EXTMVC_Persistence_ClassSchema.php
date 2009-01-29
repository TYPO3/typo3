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
 * A class schema
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class ClassSchema {

	const MODELTYPE_ENTITY = 1;
	const MODELTYPE_VALUEOBJECT = 2;

	const ALLOWED_TYPES_PATTERN = '/^\\\\?(integer|int|float|boolean|string|array|DateTime|F3\\\\[a-zA-Z0-9\\\\]+)/';

	/**
	 * Name of the class this schema is referring to
	 *
	 * @var string
	 */
	protected $className;

	/**
	 * Model type of the class this schema is referring to
	 *
	 * @var integer
	 */
	protected $modelType = self::MODELTYPE_ENTITY;

	/**
	 * The name of the property holding the uid of an entity, if any.
	 *
	 * @var string
	 */
	protected $uidProperty;

	/**
	 * Properties of the class which need to be persisted
	 *
	 * @var array
	 */
	protected $properties = array();

	/**
	 * Constructs this class schema
	 *
	 * @param string $className Name of the class this schema is referring to
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($className) {
		$this->className = $className;
	}

	/**
	 * Returns the class name this schema is referring to
	 *
	 * @return string The class name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getClassName() {
		return $this->className;
	}

	/**
	 * Sets (defines) a specific property and its type.
	 *
	 * @param string $name Name of the property
	 * @param string $type Type of the property (ie. one of "integer", "float", "boolean", "string", "array", "DateTime" or some class type (F3_*)
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setProperty($name, $type) {
		$matches = array();
		if (preg_match(self::ALLOWED_TYPES_PATTERN, $type, $matches)) {
			$this->properties[$name] = ($matches[1] == 'int') ? 'integer' : $matches[1];
		} else {
			throw new TX_EXTMVC_Persistence_Exception_InvalidPropertyType('Invalid property type encountered: ' . $type, 1220387528);
		}
	}

	/**
	 * Returns all properties defined in this schema
	 *
	 * @return array
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getProperties() {
		return $this->properties;
	}

	/**
	 * Sets the model type of the class this schema is referring to.
	 *
	 * @param integer The model type, one of the MODELTYPE_* constants.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setModelType($modelType) {
		if ($modelType < 1 || $modelType > 3) throw new InvalidArgumentException('"' . $modelType . '" is an invalid model type.', 1212519195);
		$this->modelType = $modelType;
	}

	/**
	 * Returns the model type of the class this schema is referring to.
	 *
	 * @return integer The model type, one of the MODELTYPE_* constants.
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getModelType() {
		return $this->modelType;
	}

	/**
	 * If the class schema has a certain property
	 *
	 * @param string $propertyName Name of the property
	 * @return boolean
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hasProperty($propertyName) {
		return array_key_exists($propertyName, $this->properties);
	}

	/**
	 * Sets the property marked as uid of an object with @uid
	 *
	 * @param string $name
	 * @return void
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function setUidProperty($name) {
		$this->uidProperty = $name;
	}

	/**
	 * Gets the name of the property marked as uid of an object
	 *
	 * @return string
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function getUidProperty() {
		return $this->uidProperty;
	}

}
?>