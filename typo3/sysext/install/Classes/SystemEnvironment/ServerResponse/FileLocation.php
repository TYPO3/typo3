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

namespace TYPO3\CMS\Install\SystemEnvironment\ServerResponse;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * References local file path and corresponding HTTP base URL
 *
 * @internal should only be used from within TYPO3 Core
 */
class FileLocation
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var string
     */
    protected $filePath;

    /**
     * @var string
     */
    protected $baseUrl;

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->filePath = Environment::getPublicPath() . $path;
        $this->baseUrl = GeneralUtility::getIndpEnv('TYPO3_REQUEST_HOST')
            . PathUtility::getAbsoluteWebPath($this->filePath);
    }

    /**
     * @return string
     */
    public function getFilePath(): string
    {
        return $this->filePath;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
}
