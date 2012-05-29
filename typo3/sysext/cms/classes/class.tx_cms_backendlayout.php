<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 GridView Team
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
	protected $setup;

	/*
	 * @var mixed $selectedBackendLayoutId: Id of the backend layout or FALSE if no layout is set.
	 */
	protected $selectedBackendLayoutId;

	/**
	 * Returns the page TSconfig merged with the grid layout records.
	 *
	 * @param integer $pageId: The uid of the page we are currently working on
	 * @return void
	 */
	public function isLoaded() {
		return $this->setup !== NULL;
	}

	/**
	 * Returns the grid layout setup.
	 *
	 * @author Arno Dudek <webmaster@adgrafik.at>
	 * @param string $layoutId: If set only requested layout setup, else all layout setups will be returned.
	 * @param string $fieldName: Current field.
	 * @return array
	 */
	public function getSetup($layoutId = '', $fieldName = '') {

		$fieldName = $fieldName ? $fieldName : 'backend_layout';
		$setup = NULL;

		if ($layoutId) {
			if (isset($this->setup[$fieldName][$layoutId])) {
				$setup = $this->setup[$fieldName][$layoutId];
			}
		} else {
			$setup = $this->setup;
		}

		return $setup;
	}

	/**
	 * Returns the item array for form field selection.
	 *
	 * @author Arno Dudek <webmaster@adgrafik.at>
	 * @param array &$selectItems: Select items
	 * @param string $fieldName: Current field
	 * @return array
	 */
	public function mergeBackendLayoutSelectItems(&$selectItems, $fieldName) {

		foreach ($this->setup[$fieldName] as $backendLayoutId => $item) {
			$selectItems[] = array(
				t3lib_div::isFirstPartOfStr($item['title'], 'LLL:') ? $GLOBALS['LANG']->sL($item['title']) : $item['title'],
				$backendLayoutId,
				$item['icon'],
			);
		}
	}

	/**
	 * ItemProcFunc for colpos items
	 *
	 * @param  array $params
	 * @return void
	 */
	public function colPosListItemProcFunc(&$params) {
		if ($params['row']['pid'] > 0) {
			$params['items'] = $this->addColPosListLayoutItems($params['row']['pid'], $params['items']);
		} else {
			// negative uid_pid values indicate that the element has been inserted after an existing element
			// so there is no pid to get the backendLayout for and we have to get that first
			$existingElement = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('pid', 'tt_content', 'uid=' . -(intval($params['row']['pid'])));
			if ($existingElement['pid'] > 0) {
				$params['items'] = $this->addColPosListLayoutItems($existingElement['pid'], $params['items']);
			}
		}
	}

	/**
	 * Gets the list of available columns for a given page id
	 *
	 * @param  int  $id
	 * @return  array  $tcaItems
	 */
	public function getColPosListItemsParsed($id) {
		$tsConfig  = t3lib_BEfunc::getModTSconfig($id, 'TCEFORM.tt_content.colPos');
		$tcaConfig = $GLOBALS['TCA']['tt_content']['columns']['colPos']['config'];

		/** @var $tceForms t3lib_TCEForms */
		$tceForms = t3lib_div::makeInstance('t3lib_TCEForms');

		$tcaItems = $tcaConfig['items'];
		$tcaItems = $tceForms->addItems($tcaItems, $tsConfig['properties']['addItems.']);

		if (isset($tcaConfig['itemsProcFunc']) && $tcaConfig['itemsProcFunc']) {
			$tcaItems = $this->addColPosListLayoutItems($id, $tcaItems);
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
	 * Returns the backend layout which should be used for this page.
	 *
	 * @param integer $uid: Uid of the current page
	 * @return mixed Uid of the backend layout record or NULL if no layout should be used
	 */
	function getSelectedBackendLayoutId($uid) {

		if ($this->selectedBackendLayoutId === NULL) {

				// uid, pid, t3ver_swapmode needed for workspaceOL()
			$page = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid, pid, t3ver_swapmode, backend_layout', 'pages', 'uid=' . $uid);
			t3lib_BEfunc::workspaceOL('pages', $page);

			$this->selectedBackendLayoutId = $page['backend_layout'];
			if ($this->selectedBackendLayoutId == -1) {
					// if it is set to "none" - don't use any
				$this->selectedBackendLayoutId = FALSE;
			} elseif ($this->selectedBackendLayoutId == 0) {
					// if it not set check the rootline for a layout on next level and use this
				$rootline = t3lib_BEfunc::BEgetRootLine($uid, '', TRUE);
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
	 * Gets the selected backend layout
	 *
	 * @param  int  $uid
	 * @return array|NULL  $backendLayout
	 */
	public function getSelectedBackendLayoutColumns($uid) {

		$selectedBackendLayoutColumns = NULL;
		$backendLayoutId = $this->getSelectedBackendLayoutId($uid);
/*
		if ($backendLayoutId) {
			$backendLayout = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'*',
				'backend_layout',
				'uid=' . $backendLayoutId
			);

			if ($backendLayout) {
				$parser = t3lib_div::makeInstance('t3lib_TSparser');
				$parser->parse($backendLayout['config']);

				$backendLayout['__config']     = $parser->setup;
				$backendLayout['__items']      = array();
				$backendLayout['__colPosList'] = array();

					// create items and colPosList
				if ($backendLayout['__config']['backend_layout.'] && $backendLayout['__config']['backend_layout.']['rows.']) {
					foreach ($backendLayout['__config']['backend_layout.']['rows.'] as $row) {
						if (isset($row['columns.']) && is_array($row['columns.'])) {
							foreach ($row['columns.'] as $column) {
								$backendLayout['__items'][] = array(
									t3lib_div::isFirstPartOfStr($column['name'], 'LLL:') ? $GLOBALS['LANG']->sL($column['name']) : $column['name'],
									$column['colPos'],
									NULL
								);
								$backendLayout['__colPosList'][] = $column['colPos'];
							}
						}
					}
				}
			}
		}
*/
		$setup = $this->getSetup($backendLayoutId, 'backend_layout');

		if ($setup) {

			$selectedBackendLayoutColumns = array();

			foreach ($setup['config']['rows.'] as $row) {
				if (isset($row['columns.']) && is_array($row['columns.'])) {
					foreach ($row['columns.'] as $column) {
						$selectedBackendLayoutColumns[] = array(
							t3lib_div::isFirstPartOfStr($column['name'], 'LLL:') ? $GLOBALS['LANG']->sL($column['name']) : $column['name'],
							$column['colPos'],
							NULL
						);
					}
				}
			}
		}

		return $selectedBackendLayoutColumns;
	}

	/**
	 * ItemProcFunc for layout items
	 * removes items that are available for grid boxes on the first level only
	 * and items that are excluded for a certain branch or user
	 *
	 * @param	array	$params: An array containing the items and parameters for the list of items
	 * @return	void
	 */
	public function backendLayoutItemsProcFunc(&$params) {

		$pageId = (strpos($params['row']['uid'], 'NEW') === 0) 
			? $params['row']['pid'] 
			: $params['row']['uid'];

		$this->loadSetup($pageId)
			 ->mergeBackendLayoutSelectItems($params['items'], $params['field']);
	}

	/**
	 * Returns the page TSconfig merged with the grid layout records.
	 *
	 * @author Arno Dudek <webmaster@adgrafik.at>
	 * @param integer $pageId: The uid of the page we are currently working on
	 * @return tx_cms_BackendLayout
	 */
	public function loadSetup($pageId) {

		if ($this->isLoaded()) {
			return $this;
		}

		// Load page TSconfig.
		$BEfunc = t3lib_div::makeInstance('t3lib_BEfunc');
		$pageTSconfig = $BEfunc->getPagesTSconfig($pageId);

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
				$layoutId = substr($layoutId, 0, -1);

				// continue if layout is excluded
				if (in_array($layoutId, $exclude)) {
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
					OR ( pid = ' . $pageTSconfigId[$fieldName] . ' OR pid = ' . $storagePid . ' ) 
					OR ( ' . $pageTSconfigId[$fieldName] . ' = 0 AND pid = ' . $pageId . ' ) 
				) AND NOT hidden AND NOT deleted',
				'',
				'sorting ASC',
				'',
				'uid'
			);

			foreach ($result as $layoutId => $item) {

				// continue if layout is excluded
				if (in_array($layoutId, $exclude)) {
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
	 * Adds items to a colpos list
	 *
	 * @param  int  $pageId
	 * @param  array  $items
	 * @return array
	 */
	protected function addColPosListLayoutItems($pageId, $items) {

		$selectedBackendLayoutColumns = $this->getSelectedBackendLayoutColumns($pageId);

		if ($selectedBackendLayoutColumns) {
			$items = $selectedBackendLayoutColumns;
		}

		return $items;
	}
}
?>