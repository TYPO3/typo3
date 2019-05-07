<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Imaging;

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

use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Type\File\FileInfo;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Value object for file to be used for ImageMagick/GraphicsMagick invocation when
 * being used as input file (implies and requires that file exists for some evaluations).
 */
class ImageMagickFile
{
    /**
     * Path to input file to be processed
     *
     * @var string
     */
    protected $filePath;

    /**
     * Frame to be used (of multi-page document, e.g. PDF)
     *
     * @var int|null
     */
    protected $frame;

    /**
     * Whether file actually exists
     *
     * @var bool
     */
    protected $fileExists;

    /**
     * File extension as given in $filePath (e.g. 'file.png' -> 'png')
     *
     * @var string
     */
    protected $fileExtension;

    /**
     * Resolved mime-type of file
     *
     * @var string
     */
    protected $mimeType;

    /**
     * Resolved extension for mime-type (e.g. 'image/png' -> 'png')
     * (might be empty if not defined in magic.mime database)
     *
     * @var string[]
     * @see FileInfo::getMimeExtensions()
     */
    protected $mimeExtensions = [];

    /**
     * Result to be used for ImageMagick/GraphicsMagick invocation containing
     * combination of resolved format prefix, $filePath and frame escaped to be
     * used as CLI argument (e.g. "'png:file.png'")
     *
     * @var string
     */
    protected $asArgument;

    /**
     * File extensions that directly can be used (and are considered to be safe).
     *
     * @var string[]
     */
    protected $allowedExtensions = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'tif', 'tiff', 'bmp', 'pcx', 'tga', 'ico'];

    /**
     * File extensions that never shall be used.
     *
     * @var string[]
     */
    protected $deniedExtensions = ['epi', 'eps', 'eps2', 'eps3', 'epsf', 'epsi', 'ept', 'ept2', 'ept3', 'msl', 'ps', 'ps2', 'ps3'];

    /**
     * File mime-types that have to be matching. Adding custom mime-types is possible using
     * $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType']
     *
     * @var string[]
     * @see FileInfo::getMimeExtensions()
     */
    protected $mimeTypeExtensionMap = [
        'image/png' => 'png',
        'image/jpeg' => 'jpg',
        'image/gif' => 'gif',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
        'image/webp' => 'webp',
        'image/svg' => 'svg',
        'image/svg+xml' => 'svg',
        'image/tiff' => 'tif',
        'application/pdf' => 'pdf',
    ];

    /**
     * @param string $filePath
     * @param int|null $frame
     * @return ImageMagickFile
     */
    public static function fromFilePath(string $filePath, int $frame = null): self
    {
        return GeneralUtility::makeInstance(
            static::class,
            $filePath,
            $frame
        );
    }

    /**
     * @param string $filePath
     * @param int|null $frame
     * @throws Exception
     */
    public function __construct(string $filePath, int $frame = null)
    {
        $this->frame = $frame;
        $this->fileExists = file_exists($filePath);
        $this->filePath = $filePath;
        $this->fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

        if ($this->fileExists) {
            $fileInfo = $this->getFileInfo($filePath);
            $this->mimeType = $fileInfo->getMimeType();
            $this->mimeExtensions = $fileInfo->getMimeExtensions();
        }

        $this->asArgument = $this->escape(
            $this->resolvePrefix() . $this->filePath
            . ($this->frame !== null ? '[' . $this->frame . ']' : '')
        );
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->asArgument;
    }

    /**
     * Resolves according ImageMagic/GraphicsMagic format (e.g. 'png:', 'jpg:', ...).
     * + in case mime-type could be resolved and is configured, it takes precedence
     * + otherwise resolved mime-type extension of mime.magick database is used if available
     *   (includes custom settings with $GLOBALS['TYPO3_CONF_VARS']['SYS']['FileInfo']['fileExtensionToMimeType'])
     * + otherwise "safe" and allowed file extension is used (jpg, png, gif, webp, tif, ...)
     * + potentially malicious script formats (eps, ps, ...) are not allowed
     *
     * @return string
     * @throws Exception
     */
    protected function resolvePrefix(): string
    {
        $prefixExtension = null;
        $fileExtension = strtolower($this->fileExtension);
        if ($this->mimeType !== null && !empty($this->mimeTypeExtensionMap[$this->mimeType])) {
            $prefixExtension = $this->mimeTypeExtensionMap[$this->mimeType];
        } elseif (!empty($this->mimeExtensions) && strpos((string)$this->mimeType, 'image/') === 0) {
            $prefixExtension = $this->mimeExtensions[0];
        } elseif ($this->isInAllowedExtensions($fileExtension)) {
            $prefixExtension = $fileExtension;
        }
        if ($prefixExtension !== null && !in_array(strtolower($prefixExtension), $this->deniedExtensions, true)) {
            return $prefixExtension . ':';
        }
        throw new Exception(
            sprintf(
                'Unsupported file %s (%s)',
                basename($this->filePath),
                $this->mimeType ?? 'unknown'
            ),
            1550060977
        );
    }

    /**
     * @param string $value
     * @return string
     */
    protected function escape(string $value): string
    {
        return CommandUtility::escapeShellArgument($value);
    }

    /**
     * @param string $extension
     * @return bool
     */
    protected function isInAllowedExtensions(string $extension): bool
    {
        return in_array($extension, $this->allowedExtensions, true);
    }

    /**
     * @param string $filePath
     * @return FileInfo
     */
    protected function getFileInfo(string $filePath): FileInfo
    {
        return GeneralUtility::makeInstance(FileInfo::class, $filePath);
    }
}
