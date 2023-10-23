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

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\SingletonInterface;

class ImportMapFactory implements SingletonInterface
{
    public function __construct(
        private readonly PackageManager $packageManager,
        private readonly FrontendInterface $assetsCache,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly string $cacheIdentifier,
    ) {}

    public function create(bool $bustSuffix = true): ImportMap
    {
        $activePackages = array_values(
            $this->packageManager->getActivePackages()
        );
        return new ImportMap(
            $activePackages,
            $this->assetsCache,
            $this->cacheIdentifier,
            $this->eventDispatcher,
            $bustSuffix
        );
    }
}
