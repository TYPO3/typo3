<?php
namespace TYPO3\CMS\Backend\Tree\Pagetree;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 TYPO3 Tree Team <http://forge.typo3.org/projects/typo3v4-extjstrees>
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
 */
class ExtdirectTreeDataProvider extends \TYPO3\CMS\Backend\Tree\AbstractExtJsTree {

	/**
	 * Data Provider
	 *
	 * @var \TYPO3\CMS\Backend\Tree\Pagetree\DataProvider
	 */
	protected $dataProvider = NULL;

	/**
	 * Sets the data provider
	 *
	 * @return void
	 */
	protected function initDataProvider() {
		/** @var $dataProvider \TYPO3\CMS\Backend\Tree\Pagetree\DataProvider */
		$dataProvider = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\DataProvider');
		$this->setDataProvider($dataProvider);
	}

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
	 * @param integer $nodeId
	 * @param stdClass $nodeData
	 * @return array
	 */
	public function getNextTreeLevel($nodeId, $nodeData) {
		$this->initDataProvider();
		/** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
		$node = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNode', (array) $nodeData);
		if ($nodeId === 'root') {
			$nodeCollection = $this->dataProvider->getTreeMounts();
		} else {
			$nodeCollection = $this->dataProvider->getNodes($node, $node->getMountPoint());
		}
		return $nodeCollection->toArray();
	}

	/**
	 * Returns a tree that only contains elements that match the given search string
	 *
	 * @param integer $nodeId
	 * @param stdClass $nodeData
	 * @param string $searchFilter
	 * @return array
	 */
	public function getFilteredTree($nodeId, $nodeData, $searchFilter) {
		if (strval($searchFilter) === '') {
			return array();
		}
		/** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
		$node = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\PagetreeNode', (array) $nodeData);
		$this->initDataProvider();
		if ($nodeId === 'root') {
			$nodeCollection = $this->dataProvider->getTreeMounts($searchFilter);
		} else {
			$nodeCollection = $this->dataProvider->getFilteredNodes($node, $searchFilter, $node->getMountPoint());
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
		$map = array(
			1 => 'LLL:EXT:lang/locallang_tca.xlf:doktype.I.0',
			3 => 'LLL:EXT:cms/locallang_tca.xlf:pages.doktype.I.8',
			4 => 'LLL:EXT:cms/locallang_tca.xlf:pages.doktype.I.2',
			6 => 'LLL:EXT:cms/locallang_tca.xlf:pages.doktype.I.4',
			7 => 'LLL:EXT:cms/locallang_tca.xlf:pages.doktype.I.5',
			199 => 'LLL:EXT:cms/locallang_tca.xlf:pages.doktype.I.7',
			254 => 'LLL:EXT:lang/locallang_tca.xlf:doktype.I.folder',
			255 => 'LLL:EXT:lang/locallang_tca.xlf:doktype.I.2'
		);
		$doktypes = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.doktypesToShowInNewPageDragArea'));
		$output = array();
		$allowedDoktypes = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $GLOBALS['BE_USER']->groupData['pagetypes_select']);
		$isAdmin = $GLOBALS['BE_USER']->isAdmin();
		foreach ($doktypes as $doktype) {
			if (!$isAdmin && !in_array($doktype, $allowedDoktypes)) {
				continue;
			}
			$label = $GLOBALS['LANG']->sL($map[$doktype], TRUE);
			$spriteIcon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses($GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$doktype]);
			$output[] = array(
				'nodeType' => $doktype,
				'cls' => 'typo3-pagetree-topPanel-button',
				'iconCls' => $spriteIcon,
				'title' => $label,
				'tooltip' => $label
			);
		}
		return $output;
	}

	/**
	 * Returns
	 *
	 * @return array
	 */
	public function getIndicators() {
		/** @var $indicatorProvider \TYPO3\CMS\Backend\Tree\Pagetree\Indicator */
		$indicatorProvider = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\Pagetree\\Indicator');
		$indicatorHtmlArr = $indicatorProvider->getAllIndicators();
		$indicator = array(
			'html' => implode(' ', $indicatorHtmlArr),
			'_COUNT' => count($indicatorHtmlArr)
		);
		return $indicator;
	}

	/**
	 * Returns the language labels, sprites and configuration options for the pagetree
	 *
	 * @return void
	 */
	public function loadResources() {
		$file = 'LLL:EXT:lang/locallang_core.xlf:';
		$indicators = $this->getIndicators();
		$configuration = array(
			'LLL' => array(
				'copyHint' => $GLOBALS['LANG']->sL($file . 'tree.copyHint', TRUE),
				'fakeNodeHint' => $GLOBALS['LANG']->sL($file . 'mess.please_wait', TRUE),
				'activeFilterMode' => $GLOBALS['LANG']->sL($file . 'tree.activeFilterMode', TRUE),
				'dropToRemove' => $GLOBALS['LANG']->sL($file . 'tree.dropToRemove', TRUE),
				'buttonRefresh' => $GLOBALS['LANG']->sL($file . 'labels.refresh', TRUE),
				'buttonNewNode' => $GLOBALS['LANG']->sL($file . 'tree.buttonNewNode', TRUE),
				'buttonFilter' => $GLOBALS['LANG']->sL($file . 'tree.buttonFilter', TRUE),
				'dropZoneElementRemoved' => $GLOBALS['LANG']->sL($file . 'tree.dropZoneElementRemoved', TRUE),
				'dropZoneElementRestored' => $GLOBALS['LANG']->sL($file . 'tree.dropZoneElementRestored', TRUE),
				'searchTermInfo' => $GLOBALS['LANG']->sL($file . 'tree.searchTermInfo', TRUE),
				'temporaryMountPointIndicatorInfo' => $GLOBALS['LANG']->sl($file . 'labels.temporaryDBmount', TRUE),
				'deleteDialogTitle' => $GLOBALS['LANG']->sL('LLL:EXT:cms/layout/locallang.xml:deleteItem', TRUE),
				'deleteDialogMessage' => $GLOBALS['LANG']->sL('LLL:EXT:cms/layout/locallang.xml:deleteWarning', TRUE),
				'recursiveDeleteDialogMessage' => $GLOBALS['LANG']->sL('LLL:EXT:cms/layout/locallang.xml:recursiveDeleteWarning', TRUE)
			),
			'Configuration' => array(
				'hideFilter' => $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.hideFilter'),
				'displayDeleteConfirmation' => $GLOBALS['BE_USER']->jsConfirmation(4),
				'canDeleteRecursivly' => $GLOBALS['BE_USER']->uc['recursiveDelete'] == TRUE,
				'disableIconLinkToContextmenu' => $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.disableIconLinkToContextmenu'),
				'indicator' => $indicators['html'],
				'temporaryMountPoint' => \TYPO3\CMS\Backend\Tree\Pagetree\Commands::getMountPointPath()
			),
			'Sprites' => array(
				'Filter' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('actions-system-tree-search-open'),
				'NewNode' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('actions-page-new'),
				'Refresh' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('actions-system-refresh'),
				'InputClear' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('actions-input-clear'),
				'TrashCan' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('actions-edit-delete'),
				'TrashCanRestore' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('actions-edit-restore'),
				'Info' => \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconClasses('actions-document-info')
			)
		);
		return $configuration;
	}

}


?>