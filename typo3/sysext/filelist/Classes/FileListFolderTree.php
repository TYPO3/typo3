<?php
namespace TYPO3\CMS\Filelist;

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
 * Class for drag and drop and ajax functionality
 * @see class \TYPO3\CMS\Backend\Tree\View\BrowseTreeView
 */
class FileListFolderTree extends \TYPO3\CMS\Backend\Tree\View\FolderTreeView
{
    /**
     * @var bool
     */
    public $ext_IconMode;

    /**
     * Wrapping icon in browse tree
     *
     * @param string $theFolderIcon Icon IMG code
     * @param \TYPO3\CMS\Core\Resource\Folder $folderObject the folder object
     * @return string folder icon
     */
    public function wrapIcon($theFolderIcon, $folderObject)
    {
        $theFolderIcon = parent::wrapIcon($theFolderIcon, $folderObject);
        // Wrap icon in a drag/drop span.
        return '<span class="list-tree-icon dragIcon" id="dragIconID_' . $this->getJumpToParam($folderObject) . '">' . $theFolderIcon . '</span> ';
    }

    /**
     * Wrapping $title in a-tags.
     *
     * @param string $title Title string
     * @param \TYPO3\CMS\Core\Resource\Folder $folderObject Folder to work on
     * @param int $bank Bank pointer (which mount point number)
     * @return string
     * @access private
     */
    public function wrapTitle($title, $folderObject, $bank = 0)
    {
        $theFolderTitle = parent::wrapTitle($title, $folderObject, $bank);
        // Wrap title in a drag/drop span.
        return '<span class="list-tree-title dragTitle" id="dragTitleID_' . $this->getJumpToParam($folderObject) . '">' . $theFolderTitle . '</span>';
    }
}
