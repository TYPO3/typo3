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
class BooleanValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\BooleanValidator';

	/**
	 * @test
	 * @author Pascal Dürsteler <pascal.duersteler@gmail.com>
	 */
	public function booleanValidatorReturnsNoErrorForAFalseStringExpectation() {
		$this->validatorOptions(array('is' => 'false'));
		$this->assertFalse($this->validator->validate(FALSE)->hasErrors());
	}

	/**
	 * @test
	 * @author Pascal Dürsteler <pascal.duersteler@gmail.com>
	 */
	public function booleanValidatorReturnsNoErrorForATrueStringExpectation() {
		$this->validatorOptions(array('is' => 'true'));
		$this->assertFalse($this->validator->validate(TRUE)->hasErrors());
	}

	/**
	 * @test
	 * @author Pascal Dürsteler <pascal.duersteler@gmail.com>
	 */
	public function booleanValidatorReturnsNoErrorForATrueExpectation() {
		$this->validatorOptions(array('is' => TRUE));
		$this->assertFalse($this->validator->validate(TRUE)->hasErrors());
	}

	/**
	 * @test
	 * @author Pascal Dürsteler <pascal.duersteler@gmail.com>
	 */
	public function booleanValidatorReturnsNoErrorForAFalseExpectation() {
		$this->validatorOptions(array('is' => FALSE));
		$this->assertFalse($this->validator->validate(FALSE)->hasErrors());
	}

	/**
	 * @test
	 * @author Pascal Dürsteler <pascal.duersteler@gmail.com>
	 */
	public function booleanValidatorReturnsErrorForTrueWhenFalseExpected() {
		$this->validatorOptions(array('is' => FALSE));
		$this->assertTrue($this->validator->validate(TRUE)->hasErrors());
	}

	/**
	 * @test
	 * @author Pascal Dürsteler <pascal.duersteler@gmail.com>
	 */
	public function booleanValidatorReturnsErrorForFalseWhenTrueExpected() {
		$this->validatorOptions(array('is' => TRUE));
		$this->assertTrue($this->validator->validate(FALSE)->hasErrors());
	}

	/**
	 * @test
	 * @author Pascal Dürsteler <pascal.duersteler@gmail.com>
	 */
	public function booleanValidatorReturnsErrorForAString() {
		$this->validatorOptions(array('is' => TRUE));
		$this->assertTrue($this->validator->validate('a string')->hasErrors());
	}

	/**
	 * @test
	 */
	public function booleanValidatorReturnsTrueIfNoParameterIsGiven() {
		$this->validatorOptions(array());
		$this->assertFalse($this->validator->validate(TRUE)->hasErrors());
	}
}

?>
