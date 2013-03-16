<?php
namespace TYPO3\CMS\Rtehtmlarea;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasper@typo3.com)
 *  (c) 2004-2013 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Displays image selector for the RTE
 *
 * @author 	Kasper Skårhøj <kasper@typo3.com>
 * @author 	Stanislas Rolland <typo3(arobas)sjbr.ca>
 */
/**
 * Local Folder Tree
 *
 * @author 	Kasper Skårhøj <kasper@typo3.com>
 */
class ImageFolderTree extends \TBE_FolderTree {

	/**
	 * @todo Define visibility
	 */
	public $ext_IconMode = 1;

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param 	string			Title, ready for output.
	 * @param 	\TYPO3\CMS\Core\Resource\Folder	The "record
	 * @return 	string			Wrapping title string.
	 * @todo Define visibility
	 */
	public function wrapTitle($title, \TYPO3\CMS\Core\Resource\Folder $folderObject) {
		if ($this->ext_isLinkable($folderObject)) {
			$aOnClick = 'return jumpToUrl(\'' . $this->thisScript . '?editorNo=' . $GLOBALS['SOBE']->browser->editorNo . '&act=' . $GLOBALS['SOBE']->browser->act . '&mode=' . $GLOBALS['SOBE']->browser->mode . '&expandFolder=' . rawurlencode($folderObject->getCombinedIdentifier()) . '\');';
			return '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . $title . '</a>';
		} else {
			return '<span class="typo3-dimmed">' . $title . '</span>';
		}
	}

	/**
	 * Returns TRUE if the input "record" contains a folder which can be linked.
	 *
	 * @param 	\TYPO3\CMS\Core\Resource\Folder	Object with information about the folder element. Contains keys like title, uid, path, _title
	 * @return 	boolean			TRUE is returned if the path is found in the web-part of the the server and is NOT a recycler or temp folder
	 * @todo Define visibility
	 */
	public function ext_isLinkable(\TYPO3\CMS\Core\Resource\Folder $folderObject) {
		// $folderObject->getStorage()->isPublic() does not matter if the mode is 'magic'
		return $GLOBALS['SOBE']->browser->act === 'magic' || parent::ext_isLinkable($folderObject);
	}

	/**
	 * Wrap the plus/minus icon in a link
	 *
	 * @param string $icon HTML string to wrap, probably an image tag.
	 * @param string $cmd Command for 'PM' get var
	 * @param boolean $isExpand If expanded
	 * @return string Link-wrapped input string
	 * @access private
	 */
	public function PMiconATagWrap($icon, $cmd, $isExpand = TRUE) {
		if ($this->thisScript) {
			$js = htmlspecialchars('Tree.thisScript=\'' . $GLOBALS['BACK_PATH'] . 'ajax.php\';Tree.load(\'' . $cmd . '\', ' . intval($isExpand) . ', this);');
			return '<a class="pm" onclick="' . $js . '">' . $icon . '</a>';
		} else {
			return $icon;
		}
	}

}


?>