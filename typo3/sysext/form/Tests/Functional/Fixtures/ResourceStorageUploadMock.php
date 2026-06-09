<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Form\Tests\Functional\Fixtures;

use TYPO3\CMS\Core\Resource\Exception\UploadSizeException;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ResourceStorageUploadMock extends ResourceStorage
{
    protected function assureFileUploadPermissions($localFilePath, $targetFolder, $targetFileName, $uploadedFileSize)
    {
        // HEADS UP: This condition is disabled to allow mocked $_FILES
        //if (!is_uploaded_file($localFilePath)) {
        //    throw new UploadException('The upload has failed, no uploaded file found!', 1322110455);
        //}

        // Max upload size (kb) for files.
        $maxUploadFileSize = GeneralUtility::getMaxUploadFileSize() * 1024;
        if ($maxUploadFileSize > 0 && $uploadedFileSize >= $maxUploadFileSize) {
            unlink($localFilePath);
            throw new UploadSizeException('The uploaded file exceeds the size-limit of ' . $maxUploadFileSize . ' bytes', 1322110042);
        }
        $this->assureFileAddPermissions($targetFolder, $targetFileName);
    }
}
