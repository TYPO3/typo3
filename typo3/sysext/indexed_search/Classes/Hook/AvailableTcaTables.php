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

namespace TYPO3\CMS\IndexedSearch\Hook;

use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This class is a hook implementation and is not part of the TYPO3 Core API.
 */
class AvailableTcaTables
{
    /**
     * itemsProcFunc for adding all available TCA tables
     *
     * @param array $fieldDefinition
     */
    public function populateTables(array &$fieldDefinition): void
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $languageService = $this->getLanguageService();

        foreach ($GLOBALS['TCA'] as $tableName => $tableConfiguration) {
            if ($tableConfiguration['ctrl']['adminOnly'] ?? false) {
                // Hide "admin only" tables
                continue;
            }
            $label = ($tableConfiguration['ctrl']['title'] ?? '') ?: '';
            $icon = $iconFactory->mapRecordTypeToIconIdentifier($tableName, []);
            $languageService->loadSingleTableDescription($tableName);
            $helpText = (string)($GLOBALS['TCA_DESCR'][$tableName]['columns']['']['description'] ?? '');
            $fieldDefinition['items'][] = [$label, $tableName, $icon, null, $helpText];
        }
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
