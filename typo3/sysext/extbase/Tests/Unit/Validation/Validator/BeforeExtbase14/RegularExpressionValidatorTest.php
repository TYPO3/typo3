<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\BeforeExtbase14;

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
 * Test case
 *
 * This testcase checks the expected behavior for Extbase < 1.4.0, to make sure
 * we do not break backwards compatibility.
 */
class RegularExpressionValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function regularExpressionValidatorMatchesABasicExpressionCorrectly() {
		$regularExpressionValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\RegularExpressionValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		$regularExpressionValidator->expects($this->once())->method('addError');
		$regularExpressionValidator->setOptions(array('regularExpression' => '/^simple[0-9]expression$/'));
		$regularExpressionValidator->isValid('simple1expression');
		$regularExpressionValidator->isValid('simple1expressions');
	}

	/**
	 * @test
	 */
	public function regularExpressionValidatorCreatesTheCorrectErrorIfTheExpressionDidNotMatch() {
		$regularExpressionValidator = $this->getMock('TYPO3\\CMS\\Extbase\\Validation\\Validator\\RegularExpressionValidator', array('addError', 'translateErrorMessage'), array(), '', FALSE);
		// we only test for the error key, after the translation method is mocked.
		$regularExpressionValidator->expects($this->once())->method('addError')->with(NULL, 1221565130);
		$regularExpressionValidator->setOptions(array('regularExpression' => '/^simple[0-9]expression$/'));
		$regularExpressionValidator->isValid('some subject that will not match');
	}
}
