<?php
namespace TYPO3\CMS\Rtehtmlarea;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * RTE class which generates the folder tree.
 */
class FolderTree extends \TYPO3\CMS\Backend\Tree\View\ElementBrowserFolderTreeView {

	/**
	 * Will create and return the HTML code for a browsable tree of folders.
	 * Is based on the mounts found in the internal array ->MOUNTS (set in the constructor)
	 *
	 * @return string HTML code for the browsable tree
	 */
	public function getBrowsableTree() {
		// TYPO3\CMS\Backend\Controller\FileSystemNavigationFrameController does not set custom parameters on an Ajax expand/collapse request
		if (!$GLOBALS['SOBE']->browser->editorNo) {
			$scopeData = (string)GeneralUtility::_GP('scopeData');
			$scopeHash = (string)GeneralUtility::_GP('scopeHash');
			if (!empty($scopeData) && GeneralUtility::hmac($scopeData) === $scopeHash) {
				$scopeData = unserialize($scopeData);
				if ($scopeData['browser']['editorNo']) {
					$GLOBALS['SOBE']->browser->editorNo = $scopeData['browser']['editorNo'];
				}
				if ($scopeData['browser']['sys_language_content']) {
					$GLOBALS['SOBE']->browser->sys_language_content = $scopeData['browser']['sys_language_content'];
				}
				if ($scopeData['browser']['contentTypo3Language']) {
					$GLOBALS['SOBE']->browser->contentTypo3Language = $scopeData['browser']['contentTypo3Language'];
				}
			}
		}
		return parent::getBrowsableTree();
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param string $title Title, ready for output.
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject The "record"
	 * @return string Wrapping title string.
	 */
	public function wrapTitle($title, \TYPO3\CMS\Core\Resource\Folder $folderObject) {
		if ($this->ext_isLinkable($folderObject)) {
			$aOnClick = 'return jumpToUrl(\''
				. $this->getThisScript()
				. 'act=' . $GLOBALS['SOBE']->browser->act
				. '&mode=' . $GLOBALS['SOBE']->browser->mode
				. '&editorNo=' . $GLOBALS['SOBE']->browser->editorNo
				. ($GLOBALS['SOBE']->browser->sys_language_content ? '&sys_language_content=' . $GLOBALS['SOBE']->browser->sys_language_content : '')
				. ($GLOBALS['SOBE']->browser->contentTypo3Language ? '&contentTypo3Language=' . $GLOBALS['SOBE']->browser->contentTypo3Language : '')
				. '&expandFolder=' . $this->getJumpToParam($folderObject)
				. '\');';
			return '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $title . '</a>';
		} else {
			return '<span class="typo3-dimmed">' . $title . '</span>';
		}
	}

	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param string $icon HTML string to wrap, probably an image tag.
	 * @param string $cmd Command for 'PM' get var
	 * @param bool $isExpand If expanded
	 * @return string Link-wrapped input string
	 * @access private
	 */
	public function PMiconATagWrap($icon, $cmd, $isExpand = TRUE) {
		if (empty($this->scope)) {
			$this->scope = array(
				'class' => get_class($this),
				'script' => $this->thisScript,
				'ext_noTempRecyclerDirs' => $this->ext_noTempRecyclerDirs,
				'browser' => array(
					'mode' => $GLOBALS['SOBE']->browser->mode,
					'act' => $GLOBALS['SOBE']->browser->act
				)
			);
			if ($GLOBALS['SOBE']->browser->editorNo) {
				$this->scope['browser']['editorNo'] = $GLOBALS['SOBE']->browser->editorNo;
			}
			if ($GLOBALS['SOBE']->browser->sys_language_content) {
				$this->scope['browser']['sys_language_content'] = $GLOBALS['SOBE']->browser->sys_language_content;
			}
			if ($GLOBALS['SOBE']->browser->contentTypo3Language) {
				$this->scope['browser']['contentTypo3Language'] = $GLOBALS['SOBE']->browser->contentTypo3Language;
			}
		}
		return parent::PMiconATagWrap($icon, $cmd, $isExpand);
	}

}
