<?php
namespace TYPO3\CMS\Form\Tests\Unit\Validation;

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
 */
class BetweenValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\BetweenValidator
	 */
	protected $subject;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->subject = $this->getMock('TYPO3\\CMS\\Form\\Validation\\BetweenValidator', array('dummy'), array(), '', FALSE);
	}

	public function validNonInclusiveDataProvider() {
		return array(
			'3 < 5 < 7'      => array(array(3, 5, 7)),
			'0 < 10 < 20'    => array(array(0, 10, 20)),
			'-10 < 0 < 10'   => array(array(-10, 0, 10)),
			'-20 < -10 < 0'  => array(array(-20, -10, 0)),
			'1 < 2 < 3'      => array(array(1, 2, 3)),
			'1 < 1.01 < 1.1' => array(array(1, 1.01, 1.1)),
		);
	}

	public function invalidNonInclusiveDataProvider() {
		return array(
			'1 < 1 < 2'                 => array(array(1, 1, 2)),
			'1 < 2 < 2'                 => array(array(1, 2, 2)),
			'1.1 < 1.1 < 1.2'           => array(array(1.1, 1.1, 1.2)),
			'1.1 < 1.2 < 1.2'           => array(array(1.1, 1.2, 1.2)),
			'-10.1234 < -10.12340 < 10' => array(array(-10.1234, -10.12340, 10)),
			'100 < 0 < -100'            => array(array(100, 0, -100))
		);
	}

	public function validInclusiveDataProvider() {
		return array(
			'1 ≤ 1 ≤ 1'                 => array(array(1,1,1)),
			'-10.1234 ≤ -10.12340 ≤ 10' => array(array(-10.1234, -10.12340, 10)),
			'-10.1234 ≤ -10 ≤ 10'       => array(array(-10.1234, -10.12340, 10)),
		);
	}

	public function invalidInclusiveDataProvider() {
		return array(
			'-10.1234 ≤ -10.12345 ≤ 10' => array(array(-10.1234, -10.12345, 10)),
			'100 ≤ 0 ≤ -100'            => array(array(100, 0, -100))
		);
	}

	/**
	 * @test
	 * @dataProvider validNonInclusiveDataProvider
	 */
	public function isValidWithValidInputAndWithoutInclusiveReturnsTrue($input) {
		$this->subject->setMinimum($input[0]);
		$this->subject->setMaximum($input[2]);
		$this->subject->setFieldName('numericValue');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'numericValue' => $input[1]
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->subject->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider validInclusiveDataProvider
	 */
	public function isValidWithValidInputAndWithInclusiveReturnsTrue($input) {
		$this->subject->setMinimum($input[0]);
		$this->subject->setMaximum($input[2]);
		$this->subject->setFieldName('numericValue');
		$this->subject->setInclusive(TRUE);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'numericValue' => $input[1]
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->subject->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidNonInclusiveDataProvider
	 */
	public function isValidWithInvalidInputAndWithoutInclusiveReturnsFalse($input) {
		$this->subject->setMinimum($input[0]);
		$this->subject->setMaximum($input[2]);
		$this->subject->setFieldName('numericValue');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'numericValue' => $input[1]
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidInclusiveDataProvider
	 */
	public function isValidWithInvalidInputAndWithInclusiveReturnsFalse($input) {
		$this->subject->setMinimum($input[0]);
		$this->subject->setMaximum($input[2]);
		$this->subject->setFieldName('numericValue');
		$this->subject->setInclusive(TRUE);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'numericValue' => $input[1]
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
		);
	}
}
