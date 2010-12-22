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
 * Context Menu of the Page Tree
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @package TYPO3
 * @subpackage t3lib
 */
class tx_pagetree_ExtDirect_ContextMenu extends t3lib_contextmenu_extdirect_ContextMenu {
	/**
	 * Sets the data provider
	 *
	 * @return void
	 */
	protected function initDataProvider() {
		/** @var $dataProvider tx_pagetree_ContextMenu_DataProvider */
		$dataProvider = t3lib_div::makeInstance('tx_pagetree_ContextMenu_DataProvider');
		$this->setDataProvider($dataProvider);
	}

	/**
	 * Returns the actions for the given node information's
	 *
	 * @param stdClass $node
	 * @return array
	 */
	public function getActionsForNodeArray($nodeData) {
		/** @var $node tx_pagetree_Node */
		$node = t3lib_div::makeInstance('tx_pagetree_Node', (array) $nodeData);
		$node->setRecord(tx_pagetree_Commands::getNodeRecord($node->getId()));

		$this->initDataProvider();
		$this->dataProvider->setContextMenuType('table.' . $node->getType());
		$actionCollection = $this->dataProvider->getActionsForNode($node);

		if ($actionCollection instanceof t3lib_contextmenu_ActionCollection) {
			$actions = $actionCollection->toArray();
		}

		return $actions;
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/extdirect/class.tx_pagetree_extdirect_contextmenu.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/extdirect/class.tx_pagetree_extdirect_contextmenu.php']);
}

?>