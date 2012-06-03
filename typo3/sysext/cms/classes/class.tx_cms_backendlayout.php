<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2012 GridView Team
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

class tx_cms_BackendLayout implements t3lib_Singleton {

	/*
	 * @var array $setup
	 */
	protected $pageUid;

	/*
	 * @var array $setup
	 */
	protected $setup;

	/*
	 * @var mixed $selectedBackendLayoutId: Id of the backend layout or FALSE if no layout is set.
	 */
	protected $selectedBackendLayoutId;

	/*
	 * @var array $backendLayoutColPos
	 */
	protected $backendLayoutColPos;

	/**
	 * Constructor
	 *
	 * @return	void
	 */
	function __construct($pageUid = 0) {

		$this->pageUid = intval($pageUid);
		$this->loadSetup();
	}

	/**
	 * Returns the grid layout setup.
	 *
	 * @author Arno Dudek <webmaster@adgrafik.at>
	 * @return array
	 */
	public function getSetup() {
		return $this->setup;
	}

	/**
	 * Returns the grid layout setup by layout ID.
	 *
	 * @author Arno Dudek <webmaster@adgrafik.at>
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
			// uid, pid, t3ver_swapmode needed for workspaceOL()
			$page = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid, pid, t3ver_swapmode, backend_layout', 'pages', 'uid=' . $this->pageUid);
			t3lib_BEfunc::workspaceOL('pages', $page);

			$this->selectedBackendLayoutId = $page['backend_layout'];
			if ($this->selectedBackendLayoutId == -1) {
					// if it is set to "none" - don't use any
				$this->selectedBackendLayoutId = FALSE;
			} elseif ($this->selectedBackendLayoutId == 0) {
					// if it not set check the rootline for a layout on next level and use this
				$rootline = t3lib_BEfunc::BEgetRootLine($this->pageUid, '', TRUE);
				for ($i = count($rootline) - 2; $i > 0; $i--) {
					$this->selectedBackendLayoutId = $rootline[$i]['backend_layout_next_level'];
					if ($this->selectedBackendLayoutId > 0) {
							// stop searching if a layout for "next level" is set
						break;
					} elseif ($this->selectedBackendLayoutId == -1){
							// if layout for "next level" is set to "none" - don't use any and stop searching
						$this->selectedBackendLayoutId = FALSE;
						break;
					}
				}
			}
		}
			// if it is set to a positive value use this
		return $this->selectedBackendLayoutId;
	}

	/**
	 * Returns the grid layout setup of selected backend layout.
	 *
	 * @author Arno Dudek <webmaster@adgrafik.at>
	 * @param string $layoutId: The backend layout ID.
	 * @param string $fieldName: Current field.
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
	 * @return  array  $tcaItems
	 */
	public function getColPosListItemsParsed() {
		$tsConfig  = t3lib_BEfunc::getModTSconfig($this->pageUid, 'TCEFORM.tt_content.colPos');
		$tcaConfig = $GLOBALS['TCA']['tt_content']['columns']['colPos']['config'];

		/** @var $tceForms t3lib_TCEForms */
		$tceForms = t3lib_div::makeInstance('t3lib_TCEForms');

		$tcaItems = $tcaConfig['items'];
		$tcaItems = $tceForms->addItems($tcaItems, $tsConfig['properties']['addItems.']);

		if (isset($tcaConfig['itemsProcFunc']) && $tcaConfig['itemsProcFunc']) {
			$this->addColPosSelectItems($tcaItems);
		}

		foreach (t3lib_div::trimExplode(',', $tsConfig['properties']['removeItems'], 1) as $removeId) {
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
	 * @author Arno Dudek <webmaster@adgrafik.at>
	 * @param array &$items: Select items
	 * @param string $fieldName: Current field
	 * @return void
	 */
	public function addBackendLayoutSelectItems(&$items, $fieldName) {

		foreach ($this->setup[$fieldName] as $backendLayoutId => $item) {
			$items[] = array(
				$GLOBALS['LANG']->sL($item['title']),
				$backendLayoutId,
				$item['icon'],
			);
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
	 * @author Arno Dudek <webmaster@adgrafik.at>
	 * @param integer $pageUid: The uid of the page we are currently working on
	 * @return tx_cms_BackendLayout
	 */
	protected function loadSetup() {

		// Load page TSconfig.
		$BEfunc = t3lib_div::makeInstance('t3lib_BEfunc');
		$pageTSconfig = $BEfunc->getPagesTSconfig($this->pageUid);

		$storagePid = isset($pageTSconfig['TCEFORM.']['pages.']['_STORAGE_PID'])
			? $pageTSconfig['TCEFORM.']['pages.']['_STORAGE_PID']
			: 0;

		$pageTSconfigId = array(
			'backend_layout' => isset($pageTSconfig['TCEFORM.']['pages.']['backend_layout.']['PAGE_TSCONFIG_ID'])
				? $pageTSconfig['TCEFORM.']['pages.']['backend_layout.']['PAGE_TSCONFIG_ID']
				: 0,
			'backend_layout_next_level' => isset($pageTSconfig['TCEFORM.']['pages.']['backend_layout_next_level.']['PAGE_TSCONFIG_ID'])
				? $pageTSconfig['TCEFORM.']['pages.']['backend_layout_next_level.']['PAGE_TSCONFIG_ID']
				: 0,
		);

		$backendLayoutConfig = array();

		$exclude = isset($pageTSconfig['tx_cms_backendlayout.']['exclude'])
			? t3lib_div::trimExplode(',', $pageTSconfig['tx_cms_backendlayout.']['exclude'])
			: array();

		$overruleRecords = (isset($pageTSconfig['tx_cms_backendlayout.']['overruleRecords']) && $pageTSconfig['tx_cms_backendlayout.']['overruleRecords'] == '1');

		if (isset($pageTSconfig['tx_cms_backendlayout.']) && isset($pageTSconfig['tx_cms_backendlayout.']['setup.'])) {

			foreach ($pageTSconfig['tx_cms_backendlayout.']['setup.'] as $layoutId => $item) {
				// remove tailing dot of layout ID
				$layoutId = rtrim($layoutId, '.');

				// continue if layout is excluded
				if (in_array($layoutId, $exclude, TRUE)) {
					continue;
				}

				// parse icon path
				$item['icon'] = (strpos($item['icon'], 'EXT:') === 0)
					? str_replace(PATH_site, '../', t3lib_div::getFileAbsFileName($item['icon']))
					: $item['icon'];

				// remove tailing dot of config
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
					OR ( backend_layout.pid = ' . $pageTSconfigId[$fieldName] . ' OR backend_layout.pid = ' . $storagePid . ' ) 
					OR ( ' . $pageTSconfigId[$fieldName] . ' = 0 AND backend_layout.pid = ' . $this->pageUid . ' ) 
				)' . t3lib_BEfunc::BEenableFields('backend_layout'),
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
					$parser = t3lib_div::makeInstance('t3lib_TSparser');
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
				'backend_layout' => t3lib_div::array_merge_recursive_overrule($backendLayoutConfig, $backendLayoutRecords['backend_layout']),
				'backend_layout_next_level' => t3lib_div::array_merge_recursive_overrule($backendLayoutConfig, $backendLayoutRecords['backend_layout_next_level']),
			);
		} else {
			$this->setup = array(
				'backend_layout' => t3lib_div::array_merge_recursive_overrule($backendLayoutRecords['backend_layout'], $backendLayoutConfig),
				'backend_layout_next_level' => t3lib_div::array_merge_recursive_overrule($backendLayoutRecords['backend_layout_next_level'], $backendLayoutConfig),
			);
		}

		return $this;
	}

	/**
	 * Parse backend layout columns.
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