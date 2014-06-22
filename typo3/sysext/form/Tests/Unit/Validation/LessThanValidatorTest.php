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
class LessThanValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\LessthanValidator
	 */
	protected $subject;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->subject = $this->getMock('TYPO3\\CMS\\Form\\Validation\\LessthanValidator', array('dummy'), array(), '', FALSE);
	}

	public function validValueProvider() {
		return array(
			'4 < 8' => array(array(8, 4)),
		);
	}

	public function invalidValueProvider() {
		return array(
			'8 < 4' => array(array(4, 8)),
			'8 < 8' => array(array(8, 8)),
		);
	}

	/**
	 * @test
	 * @dataProvider validValueProvider
	 */
	public function isValidForValidInputReturnsTrue($input) {
		$this->subject->setFieldName('myLessthan');
		$this->subject->setMaximum($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myLessthan' => $input[1]
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->subject->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidValueProvider
	 */
	public function isValidForInvalidInputReturnsFalse($input) {
		$this->subject->setFieldName('myLessthan');
		$this->subject->setMaximum($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myLessthan' => $input[1]
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
		);
	}
}
