<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Domain\Finishers;

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

use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Form\Domain\Model\FormElements\FileUpload;

/**
 * This finisher remove the submited files.
 * Use this e.g after the email finisher if you don't want
 * to keep the files online.
 *
 * Scope: frontend
 */
class DeleteUploadsFinisher extends AbstractFinisher
{

    /**
     * Executes this finisher
     * @see AbstractFinisher::execute()
     */
    protected function executeInternal()
    {
        $formRuntime = $this->finisherContext->getFormRuntime();

        $uploadFolders = [];
        $elements = $formRuntime->getFormDefinition()->getRenderablesRecursively();
        foreach ($elements as $element) {
            if (!$element instanceof FileUpload) {
                continue;
            }
            $file = $formRuntime[$element->getIdentifier()];
            if (!$file) {
                continue;
            }

            if ($file instanceof FileReference) {
                $file = $file->getOriginalResource();
            }

            $folder = $file->getParentFolder();
            $uploadFolders[$folder->getCombinedIdentifier()] = $folder;

            $file->getStorage()->deleteFile($file->getOriginalFile());
        }

        $this->deleteEmptyUploadFolders($uploadFolders);
    }

    /**
     * note:
     * TYPO3\CMS\Form\Mvc\Property\TypeConverter\UploadedFileReferenceConverter::importUploadedResource()
     * creates a sub-folder for file uploads (e.g. .../form_<40-chars-hash>/actual.file)
     * @param Folder[] $folders
     */
    protected function deleteEmptyUploadFolders(array $folders): void
    {
        foreach ($folders as $folder) {
            if ($this->isEmptyFolder($folder)) {
                $folder->delete();
            }
        }
    }

    /**
     * @param Folder $folder
     * @return bool
     */
    protected function isEmptyFolder(Folder $folder): bool
    {
        return $folder->getFileCount() === 0
            && $folder->getStorage()->countFoldersInFolder($folder) === 0;
    }
}
