<?php
namespace TYPO3\CMS\Form\Validation;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

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
