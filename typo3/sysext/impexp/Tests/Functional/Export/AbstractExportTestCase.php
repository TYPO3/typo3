<?php
namespace TYPO3\CMS\Impexp\Tests\Functional\Export;

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
abstract class AbstractExportTestCase extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	/**
	 * @var array
	 */
	protected $coreExtensionsToLoad = array('impexp');

	/**
	 * @var \TYPO3\CMS\Impexp\ImportExport
	 */
	protected $export;

	/**
	 * Set up for set up the backend user, initialize the language object
	 * and creating the ImportExport instance
	 *
	 * @return void
	 */
	public function setUp() {
		parent::setUp();

		$this->setUpBackendUserFromFixture(1);
		// Needed to avoid PHP Warnings
		$GLOBALS['TBE_STYLES']['spriteIconApi']['iconsAvailable'] = array();

		\TYPO3\CMS\Core\Core\Bootstrap::getInstance()->initializeLanguageObject();

		$this->export = GeneralUtility::makeInstance('TYPO3\\CMS\\Impexp\\ImportExport');
		$this->export->init(0, 'export');
	}

	/**
	 * Builds a flat array containing the page tree with the PageTreeView
	 * based on given start pid and depth and set it in the ImportExport object.
	 *
	 * @param int $pidToStart
	 * @param int $depth
	 * @return void
	 */
	protected function setPageTree($pidToStart, $depth = 1) {

		$permsClause = $GLOBALS['BE_USER']->getPagePermsClause(1);

		/** @var $tree \TYPO3\CMS\Backend\Tree\View\PageTreeView */
		$tree = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\View\\PageTreeView');
		$tree->init('AND ' . $permsClause);
		$tree->tree[] = array('row' => $pidToStart);
		$tree->buffer_idH = array();
		if ($depth > 0) {
			$tree->getTree($pidToStart, $depth, '');
		}

		$idH[$pidToStart]['uid'] = $pidToStart;
		if (count($tree->buffer_idH)) {
			$idH[$pidToStart]['subrow'] = $tree->buffer_idH;
		}

		$this->export->setPageTree($idH);
	}

	/**
	 * Adds records to the export object for a specific page id.
	 *
	 * @param int $pid Page id for which to select records to add
	 * @param array $tables Array of table names to select from
	 * @return void
	 */
	protected function addRecordsForPid($pid, array $tables) {
		foreach ($GLOBALS['TCA'] as $table => $value) {
			if ($table != 'pages' && (in_array($table, $tables) || in_array('_ALL', $tables))) {
				if ($GLOBALS['BE_USER']->check('tables_select', $table) && !$GLOBALS['TCA'][$table]['ctrl']['is_static']) {
					$orderBy = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ? 'ORDER BY ' . $GLOBALS['TCA'][$table]['ctrl']['sortby'] : $GLOBALS['TCA'][$table]['ctrl']['default_sortby'];
					$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'*',
						$table,
							'pid = ' . (int)$pid . BackendUtility::deleteClause($table),
						'',
						$GLOBALS['TYPO3_DB']->stripOrderBy($orderBy)
					);
					while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
						$this->export->export_addRecord($table, $row);
					}
				}
			}
		}
	}

}