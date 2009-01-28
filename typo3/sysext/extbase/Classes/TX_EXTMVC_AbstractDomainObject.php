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
 * A generic Domain Object
 *
 * @version $Id:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
abstract class TX_EXTMVC_AbstractDomainObject {
	
	/**
	 * An array of properties filled with database values of columns configured in $TCA.
	 *
	 * @var array
	 */
	private $EXMVCPersistenceCleanProperties = NULL;
	
	/**
	 * An array of aggregate properties configured in $TCA.
	 *
	 * @var array
	 */
	private $EXMVCPersistenceCleanAggregates = NULL;
	
	private	function initCleanProperties() {
			$possibleTableName = strtolower(get_class($this));
			t3lib_div::loadTCA($possibleTableName);
			$tca = $GLOBALS['TCA'][$possibleTableName]['columns'];
			foreach ($tca as $columnName => $columnConfiguration) {
				if (array_key_exists('foreign_table', $columnConfiguration['config'])) {
					$this->EXMVCPersistenceCleanAggregates[$columnName] = NULL;
				} else {
					$this->EXMVCPersistenceCleanProperties[$columnName] = NULL;
				}
			}
			$this->EXMVCPersistenceCleanProperties['uid'] = NULL;
	}
		
	public function reconstituteProperty($propertyName, $value) {
		$possibleSetterMethodName = 'set' . ucfirst($propertyName);
		$possibleAddMethodName = 'add' . ucfirst($propertyName);
		if (method_exists($this, $possibleSetterMethodName)) {
			$this->$possibleSetterMethodName($value);
		} elseif (method_exists($this, $possibleAddMethodName)) {
			$this->$possibleAddMethodName($value);
		} else {
			if (property_exists($this, $propertyName)) {
				$this->$propertyName = $value;
			}
		}
	}
	
	/**
	 * Register an object's clean state, e.g. after it has been reconstituted
	 * from the database
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function memorizeCleanState() {
		$this->initCleanProperties();
		foreach ($this->EXMVCPersistenceCleanProperties as $propertyName => $propertyValue) {
			$cleanProperties[$propertyName] = $this->$propertyName;
		}
		$this->EXMVCPersistenceCleanProperties = $cleanProperties;
	}
	
	/**
	 * returns TRUE if the properties configured in $TCA were modified after reconstitution
	 *
	 * @return boolean
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function isDirty() {
		$isDirty = FALSE;
		$cleanProperties = is_array($this->EXMVCPersistenceCleanProperties) ? $this->EXMVCPersistenceCleanProperties : array();
		if ($this->uid !== NULL && $this->uid != $cleanProperties['uid']) {
			throw new TX_EXTMVC__Persistence_Exception_TooDirty('The uid "' . $this->uid . '" has been modified, that is simply too much.', 1222871239);
		}
		foreach ($cleanProperties as $propertyName => $propertyValue) {
			if ($cleanProperties[$propertyName] !== $this->$propertyName) {
				$isDirty = TRUE;
			}
		}
		return $isDirty;
	}
	
	/**
	 * Returns a given string as UpperCamelCase
	 *
	 * @param	string	String to be converted to camel case
	 * @return	string	UpperCamelCasedWord
	 */
	private function underscoreToCamelCase($string) {
		$upperCamelCase = (str_replace(' ', '', ucwords(preg_replace('![^A-Z^a-z^0-9]+!', ' ', strtolower($string)))));
		$lowerCamelCase = $this->lowercaseFirst($upperCamelCase);
		return $lowerCamelCase;
	}
	
	private function lowercaseFirst($string) {
		return strtolower(substr($string,0,1) ) . substr($string,1);
	}
	
}
?>