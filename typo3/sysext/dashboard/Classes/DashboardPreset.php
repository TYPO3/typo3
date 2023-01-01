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

namespace TYPO3\CMS\Dashboard;

use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * @internal
 */
class DashboardPreset
{
    /**
     * @param string[] $defaultWidgets
     */
    public function __construct(
        protected readonly string $identifier,
        protected readonly string $title,
        protected readonly string $description,
        protected readonly string $iconIdentifier = 'content-dashboard',
        protected readonly array $defaultWidgets = [],
        protected readonly bool $showInWizard = true
    ) {
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getIconIdentifier(): string
    {
        return $this->iconIdentifier;
    }

    public function getTitle(): string
    {
        return $this->getLanguageService()->sL($this->title) ?: $this->title;
    }
    public function getDescription(): string
    {
        return $this->getLanguageService()->sL($this->description) ?: $this->description;
    }

    /**
     * @return string[]
     */
    public function getDefaultWidgets(): array
    {
        return $this->defaultWidgets;
    }

    public function isShowInWizard(): bool
    {
        return $this->showInWizard;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
