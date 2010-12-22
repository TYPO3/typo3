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
	 * @return array
	 */
	public function getRoot() {
		$this->initDataProvider();
		$node = $this->dataProvider->getRoot();

		return $node->toArray();
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
	 * @param int $nodeId
	 * @param string $searchFilter
	 * @return array
	 */
	public function getFilteredTree($nodeId, $searchFilter) {
		if ($searchFilter === '') {
			return array();
		}

		$this->initDataProvider();
		if ($nodeId === 'root') {
			$nodeCollection = $this->dataProvider->getTreeMounts($searchFilter);
		} else {
			$nodeCollection = $this->dataProvider->getFilteredNodes($nodeId, $searchFilter);
		}

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
		$allowedDoktypes = t3lib_div::trimExplode(',', $GLOBALS['BE_USER']->groupData['pagetypes_select']);
		$isAdmin = $GLOBALS['BE_USER']->isAdmin();
		foreach ($doktypes as $doktype) {
			if (!$isAdmin && !in_array($doktype, $allowedDoktypes)) {
				continue;
			}

			$label = $GLOBALS['LANG']->sL('LLL:EXT:pagetree/locallang_pagetree.xml:page.doktype.' . $doktype, TRUE);
			$spriteIcon = t3lib_iconWorks::getSpriteIconClasses(
				$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$doktype]
			);

			$output[] = array(
				'nodeType' => $doktype,
				'cls' => 'typo3-pagetree-topPanel-button',
				'iconCls' => $spriteIcon,
				'title' => $label,
				'tooltip' => $label,
			);
		}

		return $output;
	}

	/**
	 * Returns
	 *
	 * @return string
	 */
	public function getIndicators() {
		/** @var $indicatorProvider tx_pagetree_Indicator */
		$indicatorProvider = t3lib_div::makeInstance('tx_pagetree_indicator');
		$indicatorHtml = implode(' ', $indicatorProvider->getAllIndicators());
		return ($indicatorHtml ? $indicatorHtml : '');
	}

	/**
	 * Returns the language labels, sprites and configuration options for the pagetree
	 *
	 * @return void
	 */
	public function loadResources() {
		$file = 'LLL:EXT:pagetree/locallang_pagetree.xml:';
		$configuration = array(
			'LLL' => array(
				'copyHint' => $GLOBALS['LANG']->sL($file . 'copyHint', TRUE),
				'fakeNodeHint' => $GLOBALS['LANG']->sL($file . 'fakeNodeHint', TRUE),
				'activeFilterMode' => $GLOBALS['LANG']->sL($file . 'activeFilterMode', TRUE),
				'dropToRemove' => $GLOBALS['LANG']->sL($file . 'dropToRemove', TRUE),
				'dropZoneElementRemoved' => $GLOBALS['LANG']->sL($file . 'dropZoneElementRemoved', TRUE),
				'dropZoneElementRestored' => $GLOBALS['LANG']->sL($file . 'dropZoneElementRestored', TRUE),
				'treeStructure' => $GLOBALS['LANG']->sL($file . 'treeStructure', TRUE),
				'temporaryMountPointIndicatorInfo' => $GLOBALS['LANG']->sl(
					'LLL:EXT:lang/locallang_core.xml:labels.temporaryDBmount',
					TRUE
				),
			),

			'Configuration' => array(
				'hideFilter' => $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.hideFilter'),
				'disableIconLinkToContextmenu' => $GLOBALS['BE_USER']->getTSConfigVal(
					'options.pageTree.disableIconLinkToContextmenu'
				),
				'indicator' => $this->getIndicators(),
				'temporaryMountPoint' => tx_pagetree_Commands::getMountPointPath(),
			),

			'Sprites' => array(
				'Filter' => t3lib_iconWorks::getSpriteIconClasses('actions-system-tree-search-open'),
				'NewNode' => t3lib_iconWorks::getSpriteIconClasses('actions-page-new'),
				'Refresh' => t3lib_iconWorks::getSpriteIconClasses('actions-system-refresh'),
				'InputClear' => t3lib_iconWorks::getSpriteIconClasses('actions-input-clear'),
				'TrashCan' => t3lib_iconWorks::getSpriteIconClasses('actions-edit-delete'),
				'TrashCanRestore' => t3lib_iconWorks::getSpriteIconClasses('actions-edit-restore'),
				'Info' => t3lib_iconWorks::getSpriteIconClasses('actions-document-info'),
			)
		);

		return $configuration;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/extdirect/class.tx_pagetree_extdirect_tree.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/classes/extdirect/class.tx_pagetree_extdirect_tree.php']);
}

?>