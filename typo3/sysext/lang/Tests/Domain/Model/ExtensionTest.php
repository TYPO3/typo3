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
 * Test case for class Tx_Lang_Domain_Model_Extension
 *
 * @author Wouter Wolters <typo3@wouterwolters.nl>
 * @package TYPO3
 * @subpackage lang
 */
class Tx_Lang_Domain_Model_ExtensionTest extends Tx_Extbase_Tests_Unit_BaseTestCase {

	/**
	 * @var Tx_Lang_Domain_Model_Extension
	 */
	protected $fixture = NULL;

	public function setUp() {
		$this->fixture = new Tx_Lang_Domain_Model_Extension();
	}

	public function tearDown() {
		unset($this->fixture);
	}

	/**
	 * @test
	 */
	public function getKeyInitiallyReturnsEmptyString() {
		$this->fixture = new Tx_Lang_Domain_Model_Extension();

		$this->assertSame(
			'',
			$this->fixture->getKey()
		);
	}

	/**
	 * @test
	 */
	public function getKeyInitiallyReturnsGivenIconFromConstruct() {
		$key = 'foo bar';
		$this->fixture = new Tx_Lang_Domain_Model_Extension($key);

		$this->assertSame(
			$key,
			$this->fixture->getKey()
		);
	}

	/**
	 * @test
	 */
	public function setKeySetsKey() {
		$key = 'foo bar';
		$this->fixture->setKey($key);

		$this->assertSame(
			$key,
			$this->fixture->getKey()
		);
	}

	/**
	 * @test
	 */
	public function getTitleInitiallyReturnsEmptyString() {
		$this->fixture = new Tx_Lang_Domain_Model_Extension();

		$this->assertSame(
			'',
			$this->fixture->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function getTitleInitiallyReturnsGivenTitleFromConstruct() {
		$title = 'foo bar';
		$this->fixture = new Tx_Lang_Domain_Model_Extension('', $title);

		$this->assertSame(
			$title,
			$this->fixture->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function setTitleSetsTitle() {
		$title = 'foo bar';
		$this->fixture->setTitle($title);

		$this->assertSame(
			$title,
			$this->fixture->getTitle()
		);
	}

	/**
	 * @test
	 */
	public function getIconInitiallyReturnsEmptyString() {
		$this->fixture = new Tx_Lang_Domain_Model_Extension();

		$this->assertSame(
			'',
			$this->fixture->getIcon()
		);
	}

	/**
	 * @test
	 */
	public function getIconInitiallyReturnsGivenIconFromConstruct() {
		$icon = 'foo bar';
		$this->fixture = new Tx_Lang_Domain_Model_Extension('', '', $icon);

		$this->assertSame(
			$icon,
			$this->fixture->getIcon()
		);
	}

	/**
	 * @test
	 */
	public function setIconSetsIcon() {
		$icon = 'foo bar';
		$this->fixture->setIcon($icon);

		$this->assertSame(
			$icon,
			$this->fixture->getIcon()
		);
	}

	/**
	 * @test
	 */
	public function getVersionInitiallyReturnsEmptyString() {
		$this->fixture = new Tx_Lang_Domain_Model_Extension();

		$this->assertSame(
			'',
			$this->fixture->getVersion()
		);
	}

	/**
	 * @test
	 */
	public function setVersionSetsVersion() {
		$version = 10;
		$this->fixture->setVersion($version);

		$this->assertSame(
			$version,
			$this->fixture->getVersion()
		);
	}

	/**
	 * @test
	 */
	public function setVersionSetsVersionFromString() {
		$version = 4012003;
		$this->fixture->setVersionFromString('4.12.3');

		$this->assertSame(
			$version,
			$this->fixture->getVersion()
		);
	}

	/**
	 * @test
	 */
	public function getUpdateResultInitiallyReturnsEmptyArray() {
		$this->fixture = new Tx_Lang_Domain_Model_Extension();

		$this->assertSame(
			array(),
			$this->fixture->getUpdateResult()
		);
	}

	/**
	 * @test
	 */
	public function setUpdateResultSetsUpdateResult() {
		$updateResult = array(
			'nl' => array(
				'icon' => '<span class="t3-icon t3-icon-actions t3-icon-actions-document t3-icon-document-info">&nbsp;</span>',
				'message' => 'translation_n_a'
			),
		);

		$this->fixture->setUpdateResult($updateResult);

		$this->assertSame(
			$updateResult,
			$this->fixture->getUpdateResult()
		);
	}

}
?>