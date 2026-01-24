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

namespace TYPO3\CMS\Fluid\Service;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * @internal
 */
readonly class TemplateFinder
{
    public function __construct(private PackageManager $packageManager) {}

    /**
     * Finds all template files in active packages that use the *.fluid.*
     * file extension
     *
     * @return string[]
     */
    public function findTemplatesInAllPackages(): array
    {
        $finder = new Finder();
        $templates = $finder
            ->files()
            ->in($this->getPackagePaths())
            ->exclude([
                'Classes',
                'Tests',
                'node_modules',
                'vendor',
            ])
            ->name('*.fluid.*');
        return array_map(
            fn(SplFileInfo $file): string => $file->getPathname(),
            iterator_to_array($templates),
        );
    }

    /**
     * @return string[]
     */
    private function getPackagePaths(): array
    {
        return array_map(
            fn(PackageInterface $package): string => $package->getPackagePath(),
            $this->packageManager->getActivePackages(),
        );
    }
}
