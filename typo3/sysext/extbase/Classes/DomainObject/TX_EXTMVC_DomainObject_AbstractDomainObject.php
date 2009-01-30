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

require_once(t3lib_extMgm::extPath('extmvc') . 'Classes/Utility/TX_EXTMVC_Utility_Strings.php');

/**
 * A generic Domain Object
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class TX_EXTMVC_DomainObject_AbstractDomainObject {
	
	/**
	 * An array of properties filled with database values of columns configured in $TCA.
	 *
	 * @var array
	 */
	private $cleanProperties = NULL;
	
	/**
	 * A configuration array of properties configured as 1:n relations in $TCA.
	 *
	 * @var array
	 */
	private $oneToManyRelations = array();
	
	/**
	 * A configuration array of properties configured as m:n relations in $TCA.
	 *
	 * @var array
	 */
	private $manyToManyRelations = array();
	
	private	function initCleanProperties() {
			$possibleTableName = strtolower(get_class($this));
			t3lib_div::loadTCA($possibleTableName);
			$tca = $GLOBALS['TCA'][$possibleTableName]['columns'];
			foreach ($tca as $columnName => $columnConfiguration) {
				$propertyName = TX_EXTMVC_Utility_Strings::underscoredToLowerCamelCase($columnName);
				if (property_exists($this, $propertyName)) {
					$this->cleanProperties[$propertyName] = NULL;
				}
				if (array_key_exists('foreign_table', $columnConfiguration['config'])) {
					// TODO take IRRE into account
					if (array_key_exists('MM', $columnConfiguration['config'])) {
						$this->manyToManyRelations[$propertyName] = array(
							'foreign_class' => $columnConfiguration['config']['foreign_class'],
							'foreign_table' => $columnConfiguration['config']['foreign_table'],
							'MM' => $columnConfiguration['config']['MM']
							);
					} else {
						// TODO implement a $TCA object 
						$this->oneToManyRelations[$propertyName] = array(
							'foreign_class' => $columnConfiguration['config']['foreign_class'],
							'foreign_table' => $columnConfiguration['config']['foreign_table'],
							'foreign_field' => $columnConfiguration['config']['foreign_field'],
							'foreign_table_field' => $columnConfiguration['config']['foreign_table_field']
							);
					}
				}				
			}
			$this->cleanProperties['uid'] = NULL;
	}
	
	public function getOneToManyRelations() {
		return $this->oneToManyRelations;
	}
	
	public function getManyToManyRelations() {
		return $this->manyToManyRelations;
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
		$this->initCleanProperties();
	}
	
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
		$cleanProperties = array();
		foreach ($this->cleanProperties as $propertyName => $propertyValue) {
			$cleanProperties[$propertyName] = $this->$propertyName;
		}
		$this->cleanProperties = $cleanProperties;
	}
	
	/**
	 * returns TRUE if the properties configured in $TCA were modified after reconstitution
	 *
	 * @return boolean
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function _isDirty() {
		if (!is_array($this->cleanProperties)) throw new TX_EXTMVC_Persistence_Exception_CleanStateNotMemorized('The clean state of the object "' . get_class($this) . '" has not been memorized before asking _isDirty().', 1233309106);
		if ($this->uid !== NULL && $this->uid != $this->cleanProperties['uid']) throw new TX_EXTMVC_Persistence_Exception_TooDirty('The uid "' . $this->uid . '" has been modified, that is simply too much.', 1222871239);
		foreach ($this->cleanProperties as $propertyName => $propertyValue) {
			if ($this->$propertyName !== $propertyValue) return TRUE;
		}
		return FALSE;
	}	
}
?>