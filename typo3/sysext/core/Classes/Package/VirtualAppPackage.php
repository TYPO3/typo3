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

namespace TYPO3\CMS\Core\Package;

use TYPO3\CMS\Core\Package\Resource\ResourceCollection;
use TYPO3\CMS\Core\Package\Resource\ResourceCollectionInterface;

/**
 * This represents the app package (root package in Composer terms)
 *
 * @internal Only to be used in TYPO3\CMS\Core\Package and TYPO3\CMS\Core\SystemResource namespace
 */
final class VirtualAppPackage extends Package
{
    public const APP_PACKAGE_KEY = 'typo3/app';

    public function __construct(
        PackageManager $packageManager,
        string $packagePath,
        private readonly string $relativePublicPath,
    ) {
        parent::__construct($packageManager, self::APP_PACKAGE_KEY, $packagePath, true);
        $this->packageMetaData = new MetaData(self::APP_PACKAGE_KEY);
        $this->composerManifest = new \stdClass();
        $this->composerManifest->name = self::APP_PACKAGE_KEY;
    }

    protected function createResources(): void
    {
        $resourceDefinitionClosure = $this->getResourceDefinitions(
            __DIR__ . '/../../Configuration/DefaultAppResources.php'
        );
        $customResourceDefinitionClosure = $this->getResourceDefinitions(
            $this->getPackagePath() . 'config/system/resources.php'
        );
        $resourceDefinitions = array_merge(
            $resourceDefinitionClosure($this, $this->relativePublicPath),
            $customResourceDefinitionClosure === null ? [] : $customResourceDefinitionClosure($this, $this->relativePublicPath),
        );
        $this->resources = new ResourceCollection(
            $resourceDefinitions,
            null,
            false,
        );
    }

    public function getResources(): ResourceCollectionInterface
    {
        $resources = parent::getResources();
        if (!$resources instanceof ResourceCollection) {
            throw new \RuntimeException('Resource object must not be overridden', 1774537784);
        }
        return $resources;
    }
}
