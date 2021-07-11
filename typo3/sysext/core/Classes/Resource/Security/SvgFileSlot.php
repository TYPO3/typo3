<?php

declare(strict_types = 1);

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

namespace TYPO3\CMS\Core\Resource\Security;

use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SvgFileSlot
{
    /**
     * @var SvgSanitizer
     */
    protected $sanitizer;

    /**
     * @var SvgTypeCheck
     */
    protected $typeCheck;

    public function __construct(SvgSanitizer $sanitizer = null, SvgTypeCheck $typeCheck = null)
    {
        $this->sanitizer = $sanitizer ?? GeneralUtility::makeInstance(SvgSanitizer::class);
        $this->typeCheck = $typeCheck ?? GeneralUtility::makeInstance(SvgTypeCheck::class);
    }

    public function preFileAdd(string $targetFileName, FolderInterface $targetFolder, string $sourceFilePath): void
    {
        if ($this->typeCheck->forFilePath($sourceFilePath)) {
            $this->sanitizer->sanitizeFile($sourceFilePath);
        }
    }

    public function preFileReplace(FileInterface $file, string $filePath): void
    {
        if ($this->typeCheck->forFilePath($filePath)) {
            $this->sanitizer->sanitizeFile($filePath);
        }
    }

    public function postFileSetContents(FileInterface $file, $content): void
    {
        if (!$this->typeCheck->forResource($file)) {
            return;
        }
        $content = (string)$content;
        $sanitizedContent = $this->sanitizer->sanitizeContent($content);
        // cave: setting content will trigger calling this handler again
        // (having custom-flags on `FileInterface` would allow to mark it as "processed")
        if ($sanitizedContent !== $content) {
            $file->setContents($sanitizedContent);
        }
    }
}
