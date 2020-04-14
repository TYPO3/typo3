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

namespace TYPO3\CMS\Core\Routing\Aspect;

use TYPO3\CMS\Core\Utility\GeneralUtility;

trait AspectTrait
{
    protected function isSlugUniqueInSite(string $tableName, string $fieldName): bool
    {
        $configuration = $GLOBALS['TCA'][$tableName]['columns'][$fieldName]['config'] ?? null;
        return
            ($configuration['type'] ?? null) === 'slug'
            && GeneralUtility::inList($configuration['eval'] ?? '', 'uniqueInSite');
    }

    protected function hasSlugUniqueInSite(string $tableName, string ...$fieldNames): bool
    {
        foreach ($fieldNames as $fieldName) {
            if ($this->isSlugUniqueInSite($tableName, $fieldName)) {
                return true;
            }
        }
        return false;
    }
}
