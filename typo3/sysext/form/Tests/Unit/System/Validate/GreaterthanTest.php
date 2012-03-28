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
 * Test case for class tx_form_System_Validate_Greaterthan.
 *
 * @author Andreas Lappe <a.lappe@kuehlhaus.com>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_System_Validate_GreaterthanTest extends Tx_Extbase_Tests_Unit_BaseTestCase {
	/**
	 * @var tx_form_System_Validate_Helper
	 */
	protected $helper;

	/**
	 * @var tx_form_System_Validate_Greaterthan
	 */
	protected $fixture;

	public function setUp() {
		$this->helper = new tx_form_System_Validate_Helper();
		$this->fixture = new tx_form_System_Validate_Greaterthan(array());
	}

	public function tearDown() {
		unset($this->helper, $this->fixture);
	}

	public function validNumberProvider() {
		return array(
			'13 > 12' => array(array(12, 13)),
		);
	}

	public function invalidNumberProvider() {
		return array(
			'12.1 > 12'  => array(array(12, 12.1)),
			'12 > 12'    => array(array(12, 12)),
			'11.99 > 12' => array(array(12, 11.99))
		);
	}

	/**
	 * @test
	 * @dataProvider validNumberProvider
	 */
	public function isValidForValidInputReturnsTrue($input) {
		$this->fixture->setFieldName('myFile');
		$this->fixture->setMinimum($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myFile' => $input[1]
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertTrue(
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider invalidNumberProvider
	 */
	public function isValidForInvalidInputReturnsFalse($input) {
		$this->fixture->setFieldName('myFile');
		$this->fixture->setMinimum($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myFile' => $input[1]
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertFalse(
			$this->fixture->isValid()
		);
	}
}
?>