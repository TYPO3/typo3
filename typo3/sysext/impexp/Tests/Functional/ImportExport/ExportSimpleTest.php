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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Functional test for the ImportExport
 */
class ExportSimpleTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	/**
	 * @var array
	 */
	protected $coreExtensionsToLoad = array('impexp');

	public function setUp() {
		parent::setUp();
		$this->setUpBackendUserFromFixture(1);
		// Needed to avoid PHP Warnings
		$GLOBALS['TBE_STYLES']['spriteIconApi']['iconsAvailable'] = array();
		$this->importDataSet(__DIR__ . '/../Fixtures/Database/pages.xml');
		$this->importDataSet(__DIR__ . '/../Fixtures/Database/tt_content.xml');
	}

	/**
	 * @test
	 */
	public function canExportSimplePagesAndRelatedTtContent() {
		$permsClause = $GLOBALS['BE_USER']->getPagePermsClause(1);

		/** @var $export \TYPO3\CMS\Impexp\ImportExport */
		$export = GeneralUtility::makeInstance('TYPO3\\CMS\\Impexp\\ImportExport');
		$export->init();

		$export->export_addRecord('pages', BackendUtility::getRecord('pages', 1));
		$export->export_addRecord('pages', BackendUtility::getRecord('pages', 2));
		$export->export_addRecord('tt_content', BackendUtility::getRecord('tt_content', 1));

		$pidToStart = 1;
		/** @var $tree \TYPO3\CMS\Backend\Tree\View\PageTreeView */
		$tree = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\View\\PageTreeView');
		$tree->init('AND ' . $permsClause);
		$tree->tree[] = array('row' => $pidToStart);
		$tree->buffer_idH = array();
		$tree->getTree(1, 1, '');

		$idH = array();
		$idH[$pidToStart]['uid'] = $pidToStart;
		if (count($tree->buffer_idH)) {
			$idH[$pidToStart]['subrow'] = $tree->buffer_idH;
		}
		$export->setPageTree($idH);

		// After adding ALL records we set relations:
		for ($a = 0; $a < 10; $a++) {
			$addR = $export->export_addDBRelations($a);
			if (!count($addR)) {
				break;
			}
		}

		$export->export_addFilesFromRelations();

		$out = $export->compileMemoryToFileContent('xml');

		$this->assertXmlStringEqualsXmlFile(__DIR__ . '/../Fixtures/ImportExport/pages-and-ttcontent.xml', $out);
	}

}