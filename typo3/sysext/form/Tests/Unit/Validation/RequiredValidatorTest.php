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
 * Test case for class \TYPO3\CMS\Form\Validation\RequiredValidator.
 *
 * @author Andreas Lappe <a.lappe@kuehlhaus.com>
 */
class RequiredValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {
	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\RequiredValidator
	 */
	protected $fixture;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->fixture = new \TYPO3\CMS\Form\Validation\RequiredValidator(array());
	}

	public function tearDown() {
		unset($this->helper, $this->fixture);
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
		$this->fixture->setFieldName('myRequired');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myRequired' => $input
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidDataProvider
	 */
	public function isValidForInvalidDataReturnsFalse($input) {
		$this->fixture->setFieldName('myRequired');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myRequired' => $input
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->fixture->isValid()
		);
	}
}
?>