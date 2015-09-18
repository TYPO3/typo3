<?php
namespace TYPO3\CMS\Form\Tests\Unit\Validator;

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
class RequiredValidatorTest extends AbstractValidatorTest {

	/**
	 * @var string
	 */
	protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\RequiredValidator::class;

	/**
	 * @return array
	 */
	public function validDataProvider() {
		return array(
			'a'   => array('a'),
			'a b' => array('a b'),
			'"0"' => array('0'),
			'0'   => array(0)
		);
	}

	/**
	 * @return array
	 */
	public function invalidDataProvider() {
		return array(
			'empty string'  => array(''),
		);
	}

	/**
	 * @test
	 * @dataProvider validDataProvider
	 */
	public function validateForValidDataHasEmptyErrorResult($input) {
		$options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
		$subject = $this->createSubject($options);

		$this->assertEmpty(
			$subject->validate($input)->getErrors()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidDataProvider
	 */
	public function validateForInvalidDataHasNotEmptyErrorResult($input) {
		$options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
		$subject = $this->createSubject($options);

		$this->assertNotEmpty(
			$subject->validate($input)->getErrors()
		);
	}

}
