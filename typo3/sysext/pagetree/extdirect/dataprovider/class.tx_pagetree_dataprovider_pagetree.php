<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009 Sebastian Kurfuerst
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

class tx_pagetree_dataprovider_Pagetree extends tx_pagetree_dataprovider_AbstractTree {
	public $enableIcons = TRUE;
	public $backPath = '';
	protected $treeName = 'pages';

	/**
	 * PageTree
	 *
	 * @var tx_pagetree_Pagetree
	 */
	protected $pageTree;

	public function __construct() {
		$this->pageTree = t3lib_div::makeInstance('tx_pagetree_Pagetree');

		if ($GLOBALS['BE_USER']->uc['noMenuMode']
			&& strcmp($GLOBALS['BE_USER']->uc['noMenuMode'], 'icons')
		) {
			$this->enableIcons = FALSE;
		}
	}

	public function moveNodeToFirstChildOfDestination($movedNode, $destination) {
		$movedNode = $this->decodeTreeNodeId($movedNode);
		$destination = $this->decodeTreeNodeId($destination);

		$uid = intval($movedNode);
		$destination = intval($destination);
		$this->pageTree->move($uid, $destination);
	}

	public function moveNodeAfterDestination($movedNode, $destination) {
		$movedNode = $this->decodeTreeNodeId($movedNode);
		$destination = $this->decodeTreeNodeId($destination);

		$uid = intval($movedNode);
		$destination = intval($destination);
		$this->pageTree->move($uid, -$destination);
	}

	public function copyNodeToFirstChildOfDestination($copiedNode, $destination) {
		$copiedNode = $this->decodeTreeNodeId($copiedNode);
		$destination = $this->decodeTreeNodeId($destination);

		$uid = intval($copiedNode);
		$destination = intval($destination);
		$newPageId = $this->pageTree->copy($uid, $destination);

		return $this->getWholeRecordForIdAndPrepareItForResultList($newPageId);
	}

	public function copyNodeAfterDestination($copiedNode, $destination) {
		$copiedNode = $this->decodeTreeNodeId($copiedNode);
		$destination = $this->decodeTreeNodeId($destination);

		$uid = intval($copiedNode);
		$destination = intval($destination);

		$newPageId = $this->pageTree->copy($uid, -$destination);

		return $this->getWholeRecordForIdAndPrepareItForResultList($newPageId);
	}

	public function insertNodeToFirstChildOfDestination($parentNode, $pageType) {
		$parentNode = intval($this->decodeTreeNodeId($parentNode));

		$newPageId = $this->pageTree->create($parentNode, $parentNode, $pageType);
		return $this->getWholeRecordForIdAndPrepareItForResultList($newPageId);
	}

	public function insertNodeAfterDestination($parentNode, $destination, $pageType) {
		$parentNode = intval($this->decodeTreeNodeId($parentNode));
		$destination = intval($this->decodeTreeNodeId($destination));

		$newPageId = $this->pageTree->create($parentNode, -$destination, $pageType);
		return $this->getWholeRecordForIdAndPrepareItForResultList($newPageId);
	}


	public function deleteNode($nodeId) {
		$id = $this->decodeTreeNodeId($nodeId);
		$this->pageTree->remove($id);
	}

	public function undeleteNode($nodeId) {
		$id = $this->decodeTreeNodeId($nodeId);
		$this->pageTree->restore($id);
	}

	protected function getWholeRecordForIdAndPrepareItForResultList($newNode) {
		$resultRow = t3lib_BEfunc::getRecordWSOL(
			'pages',
			$newNode,
			$fields = '*',
			$where = '',
			$useDeleteClause = TRUE,
			$GLOBALS['BE_USER']->uc['currentPageTreeLanguage']
		);
		return $this->prepareRecordForResultList($resultRow);
	}

	public function getFilteredTree($searchString) {
		$records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid',
			'pages',
				'title LIKE ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('%' . $searchString . '%', 'pages') .
						t3lib_BEfunc::deleteClause('pages') .
						t3lib_BEfunc::versioningPlaceholderClause('pages') . ' AND ' .
						$GLOBALS['BE_USER']->getPagePermsClause(1)
		);

		$result = array();
		foreach ($records as $singleRecord) {
			$rootline = t3lib_BEfunc::BEgetRootLine($singleRecord['uid']);
			$rootline = array_reverse($rootline);
			array_shift($rootline);
			$currentNode = &$result; // what the fuck? Who codes such unmaintainable stuff?

			foreach ($rootline as $rootlineElement) {
				$rootlineElement['_subpages'] = 1;
				if (isset($currentNode[$rootlineElement['uid']])) {
					$currentNode = &$currentNode[$rootlineElement['uid']]['children'];
				}
				else {
					$currentNode[$rootlineElement['uid']] = $this->prepareRecordForResultList($rootlineElement);
					$currentNode[$rootlineElement['uid']]['text'] = preg_replace('/(' .
							preg_quote($searchString, '/') . ')/i', '<strong class="highlight">$1</strong>',
						$currentNode[$rootlineElement['uid']]['text']);


					$currentNode[$rootlineElement['uid']]['children'] = array();
					$currentNode = &$currentNode[$rootlineElement['uid']]['children'];
				}
			}
		}
		$this->convertChildrenToUnnamedArray($result);
		return $result;
	}

	private function convertChildrenToUnnamedArray(&$array) {
		$array = array_values($array);
		foreach ($array as &$value) {
			if (isset($value['children']) && is_array($value['children'])) {
				$this->convertChildrenToUnnamedArray($value['children']);
			}
		}
	}

	protected function getRecordsForNextTreeLevel($id) {
		if ($id === 'root') {
			return $this->pageTree->getTreeMounts();
		} else {
			return $this->pageTree->getSubPages($id);
		}
	}

	protected function getTreeNodePropertiesForRecord($record, $rootline) {
		if ($this->pageTree->getTsConfigOptionForPagetree('showNavTitle')) {
			$fields = array('nav_title', 'title');
		} else {
			$fields = array('title', 'nav_title');
		}

		$textSourceField = '';
		foreach ($fields as $field) {
			if (!empty($record[$field])) {
				$text = $record[$field];
				$textSourceField = $field;
				break;
			}
		}

		$languageOverlayIcon = '';
		if ($record['_PAGES_OVERLAY']) {
			$currentTreeLanguage = intval($GLOBALS['BE_USER']->uc['currentPageTreeLanguage']);
			$languageRecord = $this->getLanguage($currentTreeLanguage);
			$languageShortcut = $this->getLanguageShortcutFromFile($languageRecord['flag']);
			$languageOverlayIcon = t3lib_iconWorks::getSpriteIcon(
				'flags-' . $languageShortcut . '-overlay'
			);
			unset($languageRecord, $languageShortcut);
		}

		if ($record['uid'] !== 0) {
			$spriteIconCode = t3lib_iconWorks::getSpriteIconForRecord(
				'pages',
				$record,
				array(
					'html' => $languageOverlayIcon,
				)
			);
		} else {
			$spriteIconCode = t3lib_iconWorks::getSpriteIcon('apps-pagetree-root');
		}
		return array(
			'record' => $record,
			'id' => $this->encodeTreeNodeId($record['uid'], $rootline),
			'qtip' => 'ID: ' . $record['uid'],
			'leaf' => $record['_subpages'] == 0,
			'text' => $text,
			'prefixText' => $this->getPrefixForDisplayedTitle($record), // This is the prefix before the title string
			'spriteIconCode' => $spriteIconCode,
			'_meta' => array('numSubPages' => $record['_subpages']),
			'properties' => array(
				'clickCallback' => 'TYPO3.Components.PageTree.PageActions.singleClick',
				'textSourceField' => $textSourceField,
				'realId' => $record['uid']
			)
		);
	}

	public function getSpriteIconClasses($icon) {
		return t3lib_iconWorks::getSpriteIconClasses($icon);
	}

	// @todo respect tsconfig options
	protected function getActionsForRecord($record) {
		return $record['_actions'];
	}

	protected function getPrefixForDisplayedTitle($row) {
		$prefix = '';

		if ($this->pageTree->getTsConfigOptionForPagetree('showPageIdWithTitle')) {
			$prefix .= $row['uid'];
		}
		return $prefix;
	}

	public function getPageInformation($pageId, $fields) {
		return $this->pageTree->getPageInformationForGivenFields($pageId, $fields);
	}

	protected function getNextContextMenuLevel($typoScriptConfiguration, $level) {
		if ($level > 5) {
			return array();
		}

		$type = '';
		$contextMenuItems = array();
		foreach ($typoScriptConfiguration as $index => $typoScriptItem) {
			$hash = md5(microtime());

			if (substr($index, -1) === '.') {
				switch ($type) {
					case 'SUBMENU':
						$contextMenuItems['--submenu-' . $hash . '--'] =
								$this->getNextContextMenuLevel($typoScriptItem, ++$level);

						$contextMenuItems['--submenu-' . $hash . '--']['text'] =
								$this->getLabel($typoScriptItem['label']);
						break;

					case 'ITEM':
						// transform icon and text
						$contextMenuItems[$index] = array(
							'text' => $this->getLabel($typoScriptItem['label'])
						);

						// push additional attributes
						$contextMenuItems[$index] = array_merge(
							$typoScriptItem,
							$contextMenuItems[$index]
						);

						if ($this->enableIcons) {
							if (!empty($typoScriptItem['icon'])) {
								$contextMenuItems[$index]['icon'] =
										$this->getIcon($typoScriptItem['icon']);
							} elseif (isset($typoScriptItem['outerIcon'])) {
								$contextMenuItems[$index] = array_merge(
									$contextMenuItems[$index],
									$this->getIconClassFromIcon($typoScriptItem['outerIcon'])
								);
							}
						} else {
							$contextMenuItems[$index]['icon'] = '';
						}
						break;

					default:
						break;
				}
			} else {
				$type = $typoScriptItem;

				// add divider
				if ($type === 'DIVIDER') {
					$contextMenuItems['--divider-' . $hash . '--'] = array(
						'action' => 'divider'
					);
				}
			}
		}

		return $contextMenuItems;
	}

	public function getContextMenuConfiguration() {
		// @TODO defaults must be considered
		$contextMenuItemTypoScriptConfiguration = $GLOBALS['BE_USER']->getTSConfig(
			'options.contextMenu.table.pages.items'
		);
		$contextMenuItemTypoScriptConfiguration = $contextMenuItemTypoScriptConfiguration['properties'];

		$contextMenuItems = $this->getNextContextMenuLevel(
			$contextMenuItemTypoScriptConfiguration,
			0
		);
		return $contextMenuItems;
	}

	/**
	 * Fetches the attributes for an action inside the context menu.
	 * @todo optimize to prevent much useless ajax calls...
	 *
	 * @param int $id page uid
	 * @param string $menuItemId clickmenu item identifier
	 */
	public function getAttributesForContextMenu($id, $menuItemId) {
		$this->decodeTreeNodeId($id);

		switch ($menuItemId) {
			default:
				$attributes = array();
				break;
		}

		return $attributes;
	}

	/**
	 * Helper function to generate the neede css classes for img display with overlay
	 *
	 * @param string $icon icon identifier
	 */
	protected function getIconClassFromIcon($icon) {
		return array(
			//			'hrefTarget' => $iconClass[0], // href target is used as a small hack for the template function of the menu.Item
			'iconCls' => $this->getSpriteIconClasses($icon)
		);
	}

	/**
	 * Returns the page view link
	 *
	 * @param int $id page id
	 * @param unknown $workspacePreview ???
	 * @return string
	 */
	public function getViewLink($id, $workspacePreview) {
		$id = $this->decodeTreeNodeId($id);
		return $this->pageTree->getViewLink($id, $workspacePreview);
	}

	/**
	 *
	 * @param string $id page id (can be numerical or like "mp-12" in case of mount-points ,...)
	 * @param string $title
	 * @param string $textSourceField
	 */
	public function setPageTitle($id, $title, $textSourceField) {
		$id = $this->decodeTreeNodeId($id);
		$this->pageTree->updateTextInputField($id, $title, $textSourceField);
	}

	/**
	 * Enables or disables a page and returns the new node
	 *
	 * @param string $id page id (can be numerical or like "mp-12" in case of mount-points ,...)
	 * @param boolean $enabled true for enabling and false for disabling
	 * @return array new node
	 */
	public function tooglePageVisibility($id, $enabled) {
		$id = $this->decodeTreeNodeId($id);
		$this->pageTree->updateTextInputField($id, $enabled, 'hidden');

		$node = $this->getWholeRecordForIdAndPrepareItForResultList($id);
		return $node;
	}

	/**
	 * Returns the localized list of doktypes to display
	 *
	 * see User TSconfig: options.pageTree.doktypesToShowInNewPageDragArea
	 */
	public function getNodeTypes() {
		$doktypes = t3lib_div::trimExplode(
			',',
			$this->pageTree->getTsConfigOptionForPagetree('doktypesToShowInNewPageDragArea')
		);

		$output = array();
		foreach ($doktypes as $doktype) {
			$label = $this->getLabel('LLL:EXT:pagetree/locallang_pagetree.xml:page.doktype.' . $doktype);
			$output[] = array(
				'nodeType' => $doktype,
				'title' => $label,
				'cls' => 'topPanel-button ' . $this->getSpriteIconClasses(
					$GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$doktype]
				),
			);
		}
		return $output;
	}

	/**
	 * Returns the language shortcut from a language file
	 *
	 * @param string $file language flag with or without the related directory
	 * @return mixed language shortcut or boolean false
	 */
	protected function getLanguageShortcutFromFile($file) {
		if (strpos($file, '/') !== FALSE) {
			$file = basename($file);
		}

		return substr($file, 0, strrpos($file, '.'));
	}

	/**
	 * Returns a language record defined by the id parameter
	 *
	 * @param int $uid language id
	 * @return array
	 */
	protected function getLanguage($uid) {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'flag',
			'sys_language',
				'pid=0 AND uid=' . $uid .
						t3lib_BEfunc::deleteClause('sys_language')
		);

		$record = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return $record;
	}

	/**
	 * Returns an array of system languages with parameters based on the properties
	 * of Ext.Button.
	 *
	 * @return array
	 */
	public function getLanguages() {
		$languageRecords = t3lib_befunc::getSystemLanguages();

		$output = array();
		foreach ($languageRecords as $languageRecord) {
			$languageShortcut = $this->getLanguageShortcutFromFile($languageRecord[2]);
			if ($languageShortcut === FALSE) {
				$languageShortcut = 'europeanunion';
			}

			$output[] = array(
				'language' => $languageRecord[1],
				'languageShortcut' => $languageShortcut,
				'cls' => 'topPanel-button ' .
						$this->getSpriteIconClasses('flags-' . $languageShortcut),
			);
		}

		return $output;
	}

	/**
	 * Returns the european flag sprite icon css classes
	 *
	 * @TODO What if a flag is added to the core, but isn't inside the sprite?
	 * @return string
	 */
	public function getIconForCurrentLanguage() {
		$currentTreeLanguage = intval($GLOBALS['BE_USER']->uc['currentPageTreeLanguage']);

		$icon = 'flags-europeanunion';
		if ($currentTreeLanguage !== 0) {
			$languageRecord = $this->getLanguage($currentTreeLanguage);
			$icon = 'flags-' . $this->getLanguageShortcutFromFile($languageRecord['flag']);
		}

		return $this->getSpriteIconClasses($icon);
	}

	/**
	 * Saves the given language id into the backend user configuration array
	 *
	 * @param int $languageId
	 * @return void
	 */
	public function saveCurrentTreeLanguage($languageId) {
		$GLOBALS['BE_USER']->backendSetUC();
		$GLOBALS['BE_USER']->uc['currentPageTreeLanguage'] = intval($languageId);
		$GLOBALS['BE_USER']->writeUC();
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/extdirect/dataprovider/class.t3lib_tree_dataprovider_pagetree.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/sysext/pagetree/extdirect/dataprovider/class.t3lib_tree_dataprovider_pagetree.php']);
}

?>
