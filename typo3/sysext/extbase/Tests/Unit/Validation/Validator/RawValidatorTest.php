<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

/*                                                                        *
 * This script belongs to the Extbase framework.                            *
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
require_once __DIR__ . '/AbstractValidatorTestcase.php';

/**
 * Testcase for the raw validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class RawValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\RawValidator';

	/**
	 * @test
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function theRawValidatorAlwaysReturnsNoErrors() {
		$rawValidator = new \TYPO3\CMS\Extbase\Validation\Validator\RawValidator(array());
		$this->assertFalse($rawValidator->validate('simple1expression')->hasErrors());
		$this->assertFalse($rawValidator->validate('')->hasErrors());
		$this->assertFalse($rawValidator->validate(NULL)->hasErrors());
		$this->assertFalse($rawValidator->validate(FALSE)->hasErrors());
		$this->assertFalse($rawValidator->validate(new \ArrayObject())->hasErrors());
	}
}

?>