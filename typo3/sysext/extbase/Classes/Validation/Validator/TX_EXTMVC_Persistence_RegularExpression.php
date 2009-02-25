<?php
declare(ENCODING = 'utf-8');
namespace TX_EXTMVC_Validation_Validator;

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
 * @package FLOW3
 * @subpackage Validation
 * @version $ID:$
 */

/**
 * Validator for regular expressions
 *
 * @package FLOW3
 * @subpackage Validation
 * @version $ID:$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class TX_EXTMVC_Validation_RegularExpression {

	/**
	 * @var string The regular expression
	 */
	protected $regularExpression;

	/**
	 * Creates a RegularExpression validator with the given expression
	 *
	 * @param string The regular expression, must be ready to use with preg_match()
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function __construct($regularExpression) {
		$this->regularExpression = $regularExpression;
	}

	/**
	 * Returns TRUE, if the given propterty ($proptertyValue) matches the given regular expression.
	 * Any errors will be stored in the given errors object.
	 * If at least one error occurred, the result is FALSE.
	 *
	 * @param mixed $propertyValue The value that should be validated
	 * @param TX_EXTMVC_Validation_Errors $errors Any occured Error will be stored here
	 * @return boolean TRUE if the value could be validated. FALSE if an error occured
	 * @throws TX_EXTMVC_Validation_Exception_InvalidSubject if this validator cannot validate the given subject or the subject is not an object.
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function isValidProperty($propertyValue, TX_EXTMVC_Validation_Errors &$errors) {

		if (!is_string($propertyValue) || preg_match($this->regularExpression, $propertyValue) === 0) {
			$errors->append($this->objectFactory->create('TX_EXTMVC_Validation_Error', 'The given subject did not match the pattern. Got: "' . $propertyValue . '"', 1221565130));
			return FALSE;
		}

		return TRUE;
	}
}

?>