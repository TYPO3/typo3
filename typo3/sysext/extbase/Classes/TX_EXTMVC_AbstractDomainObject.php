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
	private $cleanProperties = NULL;
	
	private	function initCleanProperties() {
			$possibleTableName = strtolower(get_class($this));
			t3lib_div::loadTCA($possibleTableName);
			$tca = $GLOBALS['TCA'][$possibleTableName]['columns'];
			$tcaColumns = array_keys($tca);
			foreach ($tcaColumns as $columnName) {
				$this->cleanProperties[$columnName] = NULL;
			}
		return array_key_exists($propertyName, $this->cleanProperties);
	}
		
	private	function isConfiguredInTca($propertyName) {
		return array_key_exists($propertyName, $this->cleanProperties);
	}
		
	public function reconstituteProperty($propertyName, $value) {
		if ($this->cleanProperties === NULL) {
			$this->initCleanProperties();
		}
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
		if ($this->isConfiguredInTca($propertyName)) {
			$this->cleanProperties[$propertyName] = $value;			
		}
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