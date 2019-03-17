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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Provides ItemProcFunc fields for special population of available TYPO3 system languages
 * @internal
 */
class TcaSystemLanguageCollector
{
    private Locales $locales;

    public function __construct(Locales $locales)
    {
        $this->locales = $locales;
    }

    /**
     * Populate languages and group by available languages of the Language packs
     */
    public function populateAvailableSystemLanguagesForBackend(array &$fieldInformation): void
    {
        $languageItems = $this->locales->getLanguages();
        unset($languageItems['default']);
        asort($languageItems);
        $installedLanguages = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lang']['availableLanguages'] ?? [];
        $availableLanguages = [];
        $unavailableLanguages = [];
        foreach ($languageItems as $typo3Language => $name) {
            $available = in_array($typo3Language, $installedLanguages, true) || is_dir(Environment::getLabelsPath() . '/' . $typo3Language);
            if ($available) {
                $availableLanguages[] = [$name, $typo3Language, '', 'installed'];
            } else {
                $unavailableLanguages[] = [$name, $typo3Language, '', 'unavailable'];
            }
        }

        // Ensure ordering of the items
        $fieldInformation['items'] = array_merge($fieldInformation['items'], $availableLanguages);
        $fieldInformation['items'] = array_merge($fieldInformation['items'], $unavailableLanguages);
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
                    $allLanguages[$languageId][0] .= ', ' . $language->getTitle() . ' [Site: ' . $site->getIdentifier() . ']';
                    continue;
                }
                $allLanguages[$languageId] = [
                    0 => $language->getTitle() . ' [Site: ' . $site->getIdentifier() . ']',
                    1 => $languageId,
                    2 => $language->getFlagIdentifier(),
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
                0 => $language->getTitle(),
                1 => $languageId,
                2 => $language->getFlagIdentifier(),
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
