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

namespace TYPO3\CMS\Redirects\Configuration;

/**
 * @internal Only to be used within EXT:redirects.
 */
final class CheckIntegrityConfiguration
{
    public bool $showInfoInReports = true;
    public int $seconds = 86400;

    public function __construct(array $extensionConfiguration)
    {
        $this->showInfoInReports = (bool)$extensionConfiguration['showCheckIntegrityInfoInReports'];
        $this->seconds = (int)$extensionConfiguration['showCheckIntegrityInfoInReportsSeconds'];
    }
}
