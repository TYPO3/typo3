<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator;

/*
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
 * Testcase for the number range validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class BooleanValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = \TYPO3\CMS\Extbase\Validation\Validator\BooleanValidator::class;

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
