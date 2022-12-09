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

namespace TYPO3\CMS\Adminpanel\Modules;

use TYPO3\CMS\Adminpanel\ModuleApi\AbstractModule;
use TYPO3\CMS\Adminpanel\ModuleApi\ShortInfoProviderInterface;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Admin Panel Info Module
 */
class InfoModule extends AbstractModule implements ShortInfoProviderInterface
{
    public function getIconIdentifier(): string
    {
        return 'actions-document-info';
    }

    public function getIdentifier(): string
    {
        return 'info';
    }

    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_info.xlf:module.label'
        );
    }

    public function getShortInfo(): string
    {
        $phpInformation = $this->moduleData->offsetGet($this->subModules['info_php']);
        $parseTime = $this->getTimeTracker()->getParseTime();
        return sprintf(
            $this->getLanguageService()->sL(
                'LLL:EXT:adminpanel/Resources/Private/Language/locallang_info.xlf:module.shortinfoLoadAndMemory'
            ),
            $parseTime,
            $phpInformation['general']['Peak Memory Usage']
        );
    }

    protected function getTimeTracker(): TimeTracker
    {
        return GeneralUtility::makeInstance(TimeTracker::class);
    }
}
