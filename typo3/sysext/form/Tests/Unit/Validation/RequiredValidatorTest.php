<?php
namespace TYPO3\CMS\Form\Tests\Unit\Validation;
/***************************************************************
*  Copyright notice
*
*  (c) 2012-2013 Andreas Lappe <a.lappe@kuehlhaus.com>, kuehlhaus AG
*
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

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
