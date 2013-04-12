<?php
namespace TYPO3\CMS\Filelist;

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
 * Class for drag and drop and ajax functionality
 *
 * @author Sebastian Kurfürst <sebastian@garbage-group.de>
 * @author Benjamin Mack <bmack@xnos.org>
 * @see class \TYPO3\CMS\Backend\Tree\View\BrowseTreeView
 */
class FileListFolderTree extends \TYPO3\CMS\Backend\Tree\View\FolderTreeView {

	/**
	 * @todo Define visibility
	 */
	public $ext_IconMode;

	/**
	 * Wrapping icon in browse tree
	 *
	 * @param string $theFolderIcon Icon IMG code
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject the folder object
	 * @return string folder icon
	 * @todo Define visibility
	 */
	public function wrapIcon($theFolderIcon, \TYPO3\CMS\Core\Resource\Folder $folderObject) {
		$theFolderIcon = parent::wrapIcon($theFolderIcon, $folderObject);
		// Wrap icon in a drag/drop span.
		return '<span class="dragIcon" id="dragIconID_' . $this->getJumpToParam($folderObject) . '">' . $theFolderIcon . '</span>';
	}

	/**
	 * Wrapping $title in a-tags.
	 *
	 * @param string $title Title string
	 * @param \TYPO3\CMS\Core\Resource\Folder $folderObject Folder to work on
	 * @param integer $bank Bank pointer (which mount point number)
	 * @return string
	 * @access private
	 * @todo Define visibility
	 */
	public function wrapTitle($title, \TYPO3\CMS\Core\Resource\Folder $folderObject, $bank = 0) {
		$theFolderTitle = parent::wrapTitle($title, $folderObject, $bank);
		// Wrap title in a drag/drop span.
		return '<span class="dragTitle" id="dragTitleID_' . $this->getJumpToParam($folderObject) . '">' . $theFolderTitle . '</span>';
	}

}

?>