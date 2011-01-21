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
		$params['items'] = $this->addColPosListLayoutItems($params['row']['pid'], $params['items']);
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
		}

		return $items;
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

		$tceForms = t3lib_div::makeInstance('t3lib_TCEForms');

		$tcaItems = $tcaConfig['items'];
		$tcaItems = $tceForms->addItems($tcaItems, $tsConfig['properties']['addItems.']);

		if (isset($tcaConfig['itemsProcFunc']) && $tcaConfig['itemsProcFunc']) {
			$tcaItems = $this->addColPosListLayoutItems($id, $tcaItems);
		}

		foreach (t3lib_div::trimExplode(',', $tsConfig['properties']['removeItems'], 1) as $removeId) {
			unset($tcaItems[$removeId]);
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

			if (intval($page['backend_layout_next_level']) > 0 && $page['uid'] != $id) {
				$backendLayoutUid = intval($page['backend_layout_next_level']);
				break;
			} else {
				if (intval($page['backend_layout']) > 0) {
					$backendLayoutUid = intval($page['backend_layout']);
					break;
				}
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