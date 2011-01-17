<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 * Context Menu Data Provider for the Page Tree
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_contextmenu_pagetree_DataProvider extends t3lib_contextmenu_AbstractDataProvider {
	/**
	 * Old Context Menu Options (access mapping)
	 *
	 * Note: Only option with different namings are mapped!
	 *
	 * @var array
	 */
	protected $legacyContextMenuMapping = array(
		'hide' => 'disable',
		'paste' => 'pasteInto,pasteAfter',
		'mount_as_treeroot' => 'mountAsTreeroot',
	);

	/**
	 * Fetches the items that should be disabled from the context menu
	 *
	 * @return array
	 */
	protected function getDisableActions() {
		$tsConfig = $GLOBALS['BE_USER']->getTSConfig(
			'options.contextMenu.' . $this->getContextMenuType() . '.disableItems'
		);

		$disableItems = array();
		if (trim($tsConfig['value']) !== '') {
			$disableItems = t3lib_div::trimExplode(',', $tsConfig['value']);
		}

		$tsConfig = $GLOBALS['BE_USER']->getTSConfig('options.contextMenu.pageTree.disableItems');
		$oldDisableItems = array();
		if (trim($tsConfig['value']) !== '') {
			$oldDisableItems = t3lib_div::trimExplode(',', $tsConfig['value']);
		}

		$additionalItems = array();
		foreach ($oldDisableItems as $item) {
			if (!isset($this->legacyContextMenuMapping[$item])) {
				$additionalItems[] = $item;
				continue;
			}

			if (strpos($this->legacyContextMenuMapping[$item], ',')) {
				$actions = t3lib_div::trimExplode(',', $this->legacyContextMenuMapping[$item]);
				$additionalItems = array_merge($additionalItems, $actions);
			} else {
				$additionalItems[] = $item;
			}
		}

		return array_merge($disableItems, $additionalItems);
	}

	/**
	 * Returns the actions for the node
	 *
	 * @param t3lib_tree_pagetree_Node $node
	 * @return t3lib_contextmenu_ActionCollection
	 */
	public function getActionsForNode(t3lib_tree_Node $node) {
		$this->disableItems = $this->getDisableActions();
		$configuration = $this->getConfiguration();
		$contextMenuActions = array();
		if (is_array($configuration)) {
			$contextMenuActions = $this->getNextContextMenuLevel($configuration, $node);
		}

		return $contextMenuActions;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/contextmenu/pagetree/class.t3lib_contextmenu_pagetree_dataprovider.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/contextmenu/pagetree/class.t3lib_contextmenu_pagetree_dataprovider.php']);
}

?>