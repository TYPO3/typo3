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

namespace TYPO3\CMS\Core\SystemResource\Package;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Package\Package;

/**
 * This represents the project package (root package in Composer terms)
 * This likely will be moved to PackageManager directly before v14 release.
 *
 * @internal This is subject to change during v14 development. Do not use.
 */
final class VirtualAppPackage extends Package
{
    public const APP_PACKAGE_KEY = 'typo3/app';

    public function __construct()
    {
        $this->packageKey = self::APP_PACKAGE_KEY;
        $this->packagePath = Environment::getProjectPath() . '/';
    }

    public function getResources(): ProjectResourceCollection
    {
        return new ProjectResourceCollection(Environment::getRelativePublicPath());
    }
}
