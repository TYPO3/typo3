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

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Definition of a multi record selection action
 */
class Action
{
    public function __construct(
        protected readonly string $name,
        protected readonly array $configuration,
        protected readonly string $iconIdentifier,
        protected readonly string $labelKey,
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
        return GeneralUtility::makeInstance(IconFactory::class)->getIcon($this->iconIdentifier, Icon::SIZE_SMALL)->render();
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
