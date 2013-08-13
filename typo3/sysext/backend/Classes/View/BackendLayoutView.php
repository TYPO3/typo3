<?php
namespace TYPO3\CMS\Backend\View;
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 GridView Team
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Backend layout class
 *
 * @author GridView Team
 * @author Arno Dudek <webmaster@adgrafik.at>
 */
class BackendLayoutView implements SingletonInterface {

	/**
	 * @var integer $pageUid
	 */
	protected $pageUid;

	/**
	 * @var array $setup
	 */
	protected $setup;

	/**
	 * @var mixed $selectedBackendLayoutId: Id of the backend layout or FALSE if no layout is set.
	 */
	protected $selectedBackendLayoutId;

	/**
	 * @var array $backendLayoutColPos
	 */
	protected $backendLayoutColPos;

	/**
	 * Constructor
	 *
	 * @param integer $pageUid
	 */
	public function __construct($pageUid = 0) {
		$this->pageUid = (integer) $pageUid;
		$this->loadSetup();
	}

	/**
	 * Returns the grid layout setup.
	 *
	 * @return array
	 */
	public function getSetup() {
		return $this->setup;
	}

	/**
	 * Returns the grid layout setup by layout ID.
	 *
	 * @param string $layoutId: The backend layout ID.
	 * @param string $fieldName: Current field.
	 * @return array
	 */
	public function getSetupByLayoutId($layoutId, $fieldName = NULL) {

		$fieldName = ($fieldName === NULL) ? 'backend_layout' : $fieldName;

		if (isset($this->setup[$fieldName][$layoutId])) {
			return $this->setup[$fieldName][$layoutId];
		}
	}

	/**
	 * Returns the backend layout which should be used for this page.
	 *
	 * @return mixed Uid of the backend layout record or NULL if no layout should be used
	 */
	public function getSelectedBackendLayoutId() {

		if ($this->selectedBackendLayoutId === NULL) {
			$page = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid, pid, backend_layout', 'pages', 'uid=' . $this->pageUid);
			BackendUtility::workspaceOL('pages', $page);

			$this->selectedBackendLayoutId = $page['backend_layout'];
			if ($this->selectedBackendLayoutId == -1) {
				// If it is set to "none" - don't use any
				$this->selectedBackendLayoutId = FALSE;
			} elseif ($this->selectedBackendLayoutId == 0) {
				// If it not set check the rootline for a layout on next level and use this
				$rootline = BackendUtility::BEgetRootLine($this->pageUid, '', TRUE);
				for ($i = count($rootline) - 2; $i > 0; $i--) {
					$this->selectedBackendLayoutId = $rootline[$i]['backend_layout_next_level'];
					if ($this->selectedBackendLayoutId > 0) {
						// Stop searching if a layout for "next level" is set
						break;
					} elseif ($this->selectedBackendLayoutId == -1){
						// If layout for "next level" is set to "none" - don't use any and stop searching
						$this->selectedBackendLayoutId = FALSE;
						break;
					}
				}
			}
		}
		// If it is set to a positive value use this
		return $this->selectedBackendLayoutId;
	}

	/**
	 * Returns the grid layout setup of selected backend layout.
	 *
	 * @param integer $id
	 * @return array
	 */
	public function getSelectedBackendLayout($id) {
		$this->pageUid = $id;
		return $this->getSelectedBackendLayoutSetup();
	}

	/**
	 * Returns the grid layout setup of selected backend layout.
	 *
	 * @return array
	 */
	public function getSelectedBackendLayoutSetup() {
		return $this->getSetupByLayoutId($this->getSelectedBackendLayoutId());
	}

	/**
	 * Gets the selected backend layout columns
	 *
	 * @return array|NULL  $backendLayout
	 */
	public function getSelectedBackendLayoutColPosItems() {
		if ($this->backendLayoutColPos === NULL) {
			$this->parseBackendLayoutColPos();
		}
		return $this->backendLayoutColPos['items'];
	}

	/**
	 * Gets the selected backend layout
	 *
	 * @return array|NULL  $backendLayout
	 */
	public function getSelectedBackendLayoutColPosList() {
		if ($this->backendLayoutColPos === NULL) {
			$this->parseBackendLayoutColPos();
		}
		return $this->backendLayoutColPos['list'];
	}

	/**
	 * Gets the list of available columns for a given page id
	 *
	 * @return array $tcaItems
	 */
	public function getColPosListItemsParsed() {
		$tsConfig  = BackendUtility::getModTSconfig($this->pageUid, 'TCEFORM.tt_content.colPos');
		$tcaConfig = $GLOBALS['TCA']['tt_content']['columns']['colPos']['config'];
		$tceForms = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\FormEngine');
		$tcaItems = $tcaConfig['items'];
		$tcaItems = $tceForms->addItems($tcaItems, $tsConfig['properties']['addItems.']);
		if (isset($tcaConfig['itemsProcFunc']) && $tcaConfig['itemsProcFunc']) {
			$this->addColPosSelectItems($tcaItems);
		}
		foreach (GeneralUtility::trimExplode(',', $tsConfig['properties']['removeItems'], 1) as $removeId) {
			foreach ($tcaItems as $key => $item) {
				if ($item[1] == $removeId) {
					unset($tcaItems[$key]);
				}
			}
		}
		return $tcaItems;
	}

	/**
	 * Returns the item array for form field selection.
	 *
	 * @param array &$items: Select items
	 * @param string $fieldName: Current field
	 * @return void
	 */
	public function addBackendLayoutSelectItems(&$items, $fieldName) {

		foreach ($this->setup[$fieldName] as $backendLayoutId => $item) {
			if ($backendLayoutId > 0) {
				$items[] = array(
					$GLOBALS['LANG']->sL($item['title']),
					$backendLayoutId,
					$item['icon'],
				);
			}
		}
	}

	/**
	 * Adds items to a colpos list
	 *
	 * @param array &$items: Select items
	 * @return void
	 */
	public function addColPosSelectItems(&$items) {
		// if setup found reset items
		if (($colPosItems = $this->getSelectedBackendLayoutColPosItems()) !== NULL) {
			$items = $colPosItems;
		}
	}

	/**
	 * Returns the page TSconfig merged with the grid layout records.
	 *
	 * @return BackendLayoutView
	 */
	protected function loadSetup() {
		// Load page TSconfig.
		$pageTSconfig = BackendUtility::getPagesTSconfig($this->pageUid);
		$storagePid = $this->getStoragePid($pageTSconfig);
		$pageTSconfigId = $this->getPageTSconfigId($pageTSconfig);
		$exclude = $this->getExcludedLayouts($pageTSconfig);
		$overruleRecords = $this->isOverruleRecords($pageTSconfig);
		$backendLayoutConfig = array();
		if (isset($pageTSconfig['BackendLayoutView.']) && isset($pageTSconfig['BackendLayoutView.']['setup.']) && is_array($pageTSconfig['BackendLayoutView.']['setup.'])) {
			foreach ($pageTSconfig['BackendLayoutView.']['setup.'] as $layoutId => $item) {
				// remove trailing dot of layout ID
				$layoutId = rtrim($layoutId, '.');
				// continue if layout is excluded
				if (in_array($layoutId, $exclude, TRUE)) {
					continue;
				}
				// parse icon path
				$item['icon'] = (strpos($item['icon'], 'EXT:') === 0)
					? str_replace(PATH_site, '../', GeneralUtility::getFileAbsFileName($item['icon']))
					: $item['icon'];
				// remove trailing dot of config
				if (isset($item['config.'])) {
					$item['config'] = $item['config.'];
					unset($item['config.']);
				}
				$backendLayoutConfig[$layoutId] = $item;
			}
		}
		$backendLayoutRecords = array();
		foreach (array('backend_layout', 'backend_layout_next_level') as $fieldName) {
			$backendLayoutRecords[$fieldName] = array();
			// Load records.
			$result = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
				'*',
				'backend_layout',
				'(
					( ' . $pageTSconfigId[$fieldName] . ' = 0 AND ' . $storagePid . ' = 0 )
					OR ( backend_layout.pid = ' . intval($pageTSconfigId[$fieldName]) . ' OR backend_layout.pid = ' . intval($storagePid) . ' )
					OR ( ' . $pageTSconfigId[$fieldName] . ' = 0 AND backend_layout.pid = ' . intval($this->pageUid) . ' )
				)' . BackendUtility::BEenableFields('backend_layout'),
				'',
				'sorting ASC',
				'',
				'uid'
			);
			foreach ($result as $layoutId => $item) {
				// continue if layout is excluded
				if (in_array((string) $layoutId, $exclude, TRUE)) {
					continue;
				}
				// prepend icon path for records
				$item['icon'] = $item['icon']
					? '../' . $GLOBALS['TCA']['backend_layout']['ctrl']['selicon_field_path'] . '/' . htmlspecialchars($item['icon'])
					: '';
				// parse config
				if ($item['config']) {
					$parser = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
					$parser->parse($item['config']);
					if (isset($parser->setup['backend_layout.'])) {
						$item['config'] = $parser->setup['backend_layout.'];
					}
				}
				$backendLayoutRecords[$fieldName][$layoutId] = $item;
			}
		}
		if ($overruleRecords === TRUE) {
			$this->setup = array(
				'backend_layout' => GeneralUtility::array_merge_recursive_overrule($backendLayoutConfig, $backendLayoutRecords['backend_layout']),
				'backend_layout_next_level' => GeneralUtility::array_merge_recursive_overrule($backendLayoutConfig, $backendLayoutRecords['backend_layout_next_level']),
			);
		} else {
			$this->setup = array(
				'backend_layout' => GeneralUtility::array_merge_recursive_overrule($backendLayoutRecords['backend_layout'], $backendLayoutConfig),
				'backend_layout_next_level' => GeneralUtility::array_merge_recursive_overrule($backendLayoutRecords['backend_layout_next_level'], $backendLayoutConfig),
			);
		}
		return $this;
	}

	/**
	 * Returns the storage PID from TCEFORM.
	 *
	 * @param array $pageTSconfig
	 * @return array
	 */
	protected function getStoragePid($pageTSconfig) {
		$storagePid = isset($pageTSconfig['TCEFORM.']['pages.']['_STORAGE_PID'])
			? $pageTSconfig['TCEFORM.']['pages.']['_STORAGE_PID']
			: 0;
		return $storagePid;
	}

	/**
	 * Returns the page TSconfig from TCEFORM.
	 *
	 * @param array $pageTSconfig
	 * @return array
	 */
	protected function getPageTSconfigId($pageTSconfig) {
		$pageTSconfigId = array(
			'backend_layout' => isset($pageTSconfig['TCEFORM.']['pages.']['backend_layout.']['PAGE_TSCONFIG_ID'])
				? $pageTSconfig['TCEFORM.']['pages.']['backend_layout.']['PAGE_TSCONFIG_ID']
				: 0,
			'backend_layout_next_level' => isset($pageTSconfig['TCEFORM.']['pages.']['backend_layout_next_level.']['PAGE_TSCONFIG_ID'])
				? $pageTSconfig['TCEFORM.']['pages.']['backend_layout_next_level.']['PAGE_TSCONFIG_ID']
				: 0,
		);
		return $pageTSconfigId;
	}

	/**
	 * Returns excluded layouts from TSconfig.
	 *
	 * @param array $pageTSconfig
	 * @return array
	 */
	protected function getExcludedLayouts($pageTSconfig) {
		$exclude = isset($pageTSconfig['BackendLayoutView']['exclude'])
			? GeneralUtility::trimExplode(',', $pageTSconfig['BackendLayoutView']['exclude'])
			: array();
		return $exclude;
	}

	/**
	 * Returns TRUE if overruleRecords is set, else FALSE.
	 *
	 * @param array $pageTSconfig
	 * @return boolean
	 */
	protected function isOverruleRecords($pageTSconfig) {
		return (isset($pageTSconfig['BackendLayoutView']['overruleRecords']) && $pageTSconfig['BackendLayoutView']['overruleRecords'] == '1');
	}

	/**
	 * Parse backend layout columns and provide information about the resulting list of available colPos values.
	 *
	 * @return void
	 */
	protected function parseBackendLayoutColPos() {

		$setup = $this->getSetupByLayoutId($this->getSelectedBackendLayoutId());

		if ($setup) {

			$this->backendLayoutColPos = array(
				'items' => array(),
				'list' => array(),
			);

			foreach ($setup['config']['rows.'] as $row) {
				if (isset($row['columns.']) && is_array($row['columns.'])) {
					foreach ($row['columns.'] as $column) {
						$this->backendLayoutColPos['items'][] = array(
							$GLOBALS['LANG']->sL($column['name']),
							$column['colPos'],
							NULL
						);
						$this->backendLayoutColPos['list'][] = $column['colPos'];
					}
				}
			}
		}
	}
}
?>