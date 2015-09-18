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
class InArrayValidatorTest extends AbstractValidatorTest {

	/**
	 * @var string
	 */
	protected $subjectClassName = \TYPO3\CMS\Form\Domain\Validator\InArrayValidator::class;

	/**
	 * @return array
	 */
	public function validArrayProvider() {
		return array(
			'12 in (12, 13)' => array(array(array(12, 13), 12))
		);
	}

	/**
	 * @return array
	 */
	public function invalidArrayProvider() {
		return array(
			'12 in (11, 13)' => array(array(array(11, 13), 12)),
		);
	}

	/**
	 * @test
	 * @dataProvider validArrayProvider
	 */
	public function validateForValidInputHasEmptyErrorResult($input) {
		$options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
		$options['array.'] = $input[0];
		$subject = $this->createSubject($options);

		$this->assertEmpty(
			$subject->validate($input[1])->getErrors()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidArrayProvider
	 */
	public function validateForInvalidInputHasNotEmptyErrorResult($input) {
		$options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
		$options['array.'] = $input[0];
		$subject = $this->createSubject($options);

		$this->assertNotEmpty(
			$subject->validate($input[1])->getErrors()
		);
	}

	/**
	 * @test
	 * @dataProvider validArrayProvider
	 */
	public function validateForValidInputWithStrictComparisonHasEmptyErrorResult($input) {
		$options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
		$options['array.'] = $input[0];
		$options['strict'] = TRUE;
		$subject = $this->createSubject($options);

		$this->assertEmpty(
			$subject->validate($input[1])->getErrors()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidArrayProvider
	 */
	public function validateForInvalidInputWithStrictComparisonHasNotEmptyErrorResult($input) {
		$options = array('element' => uniqid('test'), 'errorMessage' => uniqid('error'));
		$options['array.'] = $input[0];
		$options['strict'] = TRUE;
		$subject = $this->createSubject($options);

		$this->assertNotEmpty(
			$subject->validate($input[1])->getErrors()
		);
	}

}
