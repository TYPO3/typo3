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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;

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
    /**
     * @var int
     */
    protected $selectedLanguageId = 0;

    /**
     * Determines whether rendering should happen with a visually aligned
     * connection between default language and translation. When rendered
     * with this flag enabled, any translated versions are vertically
     * aligned so they are rendered in the same visual row as the original.
     *
     * @var bool
     */
    protected $defaultLanguageBinding = true;

    /**
     * If TRUE, indicates that the current rendering method shows multiple
     * languages (e.g. the "page" module is set in "Languages" mode.
     *
     * @var bool
     */
    protected $languageMode = false;

    /**
     * Key => "Language ID", Value "Label of language"
     *
     * @var array
     */
    protected $languageColumns = [];

    /**
     * Whether or not to show hidden records when rendering column contents.
     *
     * @var bool
     */
    protected $showHidden = true;

    /**
     * An array list of currently active columns. Only column identifiers
     * (colPos value) which are contained in this array will be rendered in
     * the page module.
     *
     * @var array
     */
    protected $activeColumns = [1, 0, 2, 3];

    /**
     * Whether or not to show the "new content" buttons that open the new content
     * wizard, when rendering columns. Disabling this will disable the rendering
     * of new content buttons both in column top and after each content element.
     *
     * @var bool
     */
    protected $showNewContentWizard = true;

    public function getSelectedLanguageId(): int
    {
        return $this->selectedLanguageId;
    }

    public function setSelectedLanguageId(int $selectedLanguageId): void
    {
        $this->selectedLanguageId = $selectedLanguageId;
    }

    public function getDefaultLanguageBinding(): bool
    {
        return $this->defaultLanguageBinding;
    }

    public function setDefaultLanguageBinding(bool $defaultLanguageBinding): void
    {
        $this->defaultLanguageBinding = $defaultLanguageBinding;
    }

    public function getLanguageMode(): bool
    {
        return $this->languageMode;
    }

    public function setLanguageMode(bool $languageMode): void
    {
        $this->languageMode = $languageMode;
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

    public function setActiveColumns(array $activeColumns): void
    {
        $this->activeColumns = $activeColumns;
    }

    public function getShowNewContentWizard(): bool
    {
        return $this->showNewContentWizard;
    }

    public function setShowNewContentWizard(bool $showNewContentWizard): void
    {
        $this->showNewContentWizard = $showNewContentWizard;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
