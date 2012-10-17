<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\BeforeExtbase14;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
 *  All rights reserved
 *
 *  This class is a backport of the corresponding class of FLOW3.
 *  All credits go to the v5 team.
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
 * Testcase for the number range validator
 *
 * This testcase checks the expected behavior for Extbase < 1.4.0, to make sure
 * we do not break backwards compatibility.
 *
 * @package Extbase
 * @subpackage extbase
 * @version $Id: NumberRangeValidator_testcase.php 2428 2010-07-20 10:18:51Z jocrau $
 */
class NumberRangeValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function numberRangeValidatorReturnsTrueForASimpleIntegerInRange() {
		$numberRangeValidator = new \TYPO3\CMS\Extbase\Validation\Validator\NumberRangeValidator();
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 1000));
		$this->assertTrue($numberRangeValidator->isValid(10.5));
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorReturnsFalseForANumberOutOfRange() {
		$numberRangeValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 1000));
		$this->assertFalse($numberRangeValidator->isValid(1000.1));
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorReturnsTrueForANumberInReversedRange() {
		$numberRangeValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->setOptions(array('startRange' => 1000, 'endRange' => 0));
		$this->assertTrue($numberRangeValidator->isValid(100));
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorReturnsFalseForAString() {
		$numberRangeValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 1000));
		$this->assertFalse($numberRangeValidator->isValid('not a number'));
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorCreatesTheCorrectErrorForANumberOutOfRange() {
		$numberRangeValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->expects($this->once())->method('addError')->with('The given subject was not in the valid range (%1$d - %2$d).', 1221561046, array(1, 42));
		$numberRangeValidator->setOptions(array('startRange' => 1, 'endRange' => 42));
		$numberRangeValidator->isValid(4711);
	}

	/**
	 * @test
	 */
	public function numberRangeValidatorCreatesTheCorrectErrorForAStringSubject() {
		$numberRangeValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\NumberRangeValidator', array('addError'), array(), '', FALSE);
		$numberRangeValidator->expects($this->once())->method('addError')->with('The given subject was not a valid number.', 1221563685);
		$numberRangeValidator->setOptions(array('startRange' => 0, 'endRange' => 42));
		$numberRangeValidator->isValid('this is not between 0 an 42');
	}

}


?>