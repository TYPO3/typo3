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

namespace TYPO3\CMS\Core\Package\Cache;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Represents a cache identifier to be used for caches that depend on
 * the list of packages bundled with TYPO3.
 * @internal
 */
class PackageDependentCacheIdentifier
{
    private string $baseIdentifier;
    private string $prefix = '';
    private string $additionalIdentifier = '';

    public function __construct(PackageManager $packageManager)
    {
        $this->baseIdentifier = (new Typo3Version())->getVersion() . Environment::getProjectPath() . ($packageManager->getCacheIdentifier() ?? '');
    }

    public function toString(): string
    {
        return $this->prefix . sha1($this->baseIdentifier . $this->additionalIdentifier);
    }

    public function withPrefix(string $prefix): self
    {
        $newIdentifier = clone $this;
        $newIdentifier->prefix = $prefix . '_';

        return $newIdentifier;
    }

    public function withAdditionalHashedIdentifier(string $additionalIdentifier): self
    {
        $newIdentifier = clone $this;
        $newIdentifier->additionalIdentifier = $additionalIdentifier;

        return $newIdentifier;
    }
}
