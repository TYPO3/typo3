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

namespace TYPO3\CMS\Backend\UserFunctions;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\DateFormatter;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\OfficialLanguages;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[Autoconfigure(public: true)]
final readonly class UserSettingsItemsProcFunc
{
    public function __construct(
        private ModuleProvider $moduleProvider,
        private Locales $locales,
        private OfficialLanguages $officialLanguages,
        private LanguageServiceFactory $languageServiceFactory
    ) {}

    public function addLanguageItems(array &$params): void
    {
        $items = $this->locales->getLanguages();
        $languageService = $this->getLanguageService();
        // get all labels in default language as well
        $defaultLanguageLabelService = $this->languageServiceFactory->create('en');
        foreach ($items as $languageCode => $name) {
            if (!$this->locales->isLanguageKeyAvailable($languageCode)) {
                continue;
            }
            // TYPO3 + Ecosystem wrongly uses "ch" as Chinese, but it should be Chamorro (see #106125)
            // Ideally, we should remove "ch" from the system, marked as chinese, and then "go for it".
            // Chinese Simplified is "zh-CN"
            if ($languageCode === 'ch') {
                $labelIdentifier = 'LLL:EXT:backend/Resources/Private/Language/user_profile.xlf:warning.chinese_simplified';
            } else {
                $labelIdentifier = $this->officialLanguages->getLabelIdentifier($languageCode);
            }
            $localizedName = $languageService->sL($labelIdentifier) ?: $name;
            $defaultName = $defaultLanguageLabelService->sL($labelIdentifier);
            if ($defaultName === $localizedName || $defaultName === '') {
                $defaultName = $languageCode;
            }
            if ($defaultName !== $languageCode) {
                $defaultName .= ' - ' . $languageCode;
            }
            $params['items'][] = [
                'label' => $localizedName . ' [' . $defaultName . ']',
                'value' => $languageCode,
            ];
        }
    }

    public function renderStartModuleSelect(array &$params): void
    {
        // Load available backend modules
        $params['items'][] = ['label' => 'LLL:EXT:backend/Resources/Private/Language/user_profile.xlf:start_module.first_in_menu', 'value' => ''];
        foreach ($this->moduleProvider->getModules($this->getBackendUser(), false) as $identifier => $module) {
            if ($module->hasSubModules() || $module->isStandalone()) {
                $subItems = [];
                if ($module->hasSubModules()) {
                    foreach ($module->getSubModules() as $subModuleIdentifier => $subModule) {
                        $subItems[] = [
                            'label' => $this->getLanguageService()->sL($subModule->getTitle()),
                            'value' => $subModuleIdentifier,
                        ];
                    }
                } elseif ($module->isStandalone()) {
                    $subItems[] = [
                        'label' => $this->getLanguageService()->sL($module->getTitle()),
                        'value' => $identifier,
                    ];
                }
                if ($subItems !== []) {
                    // For selects with optgroups, we use a special label format or flat list if optgroups aren't directly supported in items array this way.
                    // Actually, TYPO3 select items can handle optgroups if provided in a certain way, or just a flat list.
                    // Traditional itemsProcFunc just adds to $params['items'].
                    foreach ($subItems as $item) {
                        $params['items'][] = [
                            'label' => $this->getLanguageService()->sL($module->getTitle()) . ': ' . $item['label'],
                            'value' => $item['value'],
                        ];
                    }
                }
            }
        }
    }

    public function renderDateTimeFirstDayOfWeekSelect(array &$params): void
    {
        $params['items'][] = ['label' => 'LLL:EXT:backend/Resources/Private/Language/user_profile.xlf:datetime_first_day_of_week.inherit', 'value' => ''];
        $locale = $this->getLanguageService()->getLocale();

        // There's no "cool" way to retrieve all weekday names like JavaScript Intl.DateTimeFormat->formatToParts() in PHP,
        // without requiring external dependencies to userland packages.
        // So we have to iterate that and create a timestamp for each day.
        // We use an index starting with "1" to prevent "0" being cast to the default value ''.
        for ($i = 1; $i <= 7; $i++) {
            $timestamp = new \DateTime('Sunday +' . ($i - 1) . ' days');
            $dateFormatter = GeneralUtility::makeInstance(DateFormatter::class);
            $dayName = $dateFormatter->strftime('%A', $timestamp, $locale);
            $params['items'][] = ['label' => $dayName, 'value' => (string)$i];
        }
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
