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
 * Testcase for the float validator
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class FloatValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\AbstractValidatorTestcase {

	protected $validatorClassName = 'TYPO3\\CMS\\Extbase\\Validation\\Validator\\FloatValidator';

	public function setup() {
		$this->validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'));
	}

	/**
	 * Data provider with valid floats
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function validFloats() {
		return array(
			array(1029437.234726),
			array('123.45'),
			array('+123.45'),
			array('-123.45'),
			array('123.45e3'),
			array(123450.0)
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider validFloats
	 * @param mixed $float
	 */
	public function floatValidatorReturnsNoErrorsForAValidFloat($float) {
		$this->assertFalse($this->validator->validate($float)->hasErrors());
	}

	/**
	 * Data provider with invalid floats
	 *
	 * @return array
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 */
	public function invalidFloats() {
		return array(
			array(1029437),
			array('1029437'),
			array('not a number')
		);
	}

	/**
	 * @author Karsten Dambekalns <karsten@typo3.org>
	 * @test
	 * @dataProvider invalidFloats
	 * @param mixed $float
	 */
	public function floatValidatorReturnsErrorForAnInvalidFloat($float) {
		$this->assertTrue($this->validator->validate($float)->hasErrors());
	}

	/**
	 * test
	 *
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function floatValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$this->assertEquals(1, count($this->validator->validate(123456)->getErrors()));
	}
}
