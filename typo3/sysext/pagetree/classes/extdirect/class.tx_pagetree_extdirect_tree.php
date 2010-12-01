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
 * Data Provider of the Page Tree
 *
 * @author Stefan Galinski <stefan.galinski@gmail.com>
 * @package TYPO3
 * @subpackage tx_pagetree
 */
class tx_pagetree_ExtDirect_Tree extends t3lib_tree_ExtDirect_AbstractExtJsTree {
	/**
	 * Sets the data provider
	 *
	 * @return void
	 */
	protected function initDataProvider() {
		/** @var $dataProvider tx_pagetree_DataProvider */
		$dataProvider = t3lib_div::makeInstance('tx_pagetree_DataProvider');
		$this->setDataProvider($dataProvider);
	}

	/**
	 * Data Provider
	 *
	 * @return tx_pagetree_DataProvider
	 */
	protected $dataProvider = NULL;

	/**
	 * Returns the root node of the tree
	 *
	 * Not used for the page tree, because the multiple domains/roots feature
	 *
	 * @return array
	 */
	public function getRoot() {
		return array();
	}

	/**
	 * Fetches the next tree level
	 *
	 * @param int $nodeId
	 * @param stdClass $nodeData
	 * @return array
	 */
	public function getNextTreeLevel($nodeId, $nodeData) {
		$this->initDataProvider();

		/** @var $node tx_pagetree_Node */
		$node = t3lib_div::makeInstance('tx_pagetree_Node', (array) $nodeData);

		if ($nodeId === 'root') {
			$nodeCollection = $this->dataProvider->getTreeMounts();
		} else {
			$nodeCollection = $this->dataProvider->getNodes($node);
		}

		return $nodeCollection->toArray();
	}

	/**
	 * Returns a tree that only contains elements that match the given search string
	 *
	 * @param string $searchFilter
	 * @return array
	 */
	public function getFilteredTree($searchFilter) {
		$this->initDataProvider();

		$nodeCollection = $this->dataProvider->getFilteredNodes($searchFilter);

		return $nodeCollection->toArray();
	}

	/**
	 * Returns the localized list of doktypes to display
	 *
	 * Note: The list can be filtered by the user typoscript
	 * option "options.pageTree.doktypesToShowInNewPageDragArea".
	 *
	 * @return array
	 */
	public function getNodeTypes() {
		$doktypes = t3lib_div::trimExplode(
			',', $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.doktypesToShowInNewPageDragArea')
		);

		$output = array();
		foreach ($doktypes as $doktype) {
			$label = $GLOBALS['LANG']->sL('LLL:EXT:pagetree/locallang_pagetree.xml:page.doktype.' . $doktype, TRUE);
			$spriteIcon = $this->getSpriteIconClasses($GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$doktype]);

			$output[] = array(
				'nodeType' => $doktype,
				'cls' => 'topPanel-button ' . $spriteIcon,
				'title' => $label,
				'tooltip' => $label,
			);
		}

		return $output;
	}

	/**
	 * Returns the sprite icon classes for a given icon
	 *
	 * @param string $icon
	 * @return string
	 */
	public function getSpriteIconClasses($icon) {
		return t3lib_iconWorks::getSpriteIconClasses($icon);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/extdirect/class.tx_pagetree_extdirect_tree.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/extdirect/class.tx_pagetree_extdirect_tree.php']);
}

?>