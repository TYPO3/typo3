<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\ImportExport;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Marc Bastian Heinrichs <typo3@mbh-software.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * Functional test for the ImportExport
 */
class ImportSimpleTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	/**
	 * @var array
	 */
	protected $coreExtensionsToLoad = array('impexp');

	public function setUp() {
		parent::setUp();
		$this->setUpBackendUserFromFixture(1);
		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();
	}

	/**
	 * @test
	 */
	public function canImportSimplePagesAndRelatedTtContent() {
		/** @var $import \TYPO3\CMS\Impexp\ImportExport */
		$import = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Impexp\\ImportExport');
		$import->init(0, 'import');
		$import->loadFile(__DIR__ . '/../Fixtures/ImportExport/pages-and-ttcontent.xml', 1);
		$import->importData(0);

		$database = $this->getDatabase();

		$row = $database->exec_SELECTgetSingleRow('*', 'pages', 'uid = 1');
		$this->assertNotEmpty($row);
		$this->assertEquals(0, $row['pid']);
		$this->assertEquals('Root', $row['title']);

		$row = $database->exec_SELECTgetSingleRow('*', 'pages', 'uid = 2');
		$this->assertNotEmpty($row);
		$this->assertEquals(1, $row['pid']);
		$this->assertEquals('Dummy 1-2', $row['title']);

		$row = $database->exec_SELECTgetSingleRow('*', 'tt_content', 'uid = 1');
		$this->assertNotEmpty($row);
		$this->assertEquals(1, $row['pid']);
		$this->assertEquals('Test content', $row['header']);
	}

}