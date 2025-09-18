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

use TYPO3\CMS\Core\Package\Resource\ResourceCollectionInterface;
use TYPO3\CMS\Core\SystemResource\Type\PublicPackageFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This is subject to change during v14 development. Do not use.
 */
final class ProjectResourceCollection implements ResourceCollectionInterface
{
    public function __construct(private readonly string $publicPrefix) {}

    public function isPublicPath(string $relativePath): bool
    {
        return str_starts_with($relativePath, $this->publicPrefix);
    }

    public function isValidPath(string $path): bool
    {
        $allowedPublicFolders = [
            '_assets/',
            'uploads/',
            'typo3temp/assets/',
        ];
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths'])) {
            $allowedFolders = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['FE']['addAllowedPaths'], true);
            foreach ($allowedFolders as $folder) {
                $allowedPublicFolders[] = $folder;
            }
        }
        $pattern = implode(
            '|',
            array_map(
                static fn(string $folder) => preg_quote($folder, '#'),
                $allowedPublicFolders
            )
        );

        $matched = preg_match('#^' . $this->publicPrefix . '(' . $pattern . ')#', $path);
        return $matched !== false && $matched > 0;
    }

    public function getPackageIcon(): ?PublicPackageFile
    {
        return null;
    }
}
