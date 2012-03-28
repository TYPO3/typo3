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
 * Test case for class tx_form_System_Validate_Filemaximumsize.
 *
 * @author Andreas Lappe <a.lappe@kuehlhaus.com>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_System_Validate_FilemaximumsizeTest extends Tx_Extbase_Tests_Unit_BaseTestCase {
	/**
	 * @var tx_form_System_Validate_Helper
	 */
	protected $helper;

	/**
	 * @var tx_form_System_Validate_Filemaximumsize
	 */
	protected $fixture;

	public function setUp() {
		$this->helper = new tx_form_System_Validate_Helper();
		$this->fixture = new tx_form_System_Validate_Filemaximumsize();
	}

	public function tearDown() {
		unset($this->helper);
		unset($this->fixture);
	}

	public function validSizesProvider() {
		return array(
			'11B for max. 12B' => array(array(12, 11), TRUE),
			'12B for max. 12B' => array(array(12, 12), TRUE)
		);
	}

	public function invalidSizesProvider() {
		return array(
			'12B for max. 11B' => array(array(11, 12), FALSE)
		);
	}

	/**
	 * @test
	 * @dataProvider validSizesProvider
	 */
	public function isValidForValidInputReturnsTrue($input, $expected) {
		$this->fixture->setFieldName('myFile');
		$this->fixture->setMaximum($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myFile' => array('size' => $input[1])
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			$expected,
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 * @dataProvider inValidSizesProvider
	 */
	public function isValidForInvalidInputReturnsFalse($input, $expected) {
		$this->fixture->setFieldName('myFile');
		$this->fixture->setMaximum($input[0]);
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'myFile' => array('size' => $input[1])
		));
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			$expected,
			$this->fixture->isValid()
		);
	}
}
?>