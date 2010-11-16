<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 GridView Team
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

class tx_cms_be_layout {

	/**
	 * @mficzel
	 * @todo add items
	 * @param unknown_type $params
	 */
	function colPosListItemProcFunc(&$params) {
		$params['items'] = $this->addColPosListLayoutItems($params['row']['pid'], $params['items']);
	}

	function addColPosListLayoutItems($pageId, $items) {
		$layout = $this->getSelectedBackendLayout($pageId);
		if ($layout && $layout['__items']) {
			$items = $layout['__items'];
		}
		return $items;
	}

	function getColPosListItemsParsed($id) {

		$tsConfig = t3lib_BEfunc::getModTSconfig($id, 'TCEFORM.tt_content.colPos');
		$tcaConfig = $GLOBALS['TCA']['tt_content']['columns']['colPos']['config'];

		$TCEForms = t3lib_div::makeInstance('t3lib_TCEForms');
		$tcaItems = $tcaConfig['items'];
		$tcaItems = $TCEForms->addItems($tcaItems, $tsConfig['properties']['addItems.']);
		if (isset($tcaConfig['itemsProcFunc']) && $tcaConfig['itemsProcFunc']) {
			$tcaItems = $this->addColPosListLayoutItems($id, $tcaItems);
		}
		foreach (t3lib_div::trimExplode(',', $tsConfig['properties']['removeItems'], 1) as $removeId) {
			unset($tcaItems[$removeId]);
		}
		return $tcaItems;
	}

	function getSelectedBackendLayout($id) {
		$rootline = t3lib_BEfunc::BEgetRootLine($id);
		$backendLayoutUid = null;
		for ($i = count($rootline); $i > 0; $i--) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid,be_layout,be_layout_next_level', 'pages', 'uid=' . intval($rootline[$i]['uid']));
			$page = $res[0];
			if (intval($page['be_layout_next_level']) > 0 && $page['uid'] != $id) {
				$backendLayoutUid = intval($page['be_layout_next_level']);
				break;
			} else {
				if (intval($page['be_layout']) > 0) {
					$backendLayoutUid = intval($page['be_layout']);
					break;
				}
			}
		}
		$backendLayout = null;
		if ($backendLayoutUid) {
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'be_layouts', 'uid=' . $backendLayoutUid);
			if ($res) {
				$backendLayout = $res[0];
				$parser = t3lib_div::makeInstance('t3lib_TSparser');
				$parser->parse($backendLayout['config']);
				$backendLayout['__config'] = $parser->setup;
				$backendLayout['__items'] = array();
				$backendLayout['__colPosList'] = array();

				// create items and colPosList
				if ($backendLayout['__config']['be_layout.'] && $backendLayout['__config']['be_layout.']['rows.']) {
					foreach ($backendLayout['__config']['be_layout.']['rows.'] as $row) {
						if (true && count($row['columns.'])) {
							foreach ($row['columns.'] as $column) {
								if (true) {
									$backendLayout['__items'][] = array(
										$column['name'],
										$column['colPos'],
										null
									);
									$backendLayout['__colPosList'][] = $column['colPos'];
								}
							}
						}
					}
				}

			}
		}
		return $backendLayout;
	}

}

?>