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
 * Test case
 */
class RegularExpressionValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	protected $validatorClassName = \TYPO3\CMS\Extbase\Validation\Validator\RegularExpressionValidator::class;

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function regularExpressionValidatorMatchesABasicExpressionCorrectly() {
		$options = array('regularExpression' => '/^simple[0-9]expression$/');
		$validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));
		$this->assertFalse($validator->validate('simple1expression')->hasErrors());
		$this->assertTrue($validator->validate('simple1expressions')->hasErrors());
	}

	/**
	 * @test
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function regularExpressionValidatorCreatesTheCorrectErrorIfTheExpressionDidNotMatch() {
		$options = array('regularExpression' => '/^simple[0-9]expression$/');
		$validator = $this->getMock($this->validatorClassName, array('translateErrorMessage'), array($options));
		$errors = $validator->validate('some subject that will not match')->getErrors();
		// we only test for the error code, after the translation Method for message is mocked anyway
		$this->assertEquals(array(new \TYPO3\CMS\Extbase\Validation\Error(NULL, 1221565130)), $errors);
	}

}
