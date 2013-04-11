<?php
namespace TYPO3\CMS\Backend\Tree\View;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Generate a folder tree,
 * specially made for browsing folders in the File module
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor René Fritz <r.fritz@colorcube.de>
 */
class FolderTreeView extends \TYPO3\CMS\Backend\Tree\View\AbstractTreeView {

	/**
	 * The users' file Storages
	 *
	 * @var \TYPO3\CMS\Core\Resource\ResourceStorage[]
	 */
	protected $storages = NULL;

	/**
	 * @var array
	 */
	protected $storageHashNumbers;

	/**
	 * Indicates, whether the AJAX call was successful,
	 * i.e. the requested page has been found
	 *
	 * @var boolean
	 */
	protected $ajaxStatus = FALSE;

	/**
	 * Constructor function of the class
	 */
	public function __construct() {
		parent::init();
		$this->storages = $GLOBALS['BE_USER']->getFileStorages();
		$this->treeName = 'folder';
		// Don't apply any title
		$this->titleAttrib = '';
		$this->domIdPrefix = 'folder';
	}

	/**
	 * Generate the plus/minus icon for the browsable tree.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject Entry folder object
	 * @param integer $subFolderCounter The current entry number
	 * @param integer $totalSubFolders The total number of entries. If equal to $a, a "bottom" element is returned.
	 * @param integer $nextCount The number of sub-elements to the current element.
	 * @param boolean $isExpanded The element was expanded to render subelements if this flag is set.
	 * @return string Image tag with the plus/minus icon.
	 * @internal
	 * @see \TYPO3\CMS\Backend\Tree\View\PageTreeView::PMicon()
	 */
	public function PMicon(\TYPO3\CMS\Core\Resource\Folder $folderObject, $subFolderCounter, $totalSubFolders, $nextCount, $isExpanded) {
		$PM = $nextCount ? ($isExpanded ? 'minus' : 'plus') : 'join';
		$BTM = $subFolderCounter == $totalSubFolders ? 'bottom' : '';
		$icon = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, ('gfx/ol/' . $PM . $BTM . '.gif'), 'width="18" height="16"') . ' alt="" />';
		if ($nextCount) {
			$cmd = $this->generateExpandCollapseParameter($this->bank, !$isExpanded, $folderObject);
			$icon = $this->PMiconATagWrap($icon, $cmd, !$isExpanded);
		}
		return $icon;
	}

	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param string $icon HTML string to wrap, probably an image tag.
	 * @param string $cmd Command for 'PM' get var
	 * @param boolean $isExpand Whether to be expanded
	 * @return string Link-wrapped input string
	 * @internal
	 */
	public function PMiconATagWrap($icon, $cmd, $isExpand = TRUE) {
		if ($this->thisScript) {
			// Activates dynamic AJAX based tree
			$js = htmlspecialchars('Tree.load(\'' . $cmd . '\', ' . intval($isExpand) . ', this);');
			return '<a class="pm" onclick="' . $js . '">' . $icon . '</a>';
		} else {
			return $icon;
		}
	}

	/**
	 * Wrapping the folder icon
	 *
	 * @param string $icon The image tag for the icon
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject The row for the current element
	 * @return string The processed icon input value.
	 * @internal
	 */
	public function wrapIcon($icon, \TYPO3\CMS\Core\Resource\Folder $folderObject) {
		// Add title attribute to input icon tag
		$theFolderIcon = $this->addTagAttributes($icon, $this->titleAttrib ? $this->titleAttrib . '="' . $this->getTitleAttrib($folderObject) . '"' : '');
		// Wrap icon in click-menu link.
		if (!$this->ext_IconMode) {
			// Check storage access to wrap with click menu
			if ($folderObject->getStorage()->hasFolder('/')) {
				$theFolderIcon = $GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon($theFolderIcon, $folderObject->getCombinedIdentifier(), '', 0);
			}
		} elseif (!strcmp($this->ext_IconMode, 'titlelink')) {
			$aOnClick = 'return jumpTo(\'' . $this->getJumpToParam($folderObject) . '\',this,\'' . $this->domIdPrefix . $this->getId($folderObject) . '\',' . $this->bank . ');';
			$theFolderIcon = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $theFolderIcon . '</a>';
		}
		return $theFolderIcon;
	}

	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param string $title Title string
	 * @param \TYPO3\CMS\Core\Resource\Folder	$folderObject the folder record
	 * @param integer $bank Bank pointer (which mount point number)
	 * @return string
	 * @internal
	 */
	public function wrapTitle($title, \TYPO3\CMS\Core\Resource\Folder $folderObject, $bank = 0) {
		// Check storage access to wrap with click menu
		if (!$folderObject->getStorage()->hasFolder('/')) {
			return $title;
		}
		$aOnClick = 'return jumpTo(\'' . $this->getJumpToParam($folderObject) . '\', this, \'' . $this->domIdPrefix . $this->getId($folderObject) . '\', ' . $bank . ');';
		$CSM = ' oncontextmenu="' . htmlspecialchars($GLOBALS['TBE_TEMPLATE']->wrapClickMenuOnIcon('', $folderObject->getCombinedIdentifier(), '', 0, ('&bank=' . $this->bank), '', TRUE)) . '"';

		return '<a href="#" title="' . htmlspecialchars($title) . '" onclick="' . htmlspecialchars($aOnClick) . '"' . $CSM . '>' . $title . '</a>';
	}

	/**
	 * Returns the id from the record - for folders, this is an md5 hash.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject The folder object
	 * @return integer The "uid" field value.
	 */
	public function getId(\TYPO3\CMS\Core\Resource\Folder $folderObject) {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::md5Int($folderObject->getCombinedIdentifier());
	}

	/**
	 * Returns jump-url parameter value.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject The folder object
	 * @return string The jump-url parameter.
	 */
	public function getJumpToParam(\TYPO3\CMS\Core\Resource\Folder $folderObject) {
		return rawurlencode($folderObject->getCombinedIdentifier());
	}

	/**
	 * Returns the title for the input record. If blank, a "no title" labele (localized) will be returned.
	 * '_title' is used for setting an alternative title for folders.
	 *
	 * @param array $row The input row array (where the key "_title" is used for the title)
	 * @param integer $titleLen Title length (30)
	 * @return string The title
	 */
	public function getTitleStr($row, $titleLen = 30) {
		return $row['_title'] ? $row['_title'] : parent::getTitleStr($row, $titleLen);
	}

	/**
	 * Returns the value for the image "title" attribute
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject The folder to be used
	 * @return 	string The attribute value (is htmlspecialchared() already)
	 * @todo Define visibility
	 */
	public function getTitleAttrib(\TYPO3\CMS\Core\Resource\Folder $folderObject) {
		return htmlspecialchars($folderObject->getName());
	}

	/**
	 * Will create and return the HTML code for a browsable tree of folders.
	 * Is based on the mounts found in the internal array ->MOUNTS (set in the constructor)
	 *
	 * @return string HTML code for the browsable tree
	 */
	public function getBrowsableTree() {
		// Get stored tree structure AND updating it if needed according to incoming PM GET var.
		$this->initializePositionSaving();
		// Init done:
		$treeItems = array();
		// Traverse mounts:
		foreach ($this->storages as $storageObject) {
			$this->getBrowseableTreeForStorage($storageObject);
			// Add tree:
			$treeItems = array_merge($treeItems, $this->tree);
			// if this is an AJAX call, don't run through all mounts, only
			// show the expansion of the current one, not the rest of the mounts
			if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {

			}
		}
		return $this->printTree($treeItems);
	}

	/**
	 * Get a tree for one storage
	 *
	 * @param \TYPO3\CMS\Core\Resource\ResourceStorage $storageObject
	 * @return void
	 */
	public function getBrowseableTreeForStorage(\TYPO3\CMS\Core\Resource\ResourceStorage $storageObject) {
		// If there are filemounts, show each, otherwise just the rootlevel folder
		$fileMounts = $storageObject->getFileMounts();
		$rootLevelFolders = array();
		if (count($fileMounts)) {
			foreach ($fileMounts as $fileMountInfo) {
				$rootLevelFolders[] = array(
					'folder' => $fileMountInfo['folder'],
					'name' => $fileMountInfo['title']
				);
			}
		} else {
			$rootLevelFolders[] = array(
				'folder' => $storageObject->getRootLevelFolder(),
				'name' => $storageObject->getName()
			);
		}
		// Clean the tree
		$this->reset();
		// Go through all "root level folders" of this tree (can be the rootlevel folder or any file mount points)
		foreach ($rootLevelFolders as $rootLevelFolderInfo) {
			/** @var $rootLevelFolder \TYPO3\CMS\Core\Resource\Folder */
			$rootLevelFolder = $rootLevelFolderInfo['folder'];
			$rootLevelFolderName = $rootLevelFolderInfo['name'];
			$folderHashSpecUID = \TYPO3\CMS\Core\Utility\GeneralUtility::md5int($rootLevelFolder->getCombinedIdentifier());
			$this->specUIDmap[$folderHashSpecUID] = $rootLevelFolder->getCombinedIdentifier();
			// Hash key
			$storageHashNumber = $this->getShortHashNumberForStorage($storageObject, $rootLevelFolder);
			// Set first:
			$this->bank = $storageHashNumber;
			$isOpen = $this->stored[$storageHashNumber][$folderHashSpecUID] || $this->expandFirst;
			// Set PM icon:
			$cmd = $this->generateExpandCollapseParameter($this->bank, !$isOpen, $rootLevelFolder);
			if (!$storageObject->isBrowsable() || $this->getNumberOfSubfolders($storageObject->getRootLevelFolder()) === 0) {
				$rootIcon = 'blank';
			} elseif (!$isOpen) {
				$rootIcon = 'plusonly';
			} else {
				$rootIcon = 'minusonly';
			}
			$icon = '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, ('gfx/ol/' . $rootIcon . '.gif')) . ' alt="" />';
			// Only link icon if storage is browseable
			if (in_array($rootIcon, array('minusonly', 'plusonly'))) {
				$firstHtml = $this->PM_ATagWrap($icon, $cmd);
			} else {
				$firstHtml = $icon;
			}
			// @todo: create sprite icons for user/group mounts etc
			if ($storageObject->isBrowsable() === FALSE) {
				$icon = 'apps-filetree-folder-locked';
			} else {
				$icon = 'apps-filetree-root';
			}
			// Mark a storage which is not online, as offline
			// maybe someday there will be a special icon for this
			if ($storageObject->isOnline() === FALSE) {
				$rootLevelFolderName .= ' (' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file.xlf:sys_file_storage.isOffline') . ')';
			}
			// Preparing rootRec for the mount
			$firstHtml .= $this->wrapIcon(\TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($icon), $rootLevelFolder);
			$row = array(
				'uid' => $folderHashSpecUID,
				'title' => $rootLevelFolderName,
				'path' => $rootLevelFolder->getCombinedIdentifier(),
				'folder' => $rootLevelFolder
			);
			// Add the storage root to ->tree
			$this->tree[] = array(
				'HTML' => $firstHtml,
				'row' => $row,
				'bank' => $this->bank,
				// hasSub is TRUE when the root of the storage is expanded
				'hasSub' => $isOpen && $storageObject->isBrowsable()
			);
			// If the mount is expanded, go down:
			if ($isOpen && $storageObject->isBrowsable()) {
				// Set depth:
				$this->getFolderTree($rootLevelFolder, 999);
			}
		}
	}

	/**
	 * Fetches the data for the tree
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject the folderobject
	 * @param integer $depth Max depth (recursivity limit)
	 * @param string $type HTML-code prefix for recursive calls.
	 * @return integer The count of items on the level
	 * @see getBrowsableTree()
	 */
	public function getFolderTree(\TYPO3\CMS\Core\Resource\Folder $folderObject, $depth = 999, $type = '') {
		$depth = intval($depth);

		// This generates the directory tree
		/* array of \TYPO3\CMS\Core\Resource\Folder */
		$subFolders = $folderObject->getSubfolders();
		$subFolders = \TYPO3\CMS\Core\Resource\Utility\ListUtility::resolveSpecialFolderNames($subFolders);
		uksort($subFolders, 'strnatcasecmp');

		$totalSubFolders = count($subFolders);
		$HTML = '';
		$subFolderCounter = 0;
		foreach ($subFolders as $subFolderName => $subFolder) {
			$subFolderCounter++;
			// Reserve space.
			$this->tree[] = array();
			// Get the key for this space
			end($this->tree);
			$treeKey = key($this->tree);
			$specUID = \TYPO3\CMS\Core\Utility\GeneralUtility::md5int($subFolder->getCombinedIdentifier());
			$this->specUIDmap[$specUID] = $subFolder->getCombinedIdentifier();
			$row = array(
				'uid' => $specUID,
				'path' => $subFolder->getCombinedIdentifier(),
				'title' => $subFolderName,
				'folder' => $subFolder
			);
			// Make a recursive call to the next level
			if ($depth > 1 && $this->expandNext($specUID)) {
				$nextCount = $this->getFolderTree($subFolder, $depth - 1, $type);
				// Set "did expand" flag
				$isOpen = 1;
			} else {
				$nextCount = $this->getNumberOfSubfolders($subFolder);
				// Clear "did expand" flag
				$isOpen = 0;
			}
			// Set HTML-icons, if any:
			if ($this->makeHTML) {
				$HTML = $this->PMicon($subFolder, $subFolderCounter, $totalSubFolders, $nextCount, $isOpen);
				if ($subFolder->checkActionPermission('write')) {
					$type = '';
					$overlays = array();
				} else {
					$type = 'readonly';
					$overlays = array('status-overlay-locked' => array());
				}
				if ($isOpen) {
					$icon = 'apps-filetree-folder-opened';
				} else {
					$icon = 'apps-filetree-folder-default';
				}
				$role = $subFolder->getRole();
				if ($role !== \TYPO3\CMS\Core\Resource\FolderInterface::ROLE_DEFAULT) {
					$row['_title'] = '<strong>' . $subFolderName . '</strong>';
				}
				if ($role === \TYPO3\CMS\Core\Resource\FolderInterface::ROLE_TEMPORARY) {
					$icon = 'apps-filetree-folder-temp';
				} elseif ($role === \TYPO3\CMS\Core\Resource\FolderInterface::ROLE_RECYCLER) {
					$icon = 'apps-filetree-folder-recycler';
				}
				$icon = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($icon, array('title' => $subFolderName), $overlays);
				$HTML .= $this->wrapIcon($icon, $subFolder);
			}
			// Finally, add the row/HTML content to the ->tree array in the reserved key.
			$this->tree[$treeKey] = array(
				'row' => $row,
				'HTML' => $HTML,
				'hasSub' => $nextCount && $this->expandNext($specUID),
				'isFirst' => $subFolderCounter == 1,
				'isLast' => FALSE,
				'invertedDepth' => $depth,
				'bank' => $this->bank
			);
		}
		if ($subFolderCounter > 0) {
			$this->tree[$treeKey]['isLast'] = TRUE;
		}
		return $totalSubFolders;
	}

	/**
	 * Compiles the HTML code for displaying the structure found inside the ->tree array
	 *
	 * @param array|string $treeItems "tree-array" - if blank string, the internal ->tree array is used.
	 * @return string The HTML code for the tree
	 */
	public function printTree($treeItems = '') {
		$doExpand = FALSE;
		$doCollapse = FALSE;
		$ajaxOutput = '';
		$titleLength = intval($this->BE_USER->uc['titleLen']);
		if (!is_array($treeItems)) {
			$treeItems = $this->tree;
		}
		$out = '
			<!-- TYPO3 folder tree structure. -->
			<ul class="tree" id="treeRoot">
		';
		// Evaluate AJAX request
		if (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_AJAX) {
			list(, $expandCollapseCommand, $expandedFolderHash, ) = $this->evaluateExpandCollapseParameter();
			if ($expandCollapseCommand == 1) {
				// We don't know yet. Will be set later.
				$invertedDepthOfAjaxRequestedItem = 0;
				$doExpand = TRUE;
			} else {
				$doCollapse = TRUE;
			}
		}
		// We need to count the opened <ul>'s every time we dig into another level,
		// so we know how many we have to close when all children are done rendering
		$closeDepth = array();
		foreach ($treeItems as $treeItem) {
			/** @var $folderObject \TYPO3\CMS\Core\Resource\Folder */
			$folderObject = $treeItem['row']['folder'];
			$classAttr = $treeItem['row']['_CSSCLASS'];
			$folderIdentifier = $folderObject->getCombinedIdentifier();
			// this is set if the AJAX request has just opened this folder (via the PM command)
			$isExpandedFolderIdentifier = $expandedFolderHash == \TYPO3\CMS\Core\Utility\GeneralUtility::md5int($folderIdentifier);
			$idAttr = htmlspecialchars($this->domIdPrefix . $this->getId($folderObject) . '_' . $treeItem['bank']);
			$itemHTML = '';
			// If this item is the start of a new level,
			// then a new level <ul> is needed, but not in ajax mode
			if ($treeItem['isFirst'] && !$doCollapse && !($doExpand && $isExpandedFolderIdentifier)) {
				$itemHTML = '<ul>
';
			}
			// Add CSS classes to the list item
			if ($treeItem['hasSub']) {
				$classAttr .= ' expanded';
			}
			if ($treeItem['isLast']) {
				$classAttr .= ' last';
			}
			$itemHTML .= '
				<li id="' . $idAttr . '" ' . ($classAttr ? ' class="' . trim($classAttr) . '"' : '') . '><div class="treeLinkItem">' . $treeItem['HTML'] . $this->wrapTitle($this->getTitleStr($treeItem['row'], $titleLength), $folderObject, $treeItem['bank']) . '</div>';
			if (!$treeItem['hasSub']) {
				$itemHTML .= '</li>
';
			}
			// We have to remember if this is the last one
			// on level X so the last child on level X+1 closes the <ul>-tag
			if ($treeItem['isLast'] && !($doExpand && $isExpandedFolderIdentifier)) {
				$closeDepth[$treeItem['invertedDepth']] = 1;
			}
			// If this is the last one and does not have subitems, we need to close
			// the tree as long as the upper levels have last items too
			if ($treeItem['isLast'] && !$treeItem['hasSub'] && !$doCollapse && !($doExpand && $isExpandedFolderIdentifier)) {
				for ($i = $treeItem['invertedDepth']; $closeDepth[$i] == 1; $i++) {
					$closeDepth[$i] = 0;
					$itemHTML .= '</ul></li>
';
				}
			}
			// Ajax request: collapse
			if ($doCollapse && $isExpandedFolderIdentifier) {
				$this->ajaxStatus = TRUE;
				return $itemHTML;
			}
			// Ajax request: expand
			if ($doExpand && $isExpandedFolderIdentifier) {
				$ajaxOutput .= $itemHTML;
				$invertedDepthOfAjaxRequestedItem = $treeItem['invertedDepth'];
			} elseif ($invertedDepthOfAjaxRequestedItem) {
				if ($treeItem['invertedDepth'] < $invertedDepthOfAjaxRequestedItem) {
					$ajaxOutput .= $itemHTML;
				} else {
					$this->ajaxStatus = TRUE;
					return $ajaxOutput;
				}
			}
			$out .= $itemHTML;
		}
		// If this is a AJAX request, output directly
		if ($ajaxOutput) {
			$this->ajaxStatus = TRUE;
			return $ajaxOutput;
		}
		// Finally close the first ul
		$out .= '</ul>
';
		return $out;
	}

	/**
	 * Counts the number of directories in a file path.
	 *
	 * @param string $file File path.
	 * @return integer
	 * @deprecated since TYPO3 6.0, as the folder objects do the counting automatically
	 */
	public function getCount($file) {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		// This generates the directory tree
		$dirs = \TYPO3\CMS\Core\Utility\GeneralUtility::get_dirs($file);
		$c = 0;
		if (is_array($dirs)) {
			$c = count($dirs);
		}
		return $c;
	}

	/**
	 * Counts the number of directories in a file path.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject File path.
	 * @return integer
	 */
	public function getNumberOfSubfolders(\TYPO3\CMS\Core\Resource\Folder $folderObject) {
		$subFolders = $folderObject->getSubfolders();
		return count($subFolders);
	}

	/**
	 * Get stored tree structure AND updating it if needed according to incoming PM GET var.
	 *
	 * @return 	void
	 * @access private
	 * @todo Define visibility
	 */
	public function initializePositionSaving() {
		// Get stored tree structure:
		$this->stored = unserialize($this->BE_USER->uc['browseTrees'][$this->treeName]);
		$this->getShortHashNumberForStorage();
		// PM action:
		// (If an plus/minus icon has been clicked,
		// the PM GET var is sent and we must update the stored positions in the tree):
		// 0: mount key, 1: set/clear boolean, 2: item ID (cannot contain "_"), 3: treeName
		list($storageHashNumber, $doExpand, $numericFolderHash, $treeName) = $this->evaluateExpandCollapseParameter();
		if ($treeName && $treeName == $this->treeName) {
			if (in_array($storageHashNumber, $this->storageHashNumbers)) {
				if ($doExpand == 1) {
					// Set
					$this->stored[$storageHashNumber][$numericFolderHash] = 1;
				} else {
					// Clear
					unset($this->stored[$storageHashNumber][$numericFolderHash]);
				}
				$this->savePosition();
			}
		}
	}

	/**
	 * Helper method to map md5-hash to shorter number
	 *
	 * @param \TYPO3\CMS\Core\Resource\ResourceStorage $storageObject
	 * @param \TYPO3\CMS\Core\Resource\Folder $startingPointFolder
	 * @return integer
	 */
	protected function getShortHashNumberForStorage(\TYPO3\CMS\Core\Resource\ResourceStorage $storageObject = NULL, \TYPO3\CMS\Core\Resource\Folder $startingPointFolder = NULL) {
		if (!$this->storageHashNumbers) {
			$this->storageHashNumbers = array();
			// Mapping md5-hash to shorter number:
			$hashMap = array();
			foreach ($this->storages as $storageUid => $storage) {
				$fileMounts = $storage->getFileMounts();
				if (count($fileMounts)) {
					foreach ($fileMounts as $fileMount) {
						$nkey = hexdec(substr(\TYPO3\CMS\Core\Utility\GeneralUtility::md5int($fileMount['folder']->getCombinedIdentifier()), 0, 4));
						$this->storageHashNumbers[$storageUid . $fileMount['folder']->getCombinedIdentifier()] = $nkey;
					}
				} else {
					$folder = $storage->getRootLevelFolder();
					$nkey = hexdec(substr(\TYPO3\CMS\Core\Utility\GeneralUtility::md5int($folder->getCombinedIdentifier()), 0, 4));
					$this->storageHashNumbers[$storageUid . $folder->getCombinedIdentifier()] = $nkey;
				}
			}
		}
		if ($storageObject) {
			if ($startingPointFolder) {
				return $this->storageHashNumbers[$storageObject->getUid() . $startingPointFolder->getCombinedIdentifier()];
			} else {
				return $this->storageHashNumbers[$storageObject->getUid()];
			}
		} else {
			return NULL;
		}
	}

	/**
	 * Gets the values from the Expand/Collapse Parameter (&PM)
	 * previously known as "PM" (plus/minus)
	 * PM action:
	 * (If an plus/minus icon has been clicked,
	 * the PM GET var is sent and we must update the stored positions in the tree):
	 * 0: mount key, 1: set/clear boolean, 2: item ID (cannot contain "_"), 3: treeName
	 *
	 * @param string $PM The "plus/minus" command
	 * @return array
	 */
	protected function evaluateExpandCollapseParameter($PM = NULL) {
		if ($PM === NULL) {
			$PM = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('PM');
			// IE takes anchor as parameter
			if (($PMpos = strpos($PM, '#')) !== FALSE) {
				$PM = substr($PM, 0, $PMpos);
			}
		}
		// Take the first three parameters
		list($mountKey, $doExpand, $folderIdentifier) = explode('_', $PM, 3);
		// In case the folder identifier contains "_", we just need to get the fourth/last parameter
		list($folderIdentifier, $treeName) = \TYPO3\CMS\Core\Utility\GeneralUtility::revExplode('_', $folderIdentifier, 2);
		return array(
			$mountKey,
			$doExpand,
			$folderIdentifier,
			$treeName
		);
	}

	/**
	 * Generates the "PM" string to sent to expand/collapse items
	 *
	 * @param string $mountKey The mount key / storage UID
	 * @param boolean $doExpand Whether to expand/collapse
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject The folder object
	 * @param string $treeName The name of the tree
	 * @return string
	 */
	protected function generateExpandCollapseParameter($mountKey = NULL, $doExpand = FALSE, \TYPO3\CMS\Core\Resource\Folder $folderObject = NULL, $treeName = NULL) {
		$parts = array(
			$mountKey !== NULL ? $mountKey : $this->bank,
			$doExpand == 1 ? 1 : 0,
			$folderObject !== NULL ? \TYPO3\CMS\Core\Utility\GeneralUtility::md5int($folderObject->getCombinedIdentifier()) : '',
			$treeName !== NULL ? $treeName : $this->treeName
		);
		return implode('_', $parts);
	}

	/**
	 * Gets the AJAX status.
	 *
	 * @return boolean
	 */
	public function getAjaxStatus() {
		return $this->ajaxStatus;
	}

}


?>