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
class RequiredValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\RequiredValidator
	 */
	protected $subject;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->subject = $this->getMock('TYPO3\\CMS\\Form\\Validation\\RequiredValidator', array('dummy'), array(), '', FALSE);
	}

	public function validDataProvider() {
		return array(
			'a'   => array('a'),
			'a b' => array('a b'),
			'"0"' => array('0'),
			'0'   => array(0)
		);
	}

	public function invalidDataProvider() {
		return array(
			'empty string'  => array(''),
		);
	}

	/**
	 * @test
	 * @dataProvider validDataProvider
	 */
	public function isValidForValidDataReturnsTrue($input) {
		$this->subject->setFieldName('myRequired');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myRequired' => $input
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->subject->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidDataProvider
	 */
	public function isValidForInvalidDataReturnsFalse($input) {
		$this->subject->setFieldName('myRequired');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myRequired' => $input
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
		);
	}
}
