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
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $title;

    /**
     * @var string
     */
    protected $iconIdentifier;

    /**
     * @var string
     */
    protected $description = '';

    /**
     * @var string[]
     */
    protected $defaultWidgets = [];

    /**
     * @var bool
     */
    protected $showInWizard = true;

    public function __construct(
        string $identifier,
        string $title,
        string $description,
        string $iconIdentifier = 'content-dashboard',
        array $defaultWidgets = [],
        bool $showInWizard = true
    ) {
        $this->identifier = $identifier;
        $this->title = $title;
        $this->description = $description;
        $this->iconIdentifier = $iconIdentifier ?: 'content-dashboard';
        $this->defaultWidgets = $defaultWidgets;
        $this->showInWizard = $showInWizard;
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * @return string
     */
    public function getIconIdentifier(): string
    {
        return $this->iconIdentifier;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->getLanguageService()->sL($this->title) ?: $this->title;
    }
    /**
     * @return string
     */
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

    /**
     * @return bool
     */
    public function isShowInWizard(): bool
    {
        return $this->showInWizard;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
