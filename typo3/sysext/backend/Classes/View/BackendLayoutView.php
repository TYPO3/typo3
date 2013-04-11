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
/**
 * Backend layout for CMS
 *
 * @author GridView Team
 */
class BackendLayoutView {

	/**
	 * ItemProcFunc for colpos items
	 *
	 * @param array $params
	 * @return void
	 */
	public function colPosListItemProcFunc(&$params) {
		if ($params['row']['pid'] > 0) {
			$params['items'] = $this->addColPosListLayoutItems($params['row']['pid'], $params['items']);
		} else {
			// Negative uid_pid values indicate that the element has been inserted after an existing element
			// so there is no pid to get the backendLayout for and we have to get that first
			$existingElement = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('pid', 'tt_content', 'uid=' . -intval($params['row']['pid']));
			if ($existingElement['pid'] > 0) {
				$params['items'] = $this->addColPosListLayoutItems($existingElement['pid'], $params['items']);
			}
		}
	}

	/**
	 * Adds items to a colpos list
	 *
	 * @param integer $pageId
	 * @param array $items
	 * @return array
	 */
	protected function addColPosListLayoutItems($pageId, $items) {
		$layout = $this->getSelectedBackendLayout($pageId);
		if ($layout && $layout['__items']) {
			$items = $layout['__items'];
		}
		return $items;
	}

	/**
	 * Gets the list of available columns for a given page id
	 *
	 * @param integer $id
	 * @return array $tcaItems
	 */
	public function getColPosListItemsParsed($id) {
		$tsConfig = \TYPO3\CMS\Backend\Utility\BackendUtility::getModTSconfig($id, 'TCEFORM.tt_content.colPos');
		$tcaConfig = $GLOBALS['TCA']['tt_content']['columns']['colPos']['config'];
		/** @var $tceForms \TYPO3\CMS\Backend\Form\FormEngine */
		$tceForms = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\FormEngine');
		$tcaItems = $tcaConfig['items'];
		$tcaItems = $tceForms->addItems($tcaItems, $tsConfig['properties']['addItems.']);
		if (isset($tcaConfig['itemsProcFunc']) && $tcaConfig['itemsProcFunc']) {
			$tcaItems = $this->addColPosListLayoutItems($id, $tcaItems);
		}
		foreach (\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $tsConfig['properties']['removeItems'], 1) as $removeId) {
			foreach ($tcaItems as $key => $item) {
				if ($item[1] == $removeId) {
					unset($tcaItems[$key]);
				}
			}
		}
		return $tcaItems;
	}

	/**
	 * Gets the selected backend layout
	 *
	 * @param integer $id
	 * @return array|NULL $backendLayout
	 */
	public function getSelectedBackendLayout($id) {
		$rootline = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($id);
		$backendLayoutUid = NULL;
		for ($i = count($rootline); $i > 0; $i--) {
			$page = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('uid, pid, backend_layout, backend_layout_next_level', 'pages', 'uid=' . intval($rootline[$i]['uid']));
			\TYPO3\CMS\Backend\Utility\BackendUtility::workspaceOL('pages', $page);
			$selectedBackendLayout = intval($page['backend_layout']);
			$selectedBackendLayoutNextLevel = intval($page['backend_layout_next_level']);
			if ($selectedBackendLayout != 0 && $page['uid'] == $id) {
				if ($selectedBackendLayout > 0) {
					// Backend layout for current page is set
					$backendLayoutUid = $selectedBackendLayout;
				}
				break;
			} elseif ($selectedBackendLayoutNextLevel == -1 && $page['uid'] != $id) {
				// Some previous page in our rootline sets layout_next to "None"
				break;
			} elseif ($selectedBackendLayoutNextLevel > 0 && $page['uid'] != $id) {
				// Some previous page in our rootline sets some backend_layout, use it
				$backendLayoutUid = $selectedBackendLayoutNextLevel;
				break;
			}
		}
		$backendLayout = NULL;
		if ($backendLayoutUid) {
			$backendLayout = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'backend_layout', 'uid=' . $backendLayoutUid);
		} else {
			$backendLayout['config'] = self::getDefaultColumnLayout();
		}
		if ($backendLayout) {
			/** @var $parser \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser */
			$parser = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\Parser\\TypoScriptParser');
			$parser->parse($parser->checkIncludeLines($backendLayout['config']));
			$backendLayout['__config'] = $parser->setup;
			$backendLayout['__items'] = array();
			$backendLayout['__colPosList'] = array();
			// create items and colPosList
			if ($backendLayout['__config']['backend_layout.'] && $backendLayout['__config']['backend_layout.']['rows.']) {
				foreach ($backendLayout['__config']['backend_layout.']['rows.'] as $row) {
					if (isset($row['columns.']) && is_array($row['columns.'])) {
						foreach ($row['columns.'] as $column) {
							$backendLayout['__items'][] = array(
								\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($column['name'], 'LLL:') ? $GLOBALS['LANG']->sL($column['name']) : $column['name'],
								$column['colPos'],
								NULL
							);
							$backendLayout['__colPosList'][] = $column['colPos'];
						}
					}
				}
			}
		}
		return $backendLayout;
	}

	/**
	 * Get default columns layout
	 *
	 * @return string Default four column layout
	 * @static
	 */
	static public function getDefaultColumnLayout() {
		return '
		backend_layout {
			colCount = 4
			rowCount = 1
			rows {
				1 {
					columns {
						1 {
							name = LLL:EXT:cms/locallang_ttc.xlf:colPos.I.0
							colPos = 1
						}
						2 {
							name = LLL:EXT:cms/locallang_ttc.xlf:colPos.I.1
							colPos = 0
						}
						3 {
							name = LLL:EXT:cms/locallang_ttc.xlf:colPos.I.2
							colPos = 2
						}
						4 {
							name = LLL:EXT:cms/locallang_ttc.xlf:colPos.I.3
							colPos = 3
						}
					}
				}
			}
		}
		';
	}

}


?>
