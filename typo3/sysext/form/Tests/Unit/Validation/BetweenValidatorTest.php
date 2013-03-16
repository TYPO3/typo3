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
 * Test case for class \TYPO3\CMS\Form\Validation\BetweenValidator.
 *
 * @author Andreas Lappe <a.lappe@kuehlhaus.com>
 */
class BetweenValidatorTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {
	/**
	 * @var \TYPO3\CMS\Form\Tests\Unit\Validation\Helper
	 */
	protected $helper;

	/**
	 * @var \TYPO3\CMS\Form\Validation\BetweenValidator
	 */
	protected $fixture;

	public function setUp() {
		$this->helper = new \TYPO3\CMS\Form\Tests\Unit\Validation\Helper();
		$this->fixture = new \TYPO3\CMS\Form\Validation\BetweenValidator(array());
	}

	public function tearDown() {
		unset($this->helper, $this->fixture);
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
		$this->fixture->setMinimum($input[0]);
		$this->fixture->setMaximum($input[2]);
		$this->fixture->setFieldName('numericValue');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'numericValue' => $input[1]
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider validInclusiveDataProvider
	 */
	public function isValidWithValidInputAndWithInclusiveReturnsTrue($input) {
		$this->fixture->setMinimum($input[0]);
		$this->fixture->setMaximum($input[2]);
		$this->fixture->setFieldName('numericValue');
		$this->fixture->setInclusive(TRUE);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'numericValue' => $input[1]
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidNonInclusiveDataProvider
	 */
	public function isValidWithInvalidInputAndWithoutInclusiveReturnsFalse($input) {
		$this->fixture->setMinimum($input[0]);
		$this->fixture->setMaximum($input[2]);
		$this->fixture->setFieldName('numericValue');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'numericValue' => $input[1]
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidInclusiveDataProvider
	 */
	public function isValidWithInvalidInputAndWithInclusiveReturnsFalse($input) {
		$this->fixture->setMinimum($input[0]);
		$this->fixture->setMaximum($input[2]);
		$this->fixture->setFieldName('numericValue');
		$this->fixture->setInclusive(TRUE);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'numericValue' => $input[1]
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->fixture->isValid()
		);
	}
}
?>