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
 * Test case for class tx_form_System_Validate_Length.
 *
 * @author Andreas Lappe <a.lappe@kuehlhaus.com>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_System_Validate_LengthTest extends Tx_Extbase_Tests_Unit_BaseTestCase {
	/**
	 * @var tx_form_System_Validate_Helper
	 */
	protected $helper;

	/**
	 * @var tx_form_System_Validate_Length
	 */
	protected $fixture;

	public function setUp() {
		$this->helper = new tx_form_System_Validate_Helper();
		$this->fixture = new tx_form_System_Validate_Length();
	}

	public function tearDown() {
		unset($this->helper);
		unset($this->fixture);
	}

	public function validLengthProvider() {
		return array(
			'4 ≤ length(myString) ≤ 8' => array(array(4, 8, 'myString'), TRUE),
			'8 ≤ length(myString) ≤ 8' => array(array(8, 8, 'myString'), TRUE),
			'4 ≤ length(myString)'       => array(array(4, NULL, 'myString'), TRUE),
			'4 ≤ length(asdf) ≤ 4'     => array(array(4, 4, 'asdf'), TRUE),
			'4 ≤ length(äüöß) ≤ 4' => array(array(4, 4, 'äüöß'), TRUE),
			'4 ≤ length(øüß¬) ≤ 4' => array(array(4, 4, 'øüß¬'), TRUE),
		);
	}

	public function invalidLengthProvider() {
		return array(
			'4 ≤ length(my) ≤ 12'             => array(array(4, 12, 'my'), FALSE),
			'4 ≤ length(my long string) ≤ 12' => array(array(4, 12, 'my long string'), FALSE),
		);
	}

	/**
	 * @test
	 * @dataProvider validLengthProvider
	 */
	public function isValidForValidInputReturnsTrue($input, $expected) {
		$this->fixture->setFieldName('myLength');
		$this->fixture->setMinimum($input[0]);
		$this->fixture->setMaximum($input[1]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myLength' => $input[2]
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			$expected,
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidLengthProvider
	 */
	public function isValidForInvalidInputReturnsFalse($input, $expected) {
		$this->fixture->setFieldName('myLength');
		$this->fixture->setMinimum($input[0]);
		$this->fixture->setMaximum($input[1]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myLength' => $input[2]
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			$expected,
			$this->fixture->isValid()
		);
	}
}
?>