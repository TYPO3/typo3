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

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\DataHandlerProcessUploadHookInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SvgHookHandler implements DataHandlerProcessUploadHookInterface
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

    /**
     * Handles `GeneralUtility::upload_copy_move` hook.
     *
     * @param array $parameters
     */
    public function processMoveUploadedFile(array $parameters)
    {
        $filePath = $parameters['source'] ?? null;
        if ($filePath !== null && $this->typeCheck->forFilePath((string)$filePath)) {
            $this->sanitizer->sanitizeFile((string)$filePath);
        }
    }

    /**
     * Handles `DataHandler` `processUpload_postProcessAction` hook.
     *
     * @param string $filename
     * @param DataHandler $parentObject
     */
    public function processUpload_postProcessAction(&$filename, DataHandler $parentObject)
    {
        if (!empty($filename) && $this->typeCheck->forFilePath($filename)) {
            $this->sanitizer->sanitizeFile($filename);
        }
    }
}
