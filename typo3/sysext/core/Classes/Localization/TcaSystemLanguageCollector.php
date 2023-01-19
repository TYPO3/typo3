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

namespace TYPO3\CMS\Core\Localization;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides ItemProcFunc fields for special population of available TYPO3 system languages
 * @internal
 */
final class TcaSystemLanguageCollector
{
    public function __construct(
        private readonly Locales $locales
    ) {
    }

    /**
     * Populate languages and group by available languages of the Language packs
     */
    public function populateAvailableSystemLanguagesForBackend(array &$fieldInformation): void
    {
        $languageItems = $this->locales->getLanguages();
        $availableLanguages = [];
        $unavailableLanguages = [];
        foreach ($languageItems as $languageKey => $name) {
            if ($this->locales->isLanguageKeyAvailable($languageKey)) {
                $availableLanguages[] = ['label' => $name, 'value' => $languageKey, 'group' => 'installed'];
            } else {
                $unavailableLanguages[] = ['label' => $name, 'value' => $languageKey, 'group' => 'unavailable'];
            }
        }

        // Ensure ordering of the items
        $fieldInformation['items'] = array_merge($availableLanguages, $unavailableLanguages);
    }

    /**
     * Provides a list of all languages available for ALL sites.
     * In case no site configuration can be found in the system,
     * a fallback is used to add at least the default language.
     *
     * Used by be_users and be_groups for their `allowed_languages` column.
     */
    public function populateAvailableSiteLanguages(array &$fieldInformation): void
    {
        $allLanguages = [];
        foreach ($this->getAllSites() as $site) {
            foreach ($site->getAllLanguages() as $language) {
                $languageId = $language->getLanguageId();
                if (isset($allLanguages[$languageId])) {
                    // Language already provided by another site, just add the label separately
                    $allLanguages[$languageId]['label'] .= ', ' . $language->getTitle() . ' [Site: ' . $site->getIdentifier() . ']';
                    continue;
                }
                $allLanguages[$languageId] = [
                    'label' => $language->getTitle() . ' [Site: ' . $site->getIdentifier() . ']',
                    'value' => $languageId,
                    'icon' => $language->getFlagIdentifier(),
                ];
            }
        }

        if ($allLanguages !== []) {
            ksort($allLanguages);
            foreach ($allLanguages as $item) {
                $fieldInformation['items'][] = $item;
            }
            return;
        }

        // Fallback if no site configuration exists
        $recordPid = (int)($fieldInformation['row']['pid'] ?? 0);
        $languages = (new NullSite())->getAvailableLanguages($this->getBackendUser(), false, $recordPid);

        foreach ($languages as $languageId => $language) {
            $fieldInformation['items'][] = [
                'label' => $language->getTitle(),
                'value' => $languageId,
                'icon' => $language->getFlagIdentifier(),
            ];
        }
    }

    protected function getAllSites(): array
    {
        return GeneralUtility::makeInstance(SiteFinder::class)->getAllSites();
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
