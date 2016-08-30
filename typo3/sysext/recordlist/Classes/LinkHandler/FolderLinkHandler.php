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
    protected $additionalFolderClass = 'bg-success';

    /**
     * @var string
     */
    protected $expectedClass = Folder::class;

    /**
     * @return string
     */
    protected function getTitle()
    {
        return $this->getLanguageService()->getLL('folders');
    }

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
        $fileIdentifier = $fileOrFolderObject->getCombinedIdentifier();
        $overlay = null;
        if ($fileOrFolderObject instanceof InaccessibleFolder) {
            $overlay = ['status-overlay-locked' => []];
        }
        $icon = '<span title="' . htmlspecialchars($fileOrFolderObject->getName()) . '">'
            . $this->iconFactory->getIcon('apps-filetree-folder-default', Icon::SIZE_SMALL, $overlay)->render()
            . '</span>';
        return [$fileIdentifier, $icon];
    }
}
