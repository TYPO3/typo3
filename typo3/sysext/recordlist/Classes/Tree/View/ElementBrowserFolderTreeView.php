<?php
namespace TYPO3\CMS\Recordlist\Tree\View;

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

/**
 * Extension class for the TBE file browser
 */
class ElementBrowserFolderTreeView extends \TYPO3\CMS\Backend\Tree\View\ElementBrowserFolderTreeView {

	/**
	 * If file-drag mode is set, temp and recycler folders are filtered out.
	 *
	 * @var int
	 */
	public $ext_noTempRecyclerDirs = 0;

	/**
	 * Returns TRUE if the input "record" contains a folder which can be linked.
	 *
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject object with information about the folder element. Contains keys like title, uid, path, _title
	 *
	 * @return bool TRUE is returned if the path is NOT a recycler or temp folder AND if ->ext_noTempRecyclerDirs is not set.
	 */
	public function ext_isLinkable($folderObject) {
		if ($this->ext_noTempRecyclerDirs && (substr($folderObject->getIdentifier(), -7) == '_temp_/' || substr($folderObject->getIdentifier(), -11) == '_recycler_/')) {
			return FALSE;
		} else {
			return TRUE;
		}
	}

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param string $title Title, ready for output.
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject The folderObject 'record'
	 *
	 * @return string Wrapping title string.
	 */
	public function wrapTitle($title, $folderObject) {
		if ($this->ext_isLinkable($folderObject)) {
			$aOnClick = 'return jumpToUrl(' . \TYPO3\CMS\Core\Utility\GeneralUtility::quoteJSvalue($this->getThisScript() . 'act=' . $GLOBALS['SOBE']->browser->act . '&mode=' . $GLOBALS['SOBE']->browser->mode . '&expandFolder=' . rawurlencode($folderObject->getCombinedIdentifier())) . ');';

			return '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $title . '</a>';
		} else {
			return '<span class="typo3-dimmed">' . $title . '</span>';
		}
	}
}
