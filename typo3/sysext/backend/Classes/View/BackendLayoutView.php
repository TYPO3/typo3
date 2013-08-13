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
	 * @var array $setup
	 */
	protected $setup;

	/**
	 * @var array $selectedBackendLayoutId
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
        $this->loadSetup($pageUid);
	}

    /**
     * Returns the grid layout setup.
     *
     * @param integer $pageUid
     * @return array
     */
	public function getSetup($pageUid = 0) {
		return $this->setup[$pageUid];
	}

    /**
     * Returns the grid layout setup by layout ID.
     *
     * @param string $layoutId: The backend layout ID.
     * @param integer $pageUid
     * @param string $fieldName: Current field.
     * @return array
     */
	public function getSetupByLayoutId($layoutId, $pageUid = 0, $fieldName = NULL) {

		$fieldName = ($fieldName === NULL) ? 'backend_layout' : $fieldName;

        if (isset($this->setup[$pageUid][$fieldName][$layoutId])) {
			return $this->setup[$pageUid][$fieldName][$layoutId];
		}
	}

    /**
     * Returns the backend layout which should be used for this page.
     *
     * @param integer $pageUid
     * @return mixed Uid of the backend layout record or NULL if no layout should be used
     */
	public function getSelectedBackendLayoutId($pageUid = 0) {

		if ($this->selectedBackendLayoutId[$pageUid] === NULL) {
			$page = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid, pid, backend_layout', 'pages', 'uid=' . $pageUid);
			BackendUtility::workspaceOL('pages', $page);

            $this->selectedBackendLayoutId[$pageUid] = $page['backend_layout'];
			if ($this->selectedBackendLayoutId[$pageUid] == -1) {
				// If it is set to "none" - don't use any
				$this->selectedBackendLayoutId[$pageUid] = FALSE;
			} elseif ($this->selectedBackendLayoutId[$pageUid] == 0) {
				// If it not set check the rootline for a layout on next level and use this
				$rootline = BackendUtility::BEgetRootLine($pageUid, '', TRUE);
				for ($i = count($rootline) - 2; $i > 0; $i--) {
					$this->selectedBackendLayoutId = $rootline[$i]['backend_layout_next_level'];
					if ($this->selectedBackendLayoutId[$pageUid] > 0) {
						// Stop searching if a layout for "next level" is set
						break;
					} elseif ($this->selectedBackendLayoutId[$pageUid] == -1){
						// If layout for "next level" is set to "none" - don't use any and stop searching
						$this->selectedBackendLayoutId = FALSE;
						break;
					}
				}
			}
		}
		// If it is set to a positive value use this
        return $this->selectedBackendLayoutId[$pageUid];
	}

    /**
     * Returns the grid layout setup of selected backend layout.
     *
     * @param integer $pageUid
     * @return array
     */
	public function getSelectedBackendLayout($pageUid = 0) {
        return $this->getSelectedBackendLayoutSetup($pageUid);
	}

    /**
     * Returns the grid layout setup of selected backend layout.
     *
     * @param integer $pageUid
     * @return array
     */
	public function getSelectedBackendLayoutSetup($pageUid = 0) {
        return $this->getSetupByLayoutId($this->getSelectedBackendLayoutId($pageUid), $pageUid);
	}

    /**
     * Gets the selected backend layout columns
     *
     * @param integer $pageUid
     * @return array|NULL  $backendLayout
     */
	public function getSelectedBackendLayoutColPosItems($pageUid = 0) {
		if ($this->backendLayoutColPos === NULL) {
			$this->parseBackendLayoutColPos($pageUid);
		}
		return $this->backendLayoutColPos[$pageUid]['items'];
	}

    /**
     * Gets the selected backend layout
     *
     * @param integer $pageUid
     * @return array|NULL  $backendLayout
     */
	public function getSelectedBackendLayoutColPosList($pageUid = 0) {
		if ($this->backendLayoutColPos === NULL) {
			$this->parseBackendLayoutColPos($pageUid);
		}
		return $this->backendLayoutColPos[$pageUid]['list'];
	}

    /**
     * Gets the list of available columns for a given page id
     *
     * @param integer $pageUid
     * @return array $tcaItems
     */
	public function getColPosListItemsParsed($pageUid = 0) {
		$tsConfig  = BackendUtility::getModTSconfig($pageUid, 'TCEFORM.tt_content.colPos');
		$tcaConfig = $GLOBALS['TCA']['tt_content']['columns']['colPos']['config'];
		$tceForms = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\FormEngine');
		$tcaItems = $tcaConfig['items'];
		$tcaItems = $tceForms->addItems($tcaItems, $tsConfig['properties']['addItems.']);
		if (isset($tcaConfig['itemsProcFunc']) && $tcaConfig['itemsProcFunc']) {
            $this->addColPosSelectItems($tcaItems, $pageUid);
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
     * @param integer $pageUid
     * @param string $fieldName: Current field
     * @return void
     */
	public function addBackendLayoutSelectItems(&$items, $pageUid = 0, $fieldName) {

		foreach ($this->setup[$pageUid][$fieldName] as $backendLayoutId => $item) {
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
     * @param integer $pageUid
     * @return void
     */
	public function addColPosSelectItems(&$items, $pageUid = 0) {
		// if setup found reset items
        if (($colPosItems = $this->getSelectedBackendLayoutColPosItems($pageUid)) !== NULL) {
            $items = $colPosItems;
		}
	}

    /**
     * Returns the page TSconfig merged with the grid layout records.
     *
     * @param integer $pageUid
     * @return BackendLayoutView
     */
	protected function loadSetup($pageUid = 0) {
		// Load page TSconfig.
		$pageTSconfig = BackendUtility::getPagesTSconfig($pageUid);
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
					OR ( ' . $pageTSconfigId[$fieldName] . ' = 0 AND backend_layout.pid = ' . intval($pageUid) . ' )
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
			$this->setup[$pageUid] = array(
				'backend_layout' => GeneralUtility::array_merge_recursive_overrule($backendLayoutConfig, $backendLayoutRecords['backend_layout']),
				'backend_layout_next_level' => GeneralUtility::array_merge_recursive_overrule($backendLayoutConfig, $backendLayoutRecords['backend_layout_next_level']),
			);
		} else {
			$this->setup[$pageUid] = array(
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
     * @param integer $pageUid
     * @return void
     */
	protected function parseBackendLayoutColPos($pageUid = 0) {

		$setup = $this->getSetupByLayoutId($this->getSelectedBackendLayoutId($pageUid), $pageUid);

		if ($setup) {

			$this->backendLayoutColPos = array(
				'items' => array(),
				'list' => array(),
			);

			foreach ($setup['config']['rows.'] as $row) {
				if (isset($row['columns.']) && is_array($row['columns.'])) {
					foreach ($row['columns.'] as $column) {
						$this->backendLayoutColPos[$pageUid]['items'][] = array(
							$GLOBALS['LANG']->sL($column['name']),
							intval($column['colPos']),
							NULL
						);
						$this->backendLayoutColPos[$pageUid]['list'][] = intval($column['colPos']);
					}
				}
			}
		}
	}
}
?>