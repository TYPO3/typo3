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

use TYPO3\CMS\Core\Core\Environment;

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
}
