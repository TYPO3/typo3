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
class InArrayValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\InArrayValidator
	 */
	protected $subject;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->subject = $this->getMock('TYPO3\\CMS\\Form\\Validation\\InArrayValidator', array('dummy'), array(), '', FALSE);
	}

	public function validArrayProvider() {
		return array(
			'12 in (12, 13)' => array(array(array(12, 13), 12))
		);
	}

	public function invalidArrayProvider() {
		return array(
			'12 in (11, 13)' => array(array(array(11, 13), 12)),
		);
	}

	/**
	 * @test
	 * @dataProvider validArrayProvider
	 */
	public function isValidForValidInputReturnsTrue($input) {
		$this->subject->setFieldName('myfield');
		$this->subject->setArray($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myfield' => $input[1]
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->subject->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidArrayProvider
	 */
	public function isValidForInvalidInputReturnsFalse($input) {
		$this->subject->setFieldName('myfield');
		$this->subject->setArray($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myfield' => $input[1]
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider validArrayProvider
	 */
	public function isValidForValidInputWithStrictComparisonReturnsTrue($input) {
		$this->subject->setFieldName('myfield');
		$this->subject->setArray($input[0]);
		$this->subject->setStrict(TRUE);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myfield' => $input[1]
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->subject->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidArrayProvider
	 */
	public function isValidForInvalidInputWithStrictComparisonReturnsFalse($input) {
		$this->subject->setFieldName('myfield');
		$this->subject->setArray($input[0]);
		$this->subject->setStrict(TRUE);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myfield' => $input[1]
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
		);
	}
}
