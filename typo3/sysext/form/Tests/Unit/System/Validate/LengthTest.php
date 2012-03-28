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
		$this->fixture = new tx_form_System_Validate_Length(array('minimum' => 0, 'maximum' => 0));
	}

	public function tearDown() {
		unset($this->helper, $this->fixture);
	}

	public function validLengthProvider() {
		return array(
			'4 ≤ length(myString) ≤ 8' => array(array(4, 8, 'myString')),
			'8 ≤ length(myString) ≤ 8' => array(array(8, 8, 'myString')),
			'4 ≤ length(myString)'       => array(array(4, NULL, 'myString')),
			'4 ≤ length(asdf) ≤ 4'     => array(array(4, 4, 'asdf')),
		);
	}

	public function invalidLengthProvider() {
		return array(
			'4 ≤ length(my) ≤ 12'             => array(array(4, 12, 'my')),
			'4 ≤ length(my long string) ≤ 12' => array(array(4, 12, 'my long string')),
		);
	}

	/**
	 * @test
	 * @dataProvider validLengthProvider
	 */
	public function isValidForValidInputReturnsTrue($input) {
		$this->fixture->setFieldName('myLength');
		$this->fixture->setMinimum($input[0]);
		$this->fixture->setMaximum($input[1]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myLength' => $input[2]
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidLengthProvider
	 */
	public function isValidForInvalidInputReturnsFalse($input) {
		$this->fixture->setFieldName('myLength');
		$this->fixture->setMinimum($input[0]);
		$this->fixture->setMaximum($input[1]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myLength' => $input[2]
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->fixture->isValid()
		);
	}
}
?>