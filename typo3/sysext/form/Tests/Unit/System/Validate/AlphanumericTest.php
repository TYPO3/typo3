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


	/**
	 * @test
	 */
	public function isValidForAlphanumericStringWithSpacesAndNoWhitespaceAllowedReturnsFalse() {
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'name' => 'This is my input 4711'
		));

		$this->fixture->setFieldName('name');
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			FALSE,
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 */
	public function isValidForAlphanumericStringWithSpacesAndWhitespaceAllowedReturnsTrue() {
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'name' => 'This is my input 4711'
		));

		$this->fixture->setAllowWhiteSpace(TRUE);
		$this->fixture->setFieldName('name');
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			TRUE,
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 */
	public function isValidForAlphanumericStringWithNonAsciiCharactersReturnsTrue() {
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'name' => 'Sigur Rós 4711'
		));

		$this->fixture->setAllowWhiteSpace(TRUE);
		$this->fixture->setFieldName('name');
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			TRUE,
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 */
	public function isValidForEmptyStringReturnsFalse() {
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'name' => ''
		));

		$this->fixture->setAllowWhiteSpace(FALSE);
		$this->fixture->setFieldName('name');
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			FALSE,
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 */
	public function isValidForSpaceAndWhitespaceAllowedReturnsTrue() {
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'name' => ' '
		));

		$this->fixture->setAllowWhiteSpace(TRUE);
		$this->fixture->setFieldName('name');
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			TRUE,
			$this->fixture->isValid()
		);
	}

	/**
	 * @test
	 */
	public function isValidForSpaceAndWhitespaceAllowedReturnsFalse() {
		$requestHandlerMock = $this->helper->getRequestHandler(array(
			'name' => ' '
		));

		$this->fixture->setAllowWhiteSpace(FALSE);
		$this->fixture->setFieldName('name');
		$this->fixture->injectRequestHandler($requestHandlerMock);

		$this->assertSame(
			FALSE,
			$this->fixture->isValid()
		);
	}
}
?>