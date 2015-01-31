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

use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Testcase for the abstract base-class of composite-validators
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class AbstractCompositeValidatorTest extends UnitTestCase {

	/**
	 * @test
	 */
	public function validatorAcceptsSupportedOptions() {
		$inputOptions = array(
			'requiredOption' => 666,
			'demoOption' => 42
		);
		$expectedOptions = $inputOptions;
		$validator = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\Fixture\AbstractCompositeValidatorClass::class, array('dummy'), array($inputOptions));
		$this->assertSame($expectedOptions, $validator->_get('options'));
	}

	/**
	 * @test
	 */
	public function validatorHasDefaultOptions() {
		$inputOptions = array('requiredOption' => 666);
		$expectedOptions = array(
			'requiredOption' => 666,
			'demoOption' => PHP_INT_MAX
		);
		$validator = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\Fixture\AbstractCompositeValidatorClass::class, array('dummy'), array($inputOptions));
		$this->assertSame($expectedOptions, $validator->_get('options'));
	}

	/**
	 * @test
	 */
	public function validatorThrowsExceptionOnNotSupportedOptions() {
		$inputOptions = array('invalidoption' => 42);
		$this->setExpectedException(\TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException::class, '', 1339079804);
		$validator = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\Fixture\AbstractCompositeValidatorClass::class, array('dummy'), array($inputOptions));
	}


	/**
	 * @test
	 */
	public function validatorThrowsExceptionOnMissingRequiredOptions() {
		$inputOptions = array();
		$this->setExpectedException(\TYPO3\CMS\Extbase\Validation\Exception\InvalidValidationOptionsException::class, '', 1339163922);
		$validator = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Tests\Unit\Validation\Validator\Fixture\AbstractCompositeValidatorClass::class, array('dummy'), array($inputOptions));
	}

}

