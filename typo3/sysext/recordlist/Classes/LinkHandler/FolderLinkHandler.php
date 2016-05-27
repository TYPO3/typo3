<?php
namespace TYPO3\CMS\Recordlist\LinkHandler;

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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\InaccessibleFolder;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Link handler for folder links
 */
class FolderLinkHandler extends FileLinkHandler
{
    /**
     * @var string
     */
    protected $mode = 'folder';

    /**
     * @var string
     */
    protected $expectedClass = Folder::class;

    /**
     * @param Folder $folder
     * @param string $extensionList
     * @return FileInterface[]
     */
    protected function getFolderContent(Folder $folder, $extensionList)
    {
        return $folder->getSubfolders();
    }

    /**
     * Renders a single item displayed in the current folder
     *
     * @param ResourceInterface $fileOrFolderObject
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function renderItem(ResourceInterface $fileOrFolderObject)
    {
        if (!$fileOrFolderObject instanceof Folder) {
            throw new \InvalidArgumentException('Expected Folder object, got "' . get_class($fileOrFolderObject) . '" object.', 1443651369);
        }
        $overlay = null;
        if ($fileOrFolderObject instanceof InaccessibleFolder) {
            $overlay = array('status-overlay-locked' => array());
        }
        return [
            'icon' => $this->iconFactory->getIcon('apps-filetree-folder-default', Icon::SIZE_SMALL, $overlay)->render(),
            'identifier' => $fileOrFolderObject->getCombinedIdentifier(),
            'name' => $fileOrFolderObject->getName(),
            'title' => GeneralUtility::fixed_lgd_cs($fileOrFolderObject->getName(), (int)$this->getBackendUser()->uc['titleLen'])
        ];
    }
}
