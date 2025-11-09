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

namespace TYPO3\CMS\Backend\Template\Components\Buttons;

use TYPO3\CMS\Backend\Context\PageContext;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Builds the language selector dropdown for backend modules (Page, List).
 *
 * This centralizes the language selection UI logic to ensure consistent behavior
 * across all modules. It handles both single-select (radio buttons) and multi-select
 * (toggles/checkboxes) modes.
 *
 * @internal
 */
final readonly class LanguageSelectorBuilder
{
    public function __construct(
        private ComponentFactory $componentFactory,
        private IconFactory $iconFactory,
    ) {}

    public function build(
        PageContext $pageContext,
        LanguageSelectorMode $mode,
        \Closure $urlBuilder,
        bool $showToggleAll = true,
    ): ButtonInterface {
        $languageService = $this->getLanguageService();
        $languageInfo = $pageContext->languageInformation;
        $selectedLanguages = $pageContext->selectedLanguageIds;
        $availableLanguages = $languageInfo->availableLanguages;

        // Calculate selector label and icon
        $selectorIcon = null;
        $selectedExistingLanguages = array_intersect($selectedLanguages, $languageInfo->getAllExistingLanguageIds());

        if (count($selectedExistingLanguages) === 1 && reset($selectedExistingLanguages) === 0) {
            // Only default language exists/selected - show the language title and flag
            $defaultLanguage = $availableLanguages[0] ?? null;
            if ($defaultLanguage) {
                $selectorLabel = $defaultLanguage->getTitle();
                $selectorIcon = $this->iconFactory->getIcon($defaultLanguage->getFlagIdentifier());
            } else {
                $selectorLabel = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.language');
            }
        } else {
            // Multiple languages being shown
            $displayedLanguageCount = count($selectedExistingLanguages);
            // In comparison mode, default language (0) is always shown
            // If it's not in the selected list, it was auto-added, so increment the count
            if (!in_array(0, $selectedExistingLanguages, true)) {
                $displayedLanguageCount++;
            }
            $selectorLabel = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.languages') . ' (' . $displayedLanguageCount . ')';
            $selectorIcon = $this->iconFactory->getIcon('flags-multiple');
        }

        $languageDropDownButton = $this->componentFactory->createDropDownButton()
            ->setLabel($selectorLabel)
            ->setShowActiveLabelText(true)
            ->setShowLabelText(true);

        if ($selectorIcon !== null) {
            $languageDropDownButton->setIcon($selectorIcon);
        }

        $defaultLanguageItem = null;
        $existingLanguageItems = [];
        $newLanguageItems = [];

        foreach ($languageInfo->languageItems as $languageItem) {
            if (!$languageItem->isAvailable()) {
                // Skip unavailable languages (no permission to create)
                continue;
            }

            if ($languageItem->isCreatable()) {
                // Language doesn't exist yet - show "Create translation" button
                $createTranslationHelpText = sprintf(
                    $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.createTranslationFor'),
                    $languageItem->getTitle()
                );
                $item = $this->componentFactory->createDropDownItem()
                    ->setTag('typo3-backend-localization-button')
                    ->setIcon($this->iconFactory->getIcon($languageItem->getFlagIdentifier()))
                    ->setLabel($languageItem->getTitle())
                    ->setTitle($createTranslationHelpText)
                    ->setAttributes([
                        'record-type' => 'pages',
                        'record-uid' => (string)$pageContext->pageId,
                        'target-language' => (string)$languageItem->getLanguageId(),
                        'aria-label' => $createTranslationHelpText,
                    ]);
                $newLanguageItems[] = $item;
            } else {
                // Language exists - build selection item
                if ($mode === LanguageSelectorMode::MULTI_SELECT) {
                    // Multi-select mode: Use toggles/checkboxes
                    if ($languageItem->getLanguageId() === 0) {
                        // Default language is always selected and disabled in multi-select
                        // Store separately to add divider after it
                        // Add accessibility attributes to explain why it's disabled
                        $defaultLanguageHelpText = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.defaultLanguageAlwaysShown');
                        $defaultLanguageItem = $this->componentFactory->createDropDownToggle()
                            ->setActive(true)
                            ->setIcon($this->iconFactory->getIcon($languageItem->getFlagIdentifier()))
                            ->setHref('#')
                            ->setLabel($languageItem->getTitle())
                            ->setTitle($defaultLanguageHelpText)
                            ->setAttributes([
                                'disabled' => 'disabled',
                                'aria-label' => $languageItem->getTitle() . ' (' . $defaultLanguageHelpText . ')',
                            ]);
                        // Don't add to $existingLanguageItems - will be added separately with divider
                        continue;
                    }

                    $isSelected = in_array($languageItem->getLanguageId(), $selectedLanguages, true);
                    $newSelectedLanguages = $selectedLanguages;
                    if ($isSelected) {
                        // Deselect this language
                        $newSelectedLanguages = array_values(array_diff($newSelectedLanguages, [$languageItem->getLanguageId()]));
                        // Ensure default language (0) is always present
                        if (!in_array(0, $newSelectedLanguages, true)) {
                            array_unshift($newSelectedLanguages, 0);
                        }
                    } else {
                        // Select this language
                        $newSelectedLanguages = array_unique(array_merge($newSelectedLanguages, [$languageItem->getLanguageId()]));
                    }
                    $item = $this->componentFactory->createDropDownToggle()
                        ->setActive($isSelected)
                        ->setIcon($this->iconFactory->getIcon($languageItem->getFlagIdentifier()))
                        ->setHref($urlBuilder($newSelectedLanguages))
                        ->setLabel($languageItem->getTitle());
                } else {
                    // Single-select mode: Use radio buttons
                    $item = $this->componentFactory->createDropDownRadio()
                        ->setActive($languageItem->getLanguageId() === $pageContext->getPrimaryLanguageId())
                        ->setIcon($this->iconFactory->getIcon($languageItem->getFlagIdentifier()))
                        ->setHref($urlBuilder([$languageItem->getLanguageId()]))
                        ->setLabel($languageItem->getTitle());
                }
                $existingLanguageItems[] = $item;
            }
        }

        // Add default language first (if in multi-select mode)
        if ($defaultLanguageItem !== null) {
            $languageDropDownButton->addItem($defaultLanguageItem);
            // Add divider after default language to visually separate it from other languages
            if (!empty($existingLanguageItems)) {
                $languageDropDownButton->addItem($this->componentFactory->createDropDownDivider());
            }
        }

        // Add other existing languages
        foreach ($existingLanguageItems as $existingItem) {
            $languageDropDownButton->addItem($existingItem);
        }

        // Add "Toggle All" option for multi-select mode AFTER the language list
        // (so users see what they're toggling before the action)
        if ($mode === LanguageSelectorMode::MULTI_SELECT && $showToggleAll && !empty($languageInfo->existingTranslations)) {
            $languageDropDownButton->addItem($this->componentFactory->createDropDownDivider());

            $areAllSelected = empty(array_diff(array_keys($languageInfo->existingTranslations), array_diff($selectedLanguages, [0])));
            $toggleUrl = $urlBuilder($areAllSelected ? [0] : $languageInfo->getAllExistingLanguageIds());

            // Count how many languages can be toggled (excluding default)
            $toggleableCount = count($languageInfo->existingTranslations);

            if ($areAllSelected) {
                $toggleLabel = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.uncheckAll') . ' (' . $toggleableCount . ')';
                $toggleIcon = 'actions-selection-elements-none';
                $toggleTitle = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.deselectAllLanguages');
            } else {
                $toggleLabel = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.checkAll') . ' (' . $toggleableCount . ')';
                $toggleIcon = 'actions-selection-elements-all';
                $toggleTitle = $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.selectAllLanguages');
            }

            $toggleItem = $this->componentFactory->createDropDownItem()
                ->setIcon($this->iconFactory->getIcon($toggleIcon))
                ->setHref($toggleUrl)
                ->setLabel($toggleLabel)
                ->setTitle($toggleTitle)
                ->setAttributes([
                    'aria-label' => $toggleTitle,
                ]);
            $languageDropDownButton->addItem($toggleItem);
        }

        // Add separator and new languages if any with stronger visual separation
        if (!empty($newLanguageItems)) {
            $languageDropDownButton->addItem($this->componentFactory->createDropDownDivider());
            $languageDropDownButton->addItem(
                $this->componentFactory->createDropDownHeader()
                ->setLabel($languageService->sL('core.core:labels.new_page_translation'))
            );
            foreach ($newLanguageItems as $item) {
                $languageDropDownButton->addItem($item);
            }
        }

        return $languageDropDownButton;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
