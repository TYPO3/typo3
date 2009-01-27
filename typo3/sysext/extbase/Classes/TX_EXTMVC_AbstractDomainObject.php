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
	
	private $cleanProperties;
	
	/**
	 * Stores the unchanged values of the database fields to compare
	 * them with the values at commit time.
	 *
	 * @return void
	 * @author Jochen Rau <jochen.rau@typoplanet.de>
	 */
	public function memorizeCleanObjectState() {
		$possibleTableName = strtolower(get_class($this));
		t3lib_div::loadTCA($possibleTableName);
		$tca = $GLOBALS['TCA'][$possibleTableName]['columns'];
		$properties = array_flip(array_keys($tca));
		foreach ($properties as $propertyName => $propertyValue) {
			$this->cleanProperties[$this->underscoreToCamelCase($propertyName)] = 'clean value';
		}
	}
	
	/**
	 * Returns given string as CamelCased
	 *
	 * @param	string	String to convert to camel case
	 * @return	string	UpperCamelCasedWord
	 */
	protected function underscoreToCamelCase($string) {
		$upperCamelCase = (str_replace(' ', '', ucwords(preg_replace('![^A-Z^a-z^0-9]+!', ' ', strtolower($string)))));
		$lowerCamelCase = strtolower( substr($upperCamelCase,0,1) ) . substr($upperCamelCase,1);
		return $lowerCamelCase;
	}
	
}
?>