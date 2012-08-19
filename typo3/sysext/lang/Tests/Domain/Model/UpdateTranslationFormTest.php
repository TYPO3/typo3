<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Wouter Wolters <typo3@wouterwolters.nl>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test case for class Tx_Lang_Domain_Model_UpdateTranslationForm
 *
 * @author Wouter Wolters <typo3@wouterwolters.nl>
 * @package TYPO3
 * @subpackage lang
 */
class Tx_Lang_Domain_Model_UpdateTranslationFormTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Lang_Domain_Model_UpdateTranslationForm
	 */
	protected $fixture = NULL;

	public function setUp() {
		$this->fixture = new Tx_Lang_Domain_Model_UpdateTranslationForm();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function getSelectedLanguagesInitiallyReturnsEmptyArray() {
		$this->fixture = new Tx_Lang_Domain_Model_UpdateTranslationForm();

		$this->assertSame(
			array(),
			$this->fixture->getSelectedLanguages()
		);
	}

	/**
	 * @test
	 */
	public function setSelectedLanguagesSetsSelectedLanguages() {
		$languages = array(
			'nl',
			'de',
		);
		$this->fixture->setSelectedLanguages($languages);

		$this->assertSame(
			$languages,
			$this->fixture->getSelectedLanguages()
		);
	}

	/**
	 * @test
	 */
	public function getExtensionsInitiallyReturnsEmptyArray() {
		$this->fixture = new Tx_Lang_Domain_Model_UpdateTranslationForm();

		$this->assertSame(
			array(),
			$this->fixture->getExtensions()
		);
	}

	/**
	 * @test
	 */
	public function setExtensionsSetsExtensions() {
		$extensions = array(
			1 => 'about',
			2 => 'aboutmodules',
			3 => 'adodb',
		);
		$this->fixture->setExtensions($extensions);

		$this->assertSame(
			$extensions,
			$this->fixture->getExtensions()
		);
	}
}
?>