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

namespace TYPO3\CMS\Core\Page;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SingletonInterface;

class ImportMapFactory implements SingletonInterface
{
    private PackageManager $packageManager;
    private FrontendInterface $cache;
    private string $cacheIdentifier;

    public function __construct(
        PackageManager $packageManager,
        FrontendInterface $assetsCache,
        string $cacheIdentifier
    ) {
        $this->packageManager = $packageManager;
        $this->cache = $assetsCache;
        $this->cacheIdentifier = $cacheIdentifier;
    }

    public function create(bool $bustSuffix = true): ImportMap
    {
        return new ImportMap(
            $this->packageManager->getActivePackages(),
            $this->cache,
            $this->cacheIdentifier,
            $bustSuffix
        );
    }
}
