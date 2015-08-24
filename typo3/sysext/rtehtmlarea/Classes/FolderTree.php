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

use TYPO3\CMS\Backend\Tree\View\ElementBrowserFolderTreeView;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * RTE class which generates the folder tree.
 */
class FolderTree extends ElementBrowserFolderTreeView {

	/**
	 * @var BrowseLinks|SelectImage
	 */
	protected $elementBrowser;

	/**
	 * Will create and return the HTML code for a browsable tree of folders.
	 * Is based on the mounts found in the internal array ->MOUNTS (set in the constructor)
	 *
	 * @return string HTML code for the browsable tree
	 */
	public function getBrowsableTree() {
		// TYPO3\CMS\Backend\Controller\FileSystemNavigationFrameController does not set custom parameters on an Ajax expand/collapse request
		if (!$this->elementBrowser->editorNo) {
			$scopeData = (string)GeneralUtility::_GP('scopeData');
			$scopeHash = (string)GeneralUtility::_GP('scopeHash');
			if (!empty($scopeData) && GeneralUtility::hmac($scopeData) === $scopeHash) {
				$scopeData = unserialize($scopeData);
				if ($scopeData['browser']['editorNo']) {
					$this->elementBrowser->editorNo = $scopeData['browser']['editorNo'];
				}
				if ($this->elementBrowser instanceof SelectImage && $scopeData['browser']['sys_language_content']) {
					$this->elementBrowser->sys_language_content = $scopeData['browser']['sys_language_content'];
				}
				if ($this->elementBrowser instanceof BrowseLinks && $scopeData['browser']['contentTypo3Language']) {
					$this->elementBrowser->contentTypo3Language = $scopeData['browser']['contentTypo3Language'];
				}
			}
		}
		return parent::getBrowsableTree();
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param string $title Title, ready for output.
	 * @param Folder $folderObject The "record"
	 * @return string Wrapping title string.
	 */
	public function wrapTitle($title, Folder $folderObject) {
		if ($this->ext_isLinkable($folderObject)) {
			$parameters = 'act=' . $this->elementBrowser->act
				. '&mode=' . $this->elementBrowser->mode
				. '&editorNo=' . $this->elementBrowser->editorNo
				. '&expandFolder=' . $this->getJumpToParam($folderObject);
			if ($this->elementBrowser instanceof SelectImage && $this->elementBrowser->sys_language_content) {
				$parameters .= '&sys_language_content=' . $this->elementBrowser->sys_language_content;
			}
			if ($this->elementBrowser instanceof BrowseLinks && $this->elementBrowser->contentTypo3Language) {
				$parameters .= '&contentTypo3Language=' . $this->elementBrowser->contentTypo3Language;
			}
			$aOnClick = 'return jumpToUrl(' . GeneralUtility::quoteJSvalue($this->getThisScript() . $parameters) . ');';
			return '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $title . '</a>';
		}
		return '<span class="text-muted">' . $title . '</span>';
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
					'mode' => $this->elementBrowser->mode,
					'act' => $this->elementBrowser->act
				)
			);
			if ($this->elementBrowser instanceof BrowseLinks) {
				if ($this->elementBrowser->editorNo) {
					$this->scope['browser']['editorNo'] = $this->elementBrowser->editorNo;
				}
				if ($this->elementBrowser->contentTypo3Language) {
					$this->scope['browser']['contentTypo3Language'] = $this->elementBrowser->contentTypo3Language;
				}
			}
			if ($this->elementBrowser instanceof SelectImage) {
				if ($this->elementBrowser->sys_language_content) {
					$this->scope['browser']['sys_language_content'] = $this->elementBrowser->sys_language_content;
				}
			}
		}
		return parent::PMiconATagWrap($icon, $cmd, $isExpand);
	}

}
