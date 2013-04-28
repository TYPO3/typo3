<?php
namespace TYPO3\CMS\Extbase\Validation\Validator;

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
 * Contract for an object validator
 *
 * @deprecated since Extbase 1.4.0, will be removed two versions after Extbase 6.1
 */
interface ObjectValidatorInterface extends \TYPO3\CMS\Extbase\Validation\Validator\ValidatorInterface {

	/**
	 * Checks the given object can be validated by the validator implementation
	 *
	 * @param object $object The object to be checked
	 * @return boolean TRUE if this validator can validate instances of the given object or FALSE if it can't
	 */
	public function canValidate($object);

	/**
	 * Checks if the specified property of the given object is valid.
	 *
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param object $object The object containing the property to validate
	 * @param string $propertyName Name of the property to validate
	 * @return boolean TRUE if the property value is valid, FALSE if an error occured
	 */
	public function isPropertyValid($object, $propertyName);
}

?>