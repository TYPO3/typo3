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
 * Test case for class tx_form_System_Validate_Alphanumeric.
 *
 * @author Andreas Lappe <a.lappe@kuehlhaus.com>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_System_Validate_AlphanumericTest extends Tx_Extbase_Tests_Unit_BaseTestCase {
	/**
	 * @var tx_form_System_Validate_Helper
	 */
	protected $helper;

	/**
	 * @var tx_form_System_Validate_Alphanumeric
	 */
	protected $fixture;

	public function setUp() {
		$this->helper = new tx_form_System_Validate_Helper();
		$this->fixture = new tx_form_System_Validate_Alphanumeric();
	}

	public function tearDown() {
		unset($this->helper);
		unset($this->fixture);
	}

	public function validDataProviderWithoutWhitespace() {
		return array(
			'ascii without spaces' => array('thisismyinput4711', TRUE),
			'accents without spaces' => array('éóéàèò4711', TRUE),
			'umlauts without spaces' => array('üöä4711', TRUE),
			'empty string' => array('', TRUE)
		);
	}

	public function validDataProviderWithWhitespace() {
		return array(
			'ascii with spaces' => array('This is my input 4711', TRUE),
			'accents with spaces' => array('Sigur Rós 4711', TRUE),
			'umlauts with spaces' => array('Hürriyet Daily News 4711', TRUE),
			'space' => array(' ', TRUE),
			'empty string' => array('', TRUE)
		);
	}

	public function invalidDataProviderWithoutWhitespace() {
		return array(
			'ascii with dash' => array('my-name-4711', FALSE),
			'accents with underscore' => array('Sigur_Rós_4711', FALSE),
			'umlauts with periods' => array('Hürriyet.Daily.News.4711', FALSE),
			'space' => array(' ', FALSE),
		);
	}

	public function invalidDataProviderWithWhitespace() {
		return array(
			'ascii with spaces and dashes' => array('This is my-name 4711', FALSE),
			'accents with spaces and underscores' => array('Listen to Sigur_Rós_Band 4711', FALSE),
			'umlauts with spaces and periods' => array('Go get the Hürriyet.Daily.News 4711', FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validDataProviderWithoutWhitespace
	 */
	public function isValidForValidInputWithoutAllowedWhitespaceReturnsTrue($input, $expected) {
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'name' => $input
		));

		$this->fixture->setFieldName('name');
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			$expected,
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider validDataProviderWithWhitespace
	 */
	public function isValidForValidInputWithAllowedWhitespaceReturnsTrue($input, $expected) {
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'name' => $input
		));

		$this->fixture->setAllowWhiteSpace(TRUE);
		$this->fixture->setFieldName('name');
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			$expected,
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidDataProviderWithoutWhitespace
	 */
	public function isValidForInvalidInputWithoutAllowedWhitespaceReturnsFalse($input, $expected) {
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'name' => $input
		));

		$this->fixture->setFieldName('name');
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			$expected,
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidDataProviderWithWhitespace
	 */
	public function isValidForInvalidInputWithAllowedWhitespaceReturnsFalse($input, $expected) {
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'name' => $input
		));

		$this->fixture->setAllowWhiteSpace(TRUE);
		$this->fixture->setFieldName('name');
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			$expected,
			$this->fixture->isValid()
		);
	}
}
?>