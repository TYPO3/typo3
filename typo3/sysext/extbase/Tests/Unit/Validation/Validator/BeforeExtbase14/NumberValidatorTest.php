<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\BeforeExtbase14;

/***************************************************************
 *  Copyright notice
 *
 *  This class is a backport of the corresponding class of TYPO3 Flow.
 *  All credits go to the TYPO3 Flow team.
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
 * Testcase for the number validator
 *
 * This testcase checks the expected behavior for Extbase < 1.4.0, to make sure
 * we do not break backwards compatibility.
 */
class NumberValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function numberValidatorReturnsTrueForASimpleInteger() {
		$numberValidator = new \TYPO3\CMS\Extbase\Validation\Validator\NumberValidator();
		$this->assertTrue($numberValidator->isValid(1029437));
	}

	/**
	 * @test
	 */
	public function numberValidatorReturnsFalseForAString() {
		$numberValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberValidator', array('addError'), array(), '', FALSE);
		$this->assertFalse($numberValidator->isValid('not a number'));
	}

	/**
	 * @test
	 */
	public function numberValidatorCreatesTheCorrectErrorForAnInvalidSubject() {
		$numberValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberValidator', array('addError'), array(), '', FALSE);
		$numberValidator->expects($this->once())->method('addError')->with('The given subject was not a valid number.', 1221563685);
		$numberValidator->isValid('this is not a number');
	}
}

?>