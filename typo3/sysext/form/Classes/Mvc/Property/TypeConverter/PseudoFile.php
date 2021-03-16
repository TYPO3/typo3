<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Form\Mvc\Property\TypeConverter;

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

use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Mvc\Property\Exception\TypeConverterException;

/**
 * Use in `UploadedFileReferenceConverter` handling file uploads.
 * `PseudoFile` and `PseudoFileReference` are independent and not associated.
 * @internal
 */
class PseudoFile
{
    /**
     * @var \SplFileInfo
     */
    protected $nameFileInfo;

    /**
     * @var FileInfo
     */
    protected $payloadFileInfo;

    /**
     * @var string
     */
    protected $payloadFilePath;

    /**
     * see https://www.php.net/manual/en/features.file-upload.post-method.php
     *
     * @param array $uploadInfo as in $_FILES
     * @throws TypeConverterException
     */
    public function __construct(array $uploadInfo)
    {
        if (!isset($uploadInfo['tmp_name']) || !isset($uploadInfo['name'])) {
            throw new TypeConverterException(
                'Could not determine uploaded file',
                1602103603
            );
        }
        $this->nameFileInfo = new \SplFileInfo($uploadInfo['name']);
        $this->payloadFilePath = $uploadInfo['tmp_name'];
        $this->payloadFileInfo = GeneralUtility::makeInstance(FileInfo::class, $uploadInfo['tmp_name']);
    }

    public function getName(): string
    {
        return $this->nameFileInfo->getBasename();
    }

    public function getNameWithoutExtension(): string
    {
        // `image...png`
        return rtrim(
            $this->nameFileInfo->getBasename($this->getExtension()),
            '.'
        );
    }

    public function getExtension(): string
    {
        return $this->nameFileInfo->getExtension();
    }

    public function getSize(): ?int
    {
        // returns `null` in case size is empty (includes `0`)
        // @see \TYPO3\CMS\Core\Resource\AbstractFile::getSize()
        return $this->payloadFileInfo->getSize() ?: null;
    }

    public function getMimeType(): ?string
    {
        $mimeType = $this->payloadFileInfo->getMimeType();
        return is_string($mimeType) ? $mimeType : null;
    }

    public function getContents(): string
    {
        return file_get_contents($this->payloadFilePath);
    }

    public function getSha1(): string
    {
        return sha1_file($this->payloadFilePath);
    }
}
