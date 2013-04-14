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
 * Testcase for the number range validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class NumberRangeValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator';

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsNoErrorForASimpleIntegerInRange() {
		$this->validatorOptions(array('minimum' => 0, 'maximum' => 1000));
		$this->assertFalse($this->validator->validate(10.5)->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsErrorForANumberOutOfRange() {
		$this->validatorOptions(array('minimum' => 0, 'maximum' => 1000));
		$this->assertTrue($this->validator->validate(1000.1)->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsNoErrorForANumberInReversedRange() {
		$this->validatorOptions(array('minimum' => 1000, 'maximum' => 0));
		$this->assertFalse($this->validator->validate(100)->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function numberRangeValidatorReturnsErrorForAString() {
		$this->validatorOptions(array('minimum' => 0, 'maximum' => 1000));
		$this->assertTrue($this->validator->validate('not a number')->hasErrors());
	}
}

?>