<?php
namespace TYPO3\CMS\Form\Validation;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2008-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Interface for validate
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
interface ValidatorInterface {

	/**
	 * Set the fieldName
	 *
	 * @param string $fieldName The field name
	 * @return object The rule object
	 */
	public function setFieldName($fieldName);

	/**
	 * Returns the field name
	 *
	 * @return string The field name
	 */
	public function getFieldName();

	/**
	 * Returns TRUE if submitted value validates according to rule
	 *
	 * @return boolean
	 */
	public function isValid();

	/**
	 * Returns the message
	 *
	 * @return array Typoscript for cObj
	 */
	public function getMessage();

	/**
	 * Returns the error
	 *
	 * @return array Typoscript for cObj
	 */
	public function getError();

	/**
	 * Set the message, like 'required' for the validation rule
	 * and substitutes markers for values, like %maximum
	 *
	 * The output will be a Typoscript array to use as cObj
	 * If no parameter is given, it will take the default locallang label
	 * If only first parameter, then it's supposed to be a TEXT cObj
	 * When both are filled, it's supposed to be a cObj made by the administrator
	 * In the last case, no markers will be substituted
	 *
	 * @param mixed $message Message as string or TS
	 * @param string $type Name of the cObj
	 * @return void
	 */
	public function setMessage($message = '', $type = 'TEXT');

	/**
	 * Set if message needs to be displayed
	 *
	 * @param boolean $show TRUE is display
	 * @return object The rule object
	 */
	public function setShowMessage($show);

	/**
	 * Returns TRUE when message needs to be displayed
	 *
	 * @return boolean
	 */
	public function messageMustBeDisplayed();

}

?>