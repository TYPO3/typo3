<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Andreas Lappe <a.lappe@kuehlhaus.com>, kuehlhaus AG
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

require_once('Helper.php');

/**
 * Test case for class tx_form_System_Validate_Between.
 *
 * @author Andreas Lappe <a.lappe@kuehlhaus.com>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_System_Validate_BetweenTest extends Tx_Extbase_Tests_Unit_BaseTestCase {
	/**
	 * @var tx_form_System_Validate_Helper
	 */
	protected $helper;

	/**
	 * @var tx_form_System_Validate_Between
	 */
	protected $fixture;

	public function setUp() {
		$this->helper = new tx_form_System_Validate_Helper();
		$this->fixture = new tx_form_System_Validate_Between();
	}

	public function tearDown() {
		unset($this->helper);
		unset($this->fixture);
	}

	public function nonInclusiveProvider() {
			// input is array(min, value, max)
		return array(
			array(array(3, 5, 7), TRUE),
			array(array(0, 10, 20), TRUE),
			array(array(-10, 0, 10), TRUE),
			array(array(-20, -10, 0), TRUE),
			array(array(1, 2, 3), TRUE),
			array(array(1, 1.01, 1.1), TRUE),

			array(array(1, 1, 2), FALSE),
			array(array(1, 2, 2), FALSE),
			array(array(1.1, 1.1, 1.2), FALSE),
			array(array(1.1, 1.2, 1.2), FALSE),
			array(array(-10.1234, -10.12340, 10), FALSE),

				// Nonsense input
			array(array(100, 0, -100), FALSE)
		);
	}

	public function inclusiveProvider() {
			// input is array(min, value, max)
		return array(
			array(array(1,1,1), TRUE),
			array(array(-10.1234, -10.12345, 10), FALSE),
			array(array(-10.1234, -10.12340, 10), TRUE),

				// Nonsense input
			array(array(100, 0, -100), FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider nonInclusiveProvider
	 */
	public function isValidWithNonInclusiveReturnsExpectedValue($input, $expected) {
		$this->fixture->setMinimum($input[0]);
		$this->fixture->setMaximum($input[2]);
		$this->fixture->setFieldName('numericValue');
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'numericValue' => $input[1]
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			$expected,
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider inclusiveProvider
	 */
	public function isValidWithInclusiveReturnsExpectedValue($input, $expected) {
		$this->fixture->setMinimum($input[0]);
		$this->fixture->setMaximum($input[2]);
		$this->fixture->setFieldName('numericValue');
		$this->fixture->setInclusive(TRUE);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'numericValue' => $input[1]
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			$expected,
			$this->fixture->isValid()
		);
	}
}
?>