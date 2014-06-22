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
class DateValidatorTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\DateValidator
	 */
	protected $subject;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->subject = $this->getMock('TYPO3\\CMS\\Form\\Validation\\DateValidator', array('dummy'), array(), '', FALSE);
	}

	public function validDateProvider() {
		return array(
			'28-03-2012' => array(array('%e-%m-%Y', '28-03-2012')),
			'8-03-2012'  => array(array('%e-%m-%Y', '8-03-2012')),
			'29-02-2012' => array(array('%d-%m-%Y', '29-02-2012'))
		);
	}

	public function invalidDateProvider() {
		return array(
			'32-03-2012' => array(array('%d-%m-%Y', '32-03-2012')),
			'31-13-2012' => array(array('%d-%m-%Y', '31-13-2012')),
			'29-02-2011' => array(array('%d-%m-%Y', '29-02-2011'))
		);
	}

	/**
	 * @test
	 * @dataProvider validDateProvider
	 */
	public function isValidForValidInputReturnsTrue($input) {
		$this->subject->setFormat($input[0]);
		$this->subject->setFieldName('myDate');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myDate' => $input[1]
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->subject->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidDateProvider
	 */
	public function isValidForInvalidInputReturnsFalse($input) {
		$this->subject->setFormat($input[0]);
		$this->subject->setFieldName('myDate');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myDate' => $input[1]
		));
		$this->subject->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->subject->isValid()
		);
	}
}
