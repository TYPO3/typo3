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

namespace TYPO3\CMS\Backend\View\Drawing;

use TYPO3\CMS\Backend\View\BackendLayout\BackendLayout;
use TYPO3\CMS\Backend\View\PageViewMode;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Drawing Configuration
 *
 * Attached to BackendLayout as storage for configuration options which
 * determine how a page layout is rendered. Contains settings for active
 * language, show-hidden, site languages etc. and returns TCA labels for
 * tt_content fields and CTypes.
 *
 * Corresponds to legacy public properties from PageLayoutView.
 *
 * @internal this is experimental and subject to change in TYPO3 v10 / v11
 */
class DrawingConfiguration
{
    protected int $selectedLanguageId = 0;

    /**
     * Corresponds to web.layout.allowInconsistentLanguageHandling TSconfig property
     */
    protected bool $allowInconsistentLanguageHandling;

    /**
     * Determines whether rendering should happen with a visually aligned
     * connection between default language and translation. When rendered
     * with this flag enabled, any translated versions are vertically
     * aligned so they are rendered in the same visual row as the original.
     */
    protected bool $defaultLanguageBinding;

    /**
     * Key => "Language ID", Value "Label of language"
     */
    protected array $languageColumns = [];

    /**
     * Whether or not to show hidden records when rendering column contents.
     */
    protected bool $showHidden = true;

    /**
     * An array list of currently active columns. Only column identifiers
     * (colPos value) which are contained in this array will be rendered in
     * the page module.
     */
    protected array $activeColumns = [1, 0, 2, 3];

    /**
     * Whether or not to allow the translate mode for translations
     */
    protected bool $allowTranslateModeForTranslations;

    /**
     * Whether or not to allow the copy mode for translations
     */
    protected bool $allowCopyModeForTranslations;

    protected bool $shouldHideRestrictedColumns;

    protected PageViewMode $pageViewMode;

    public static function create(BackendLayout $backendLayout, array $pageTsConfig, PageViewMode $pageViewMode): self
    {
        $obj = new self();
        $obj->pageViewMode = $pageViewMode;
        $obj->defaultLanguageBinding = !empty($pageTsConfig['mod.']['web_layout.']['defLangBinding']);
        $obj->allowInconsistentLanguageHandling = (bool)($pageTsConfig['mod.']['web_layout.']['allowInconsistentLanguageHandling'] ?? false);
        $obj->shouldHideRestrictedColumns = (bool)($pageTsConfig['mod.']['web_layout.']['hideRestrictedCols'] ?? false);
        $availableColumnPositionsFromBackendLayout = array_unique($backendLayout->getColumnPositionNumbers());
        $allowedColumnPositionsByTsConfig = array_unique(GeneralUtility::intExplode(',', (string)($pageTsConfig['mod.']['SHARED.']['colPos_list'] ?? ''), true));
        // If there is no tsConfig colPos_list, no restriction. Else create intersection of available and allowed.
        if (!empty($allowedColumnPositionsByTsConfig)) {
            $obj->activeColumns = array_intersect($availableColumnPositionsFromBackendLayout, $allowedColumnPositionsByTsConfig);
        } else {
            $obj->activeColumns = $availableColumnPositionsFromBackendLayout;
        }
        $obj->allowTranslateModeForTranslations = (bool)($pageTsConfig['mod.']['web_layout.']['localization.']['enableTranslate'] ?? true);
        $obj->allowCopyModeForTranslations = (bool)($pageTsConfig['mod.']['web_layout.']['localization.']['enableCopy'] ?? true);

        return $obj;
    }

    public function getSelectedLanguageId(): int
    {
        return $this->selectedLanguageId;
    }

    public function setSelectedLanguageId(int $selectedLanguageId): void
    {
        $this->selectedLanguageId = $selectedLanguageId;
    }

    public function getAllowInconsistentLanguageHandling(): bool
    {
        return $this->allowInconsistentLanguageHandling;
    }

    public function getDefaultLanguageBinding(): bool
    {
        return $this->defaultLanguageBinding;
    }

    public function isLanguageComparisonMode(): bool
    {
        return $this->pageViewMode === PageViewMode::LanguageComparisonView;
    }

    public function getLanguageColumns(): array
    {
        if (empty($this->languageColumns)) {
            return [0 => 'Default'];
        }
        return $this->languageColumns;
    }

    public function setLanguageColumns(array $languageColumns): void
    {
        $this->languageColumns = $languageColumns;
    }

    public function getShowHidden(): bool
    {
        return $this->showHidden;
    }

    public function setShowHidden(bool $showHidden): void
    {
        $this->showHidden = $showHidden;
    }

    public function getActiveColumns(): array
    {
        return $this->activeColumns;
    }

    public function translateModeForTranslationsAllowed(): bool
    {
        return $this->allowTranslateModeForTranslations;
    }

    public function copyModeForTranslationsAllowed(): bool
    {
        return $this->allowCopyModeForTranslations;
    }

    public function shouldHideRestrictedColumns(): bool
    {
        return $this->shouldHideRestrictedColumns;
    }
}
