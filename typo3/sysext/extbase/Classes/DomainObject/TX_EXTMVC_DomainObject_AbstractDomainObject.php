<?php

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

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Persistence/Mapper/TX_EXTMVC_Persistence_Mapper_ObjectRelationalMapper.php');

/**
 * A generic Domain Object
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class TX_EXTMVC_DomainObject_AbstractDomainObject {
	
	/**
	 * @var string The uid
	 */
	protected $uid;

	/**
	 * The generic constructor. If you want to implement your own __constructor() method in your Domain Object you have to call 
	 * $this->initializeObject() in the first line of your constructor.
	 *
	 * @var array
	 */
	private $cleanProperties = NULL;
	
	public function __construct() {
		$this->initializeObject();
	}
		
	/**
	 * This is the magic wakeup() method. It's invoked by the unserialize statement in the reconstitution process
	 * of the object. If you want to implement your own __wakeup() method in your Domain Object you have to call 
	 * parent::__wakeup() first!
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function __wakeup() {
		foreach ($GLOBALS['EXTMVC']['reconstituteObject']['properties'] as $propertyName => $value) {
			$this->_reconstituteProperty($propertyName, $value);
		}
		$this->initializeObject();
		$this->initializeCleanProperties();
	}
	
	/**
	 * A template function to initialize an object
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	protected function initializeObject() {
	}
	
	/**
	 * Getter for uid
	 *
	 * @return string
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function getUid() {
		return $this->uid;
	}
	
	/**
	 * Reconstitutes a property. This method should only be called at reconstitution time!
	 *
	 * @param string $propertyName 
	 * @param string $value 
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function _reconstituteProperty($propertyName, $value) {
		if (property_exists($this, $propertyName)) {
			$this->$propertyName = $value;
		} else {
			// throw new TX_EXTMVC_Persistence_Exception_UnknownProperty('The property "' . $propertyName . '" doesn\'t exist in this object.', 1233270476);
		}
	}
	
	/**
	 * Register an object's clean state, e.g. after it has been reconstituted
	 * from the database
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function _memorizeCleanState() {
		$this->initializeCleanProperties();
		$cleanProperties = array();
		foreach ($this->cleanProperties as $propertyName => $propertyValue) {
			$cleanProperties[$propertyName] = $this->$propertyName;
		}
		$this->cleanProperties = $cleanProperties;
	}
	
	/**
	 * returns TRUE if the properties were modified after reconstitution
	 *
	 * @return boolean
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function _isDirty() {
		// if (!is_array($this->cleanProperties)) throw new TX_EXTMVC_Persistence_Exception_CleanStateNotMemorized('The clean state of the object "' . get_class($this) . '" has not been memorized before asking _isDirty().', 1233309106);
		if ($this->uid !== NULL && $this->uid != $this->cleanProperties['uid']) throw new TX_EXTMVC_Persistence_Exception_TooDirty('The uid "' . $this->uid . '" has been modified, that is simply too much.', 1222871239);
		foreach ($this->cleanProperties as $propertyName => $propertyValue) {
			if ($this->$propertyName !== $propertyValue) return TRUE;
		}
		return FALSE;
	}

	/**
	 * Returns a hash map of property names and property values
	 *
	 * @return array The properties
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function _getProperties() {
		return get_object_vars($this);
	}

	/**
	 * Returns a hash map of dirty properties and $values
	 *
	 * @return boolean
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function _getDirtyProperties() {
		if (!is_array($this->cleanProperties)) throw new TX_EXTMVC_Persistence_Exception_CleanStateNotMemorized('The clean state of the object "' . get_class($this) . '" has not been memorized before asking _isDirty().', 1233309106);
		if ($this->uid !== NULL && $this->uid != $this->cleanProperties['uid']) throw new TX_EXTMVC_Persistence_Exception_TooDirty('The uid "' . $this->uid . '" has been modified, that is simply too much.', 1222871239);
		$dirtyProperties = array();
		foreach ($this->cleanProperties as $propertyName => $propertyValue) {
			if ($this->$propertyName !== $propertyValue) {
				$dirtyProperties[$propertyName] = $this->$propertyName;
			}
		}
		return $dirtyProperties;
	}

	private	function initializeCleanProperties() {
		$properties = get_object_vars($this);
		$dataMapper = t3lib_div::makeInstance('TX_EXTMVC_Persistence_Mapper_ObjectRelationalMapper');
		foreach ($properties as $propertyName => $propertyValue) {
			if ($dataMapper->isPersistableProperty(get_class($this), $propertyName)) {
				$this->cleanProperties[$propertyName] = NULL;
			}
		}
		$this->cleanProperties['uid'] = NULL;
	}
	
}
?>