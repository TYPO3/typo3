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

namespace TYPO3\CMS\Core\Package\Resource;

use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\SystemResource\Identifier\PackageResourceIdentifier;
use TYPO3\CMS\Core\SystemResource\Type\PublicPackageFile;

/**
 * @internal This is subject to change during v14 development. Do not use.
 */
final class ResourceCollection implements ResourceCollectionInterface
{
    public function __construct(
        private readonly PackageInterface $package,
        private readonly ?string $iconPath = null,
    ) {}

    public function isPublicPath(string $relativePath): bool
    {
        return str_starts_with($relativePath, 'Resources/Public');
    }

    public function getPackageIcon(): ?PublicPackageFile
    {
        if ($this->iconPath === null) {
            return null;
        }
        return new PublicPackageFile(
            $this->package,
            $this->iconPath,
            new PackageResourceIdentifier(
                $this->package->getPackageKey(),
                $this->iconPath,
                sprintf(
                    'PKG:%s:%s',
                    $this->package->getValueFromComposerManifest('name') ?? $this->package->getPackageKey(),
                    $this->iconPath
                )
            )
        );
    }
}
