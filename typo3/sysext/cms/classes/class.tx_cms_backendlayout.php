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

class tx_cms_BackendLayout {

	/**
	 * ItemProcFunc for colpos items
	 *
	 * @param  array $params
	 * @return void
	 */
	public function colPosListItemProcFunc(&$params) {
		if ($params['row']['pid'] > 0) {
			$params['items'] = $this->getColPosListItemsParsed($params['row']['pid'], $params['row']);
		} else {
			// negative uid_pid values indicate that the element has been inserted after an existing element
			// so there is no pid to get the backendLayout for and we have to get that first
			$existingElement = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('pid', 'tt_content', 'uid=' . -(intval($params['row']['pid'])));
			if ($existingElement['pid'] > 0) {
				$params['items'] = $this->getColPosListItemsParsed($existingElement['pid'], $params['row']);
			}
		}
	}

	/**
	 * Adds items to a colpos list
	 *
	 * @param  int  $pageId
	 * @param  array  $items
	 * @return array
	 */
	protected function addColPosListLayoutItems($pageId, $items) {
		$layout = $this->getSelectedBackendLayout($pageId);

		if ($layout && $layout['__items']) {
			$items = $layout['__items'];
			if(count($items)) {
				foreach($items as $key => $valueArray) {
					if($valueArray['1'] == '') {
						unset($items[$key]);
					}
				}
			};
		}

		return $items;
	}

	/**
	 * Gets the list of available columns for a given page id
	 *
	 * @param  int  $id
	 * @return  array  $tcaItems
	 */
	public function getColPosListItemsParsed($id, $caller) {

		$pageData = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*','pages','uid='. $id);
		$tcaConfig = $GLOBALS['TCA']['tt_content']['columns']['colPos']['config'];
		$tceForms = t3lib_div::makeInstance('t3lib_TCEForms');
		$tcaItems = $tcaConfig['items'];

		if (isset($tcaConfig['itemsProcFunc']) && $tcaConfig['itemsProcFunc']) {
			$tcaItems = $this->addColPosListLayoutItems($id, $tcaItems);
		}

		// Labels should only be processed when we are in the actual grid view
		// while we are editing an item, or while moving it in the list view
		// but not while listing items
		if(is_array($caller) && (($caller['checkItemsArray'] && !$caller['uid']) || (!$caller['checkItemsArray'] && $caller['uid']))) {

			// we have to take care of items that have been removed via removeItems
			// and items that have been restricted by colPos_list settings
			// each of them can be set in 2 different places
			// addItems will be ignored, since the grid can't show these additional columns anyway

			$removeItems = t3lib_Befunc::getModTSconfig($id,'TCEFORM.tt_content.colPos.removeItems');
			$removeItems = t3lib_div::trimExplode(',', $removeItems['value']);
			$removeTypeItems = t3lib_Befunc::getModTSconfig($id,'TCEFORM.tt_content.colPos.types.' . $pageData['doktype'] . '.removeItems');
			$removeTypeItems = t3lib_div::trimExplode(',', $removeTypeItems['value']);
			$webLayout_colPosList = t3lib_Befunc::getModTSconfig($id,'mod.web_layout.tt_content.colPos_list');
			
			if($webLayout_colPosList) {
				$colPosList = $webLayout_colPosList;
			} else {
				$colPosList = t3lib_Befunc::getModTSconfig($id,'mod.SHARED.colPos_list');
			}
			if($colPosList['value'] != '') {
				$colPosArray = t3lib_div::trimExplode(',',$colPosList['value']);
			}

			foreach($tcaItems as $key => $item) {
				if((is_array($removeItems) && in_array($item[1], $removeItems)) || (is_array($removeItems) && in_array($item[1], $removeTypeItems))) {
					unset($tcaItems[$key]);
				}
				if(isset($colPosArray[0]) && !in_array($item[1], $colPosArray)) {
					unset($tcaItems[$key]);
				}
			}
		}

		return $tcaItems;
	}

	/**
	 * Gets the selected backend layout
	 *
	 * @param  int  $id
	 * @return array|null  $backendLayout
	 */
	public function getSelectedBackendLayout($id) {
		$rootline = t3lib_BEfunc::BEgetRootLine($id);
		$backendLayoutUid = NULL;

		for ($i = count($rootline); $i > 0; $i--) {
			$page = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'uid, backend_layout, backend_layout_next_level',
				'pages',
				'uid=' . intval($rootline[$i]['uid'])
			);
			$selectedBackendLayout = intval($page['backend_layout']);
			$selectedBackendLayoutNextLevel = intval($page['backend_layout_next_level']);
			if ($selectedBackendLayout != 0 && $page['uid'] == $id) {
				if ($selectedBackendLayout > 0) {
						// Backend layout for current page is set
					$backendLayoutUid = $selectedBackendLayout;
				}
				break;
			} else if ($selectedBackendLayoutNextLevel == -1 && $page['uid'] != $id) {
					// Some previous page in our rootline sets layout_next to "None"
				break;
			} else if ($selectedBackendLayoutNextLevel > 0 && $page['uid'] != $id) {
					// Some previous page in our rootline sets some backend_layout, use it
				$backendLayoutUid = $selectedBackendLayoutNextLevel;
				break;
			}
		}

		$backendLayout = NULL;
		if ($backendLayoutUid) {
			$backendLayout = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
				'*',
				'backend_layout',
				'uid=' . $backendLayoutUid
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
									$column['name'],
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

		return $backendLayout;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cms/classes/class.tx_cms_backendlayout.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/cms/classes/class.tx_cms_backendlayout.php']);
}

?>
