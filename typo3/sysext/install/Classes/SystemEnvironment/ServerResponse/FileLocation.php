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

namespace TYPO3\CMS\Install\SystemEnvironment\ServerResponse;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * References local file path and corresponding HTTP base URL
 *
 * @internal should only be used from within TYPO3 Core
 */
class FileLocation
{
    protected string $filePath;

    public function __construct(string $path)
    {
        $this->filePath = Environment::getPublicPath() . $path;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }

    public function getBaseUrl(ServerRequestInterface $request): string
    {
        return $request->getAttribute('normalizedParams')->getRequestHost()
            . PathUtility::getAbsoluteWebPath($this->filePath);
    }
}
