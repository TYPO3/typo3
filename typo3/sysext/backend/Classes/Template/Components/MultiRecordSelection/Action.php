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

namespace TYPO3\CMS\Backend\Template\Components\MultiRecordSelection;

use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Defines a bulk action that can be performed on multiple selected records in the backend.
 * These actions appear when users select multiple records in list views or other record
 * listings, allowing operations like mass delete, mass edit, etc.
 *
 * Actions are readonly DTOs that encapsulate the configuration,
 * icon, and label for a multi-record operation.
 *
 * Example:
 *
 * ```
 * $action = new Action(
 *     name: 'delete',
 *     configuration: ['action' => 'deleteRecords'],
 *     iconIdentifier: 'actions-delete',
 *     labelKey: 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete'
 * );
 *
 * // The action provides formatted output for rendering
 * $actionName = $action->getName();           // 'delete'
 * $jsonConfig = $action->getConfiguration();  // JSON-encoded for HTML attributes
 * $label = $action->getLabel();               // Translated label
 * $icon = $action->getIcon();                 // Rendered icon HTML
 * ```
 *
 * @internal
 */
readonly class Action
{
    public function __construct(
        protected string $name,
        protected array $configuration,
        protected string $iconIdentifier,
        protected string $labelKey,
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getConfiguration(): string
    {
        return GeneralUtility::jsonEncodeForHtmlAttribute($this->configuration);
    }

    public function getLabel(): string
    {
        return $this->getLanguageService()->sL($this->labelKey);
    }

    public function getIcon(): string
    {
        return GeneralUtility::makeInstance(IconFactory::class)->getIcon($this->iconIdentifier, IconSize::SMALL)->render();
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
