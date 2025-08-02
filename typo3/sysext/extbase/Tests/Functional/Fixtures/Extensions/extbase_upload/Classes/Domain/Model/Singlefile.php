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

namespace TYPO3Tests\ExtbaseUpload\Domain\Model;

use TYPO3\CMS\Extbase\Attribute\FileUpload;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class Singlefile extends AbstractEntity
{
    protected ?string $title = null;

    #[FileUpload([
        'validation' => [
            'maxFiles' => 1,
        ],
        'addRandomSuffix' => false,
        'uploadFolder' => '1:/user_upload/',
    ])]
    protected ?FileReference $fileUnrestrictedSingle = null;

    #[FileUpload([
        'validation' => [
            'mimeType' => [
                'allowedMimeTypes' => ['image/jpeg'],
            ],
        ],
        'addRandomSuffix' => false,
        'uploadFolder' => '1:/user_upload/',
    ])]
    protected ?FileReference $fileImageSingle = null;

    #[FileUpload([
        'validation' => [
            'mimeType' => [
                'allowedMimeTypes' => [
                    'application/pdf',
                    'application/x-dosexec',
                    'application/x-msdos-program',
                    'application/x-dosexec',
                    'application/x-msdownload',
                    'application/x-mz-executable',
                    'application/x-wine-extension-mz',
                    'application/x-executable',
                    'application/binary',
                    'application/x-ms-application',
                    'application/dos-exe',
                    'application/x-ms-dos-executable',
                    'application/vnd.microsoft.portable-executable',
                    'application/x-winexe',

                ],
            ],
        ],
        'addRandomSuffix' => false,
        'uploadFolder' => '1:/user_upload/',
    ])]
    protected ?FileReference $fileAppSingle = null;

    #[FileUpload([
        'validation' => [
            'fileExtension' => ['allowedFileExtensions' => ['exe']],
        ],
        'addRandomSuffix' => false,
        'uploadFolder' => '1:/user_upload/',
    ])]
    protected ?FileReference $fileExtensionSingle = null;

    #[FileUpload([
        'validation' => [
            'fileExtension' => ['useStorageDefaults' => true],
        ],
        'addRandomSuffix' => false,
        'uploadFolder' => '1:/user_upload/',
    ])]
    protected ?FileReference $fileExtensionstorageSingle = null;

    #[FileUpload([
        'validation' => [
            'fileExtension' => [
                'allowedFileExtensions' => ['exe'],
                'useStorageDefaults' => true,
            ],
        ],
        'addRandomSuffix' => false,
        'uploadFolder' => '1:/user_upload/',
    ])]
    protected ?FileReference $fileExtensionstorageplusSingle = null;

    // ------------------------ MULTI ---------------------------------------
    #[FileUpload([
        'validation' => [
            'maxFiles' => 10,
        ],
        'addRandomSuffix' => false,
        'uploadFolder' => '1:/user_upload/',
    ])]
    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $fileUnrestrictedMulti;

    #[FileUpload([
        'validation' => [
            'maxFiles' => 10,
            'mimeType' => [
                'allowedMimeTypes' => ['image/jpeg'],
            ],
        ],
        'addRandomSuffix' => false,
        'uploadFolder' => '1:/user_upload/',
    ])]
    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $fileImageMulti;

    #[FileUpload([
        'validation' => [
            'maxFiles' => 10,
            'mimeType' => [
                'allowedMimeTypes' => ['application/x-dosexec', 'application/x-msdos-program'],
            ],
        ],
        'addRandomSuffix' => false,
        'uploadFolder' => '1:/user_upload/',
    ])]
    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $fileAppMulti;

    #[FileUpload([
        'validation' => [
            'maxFiles' => 10,
            'fileExtension' => ['allowedFileExtensions' => ['exe']],
        ],
        'addRandomSuffix' => false,
        'uploadFolder' => '1:/user_upload/',
    ])]
    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $fileExtensionMulti;

    #[FileUpload([
        'validation' => [
            'maxFiles' => 10,
            'fileExtension' => ['useStorageDefaults' => true],
        ],
        'addRandomSuffix' => false,
        'uploadFolder' => '1:/user_upload/',
    ])]
    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $fileExtensionstorageMulti;

    #[FileUpload([
        'validation' => [
            'maxFiles' => 10,
            'fileExtension' => [
                'allowedFileExtensions' => ['exe'],
                'useStorageDefaults' => true,
            ],
        ],
        'addRandomSuffix' => false,
        'uploadFolder' => '1:/user_upload/',
    ])]
    /**
     * @var ObjectStorage<FileReference>
     */
    protected ObjectStorage $fileExtensionstorageplusMulti;

    public function getFileUnrestrictedSingle(): ?FileReference
    {
        return $this->fileUnrestrictedSingle;
    }

    public function setFileUnrestrictedSingle(?FileReference $fileUnrestrictedSingle): void
    {
        $this->fileUnrestrictedSingle = $fileUnrestrictedSingle;
    }

    public function getFileImageSingle(): ?FileReference
    {
        return $this->fileImageSingle;
    }

    public function setFileImageSingle(?FileReference $fileImageSingle): void
    {
        $this->fileImageSingle = $fileImageSingle;
    }

    public function getFileAppSingle(): ?FileReference
    {
        return $this->fileAppSingle;
    }

    public function setFileAppSingle(?FileReference $fileAppSingle): void
    {
        $this->fileAppSingle = $fileAppSingle;
    }

    public function getFileExtensionSingle(): ?FileReference
    {
        return $this->fileExtensionSingle;
    }

    public function setFileExtensionSingle(?FileReference $fileExtensionSingle): void
    {
        $this->fileExtensionSingle = $fileExtensionSingle;
    }

    public function getFileExtensionstorageSingle(): ?FileReference
    {
        return $this->fileExtensionstorageSingle;
    }

    public function setFileExtensionstorageSingle(?FileReference $fileExtensionstorageSingle): void
    {
        $this->fileExtensionstorageSingle = $fileExtensionstorageSingle;
    }

    public function getFileExtensionstorageplusSingle(): ?FileReference
    {
        return $this->fileExtensionstorageplusSingle;
    }

    public function setFileExtensionstorageplusSingle(?FileReference $fileExtensionstorageplusSingle): void
    {
        $this->fileExtensionstorageplusSingle = $fileExtensionstorageplusSingle;
    }

    public function __construct()
    {
        $this->initializeObject();
    }

    public function initializeObject(): void
    {
        $this->fileUnrestrictedMulti = new ObjectStorage();
        $this->fileImageMulti = new ObjectStorage();
        $this->fileAppMulti = new ObjectStorage();
        $this->fileExtensionMulti = new ObjectStorage();
        $this->fileExtensionstorageMulti = new ObjectStorage();
        $this->fileExtensionstorageplusMulti = new ObjectStorage();
    }

    public function getFileUnrestrictedMulti(): ObjectStorage
    {
        return $this->fileUnrestrictedMulti;
    }

    public function setFileUnrestrictedMulti(ObjectStorage $fileUnrestrictedMulti): void
    {
        $this->fileUnrestrictedMulti = $fileUnrestrictedMulti;
    }

    public function getFileImageMulti(): ObjectStorage
    {
        return $this->fileImageMulti;
    }

    public function setFileImageMulti(ObjectStorage $fileImageMulti): void
    {
        $this->fileImageMulti = $fileImageMulti;
    }

    public function getFileAppMulti(): ObjectStorage
    {
        return $this->fileAppMulti;
    }

    public function setFileAppMulti(ObjectStorage $fileAppMulti): void
    {
        $this->fileAppMulti = $fileAppMulti;
    }

    public function getFileExtensionMulti(): ObjectStorage
    {
        return $this->fileExtensionMulti;
    }

    public function setFileExtensionMulti(ObjectStorage $fileExtensionMulti): void
    {
        $this->fileExtensionMulti = $fileExtensionMulti;
    }

    public function getFileExtensionstorageMulti(): ObjectStorage
    {
        return $this->fileExtensionstorageMulti;
    }

    public function setFileExtensionstorageMulti(ObjectStorage $fileExtensionstorageMulti): void
    {
        $this->fileExtensionstorageMulti = $fileExtensionstorageMulti;
    }

    public function getFileExtensionstorageplusMulti(): ObjectStorage
    {
        return $this->fileExtensionstorageplusMulti;
    }

    public function setFileExtensionstorageplusMulti(ObjectStorage $fileExtensionstorageplusMulti): void
    {
        $this->fileExtensionstorageplusMulti = $fileExtensionstorageplusMulti;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }
}
