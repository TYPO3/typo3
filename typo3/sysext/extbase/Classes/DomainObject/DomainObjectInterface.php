<?php
namespace TYPO3\CMS\Extbase\DomainObject;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * A Domain Object Interface. All domain objects which should be persisted need to implement the below interface.
 * Usually you will need to subclass \TYPO3\CMS\Extbase\DomainObject\AbstractEntity and \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
 * instead.
 *
 * @see \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
 * @see \TYPO3\CMS\Extbase\DomainObject\AbstractValueObject
 */
interface DomainObjectInterface {

	/**
	 * Getter for uid.
	 *
	 * @return int the uid or NULL if none set yet.
	 */
	public function getUid();

	/**
	 * Setter for the pid.
	 *
	 * @param integer $pid
	 * @return void
	 */
	public function setPid($pid);

	/**
	 * Getter for the pid.
	 *
	 * @return int The pid or NULL if none set yet.
	 */
	public function getPid();

	/**
	 * Returns TRUE if the object is new (the uid was not set, yet). Only for internal use
	 *
	 * @return boolean
	 */
	public function _isNew();

	/**
	 * Reconstitutes a property. Only for internal use.
	 *
	 * @param string $propertyName
	 * @param string $value
	 * @return void
	 */
	public function _setProperty($propertyName, $value);

	/**
	 * Returns the property value of the given property name. Only for internal use.
	 *
	 * @param string $propertyName
	 * @return mixed The propertyValue
	 */
	public function _getProperty($propertyName);

	/**
	 * Returns a hash map of property names and property values
	 *
	 * @return array The properties
	 */
	public function _getProperties();
}

?>