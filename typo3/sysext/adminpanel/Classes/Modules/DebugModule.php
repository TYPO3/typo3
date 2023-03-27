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

use TYPO3\CMS\Adminpanel\Log\InMemoryLogWriter;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractModule;
use TYPO3\CMS\Adminpanel\ModuleApi\ShortInfoProviderInterface;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\LogRecord;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Debug Module of the AdminPanel
 */
class DebugModule extends AbstractModule implements ShortInfoProviderInterface
{
    public function getIdentifier(): string
    {
        return 'debug';
    }

    public function getIconIdentifier(): string
    {
        return 'actions-debug';
    }

    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_debug.xlf:module.label'
        );
    }

    public function getShortInfo(): string
    {
        $logRecords = GeneralUtility::makeInstance(InMemoryLogWriter::class)->getLogEntries();
        $errorsAndWarnings = array_filter($logRecords, static function (LogRecord $entry): bool {
            return LogLevel::normalizeLevel($entry->getLevel()) <= 4;
        });

        $queryInformation = $this->moduleData->offsetGet($this->subModules['debug_queryinformation']);

        return sprintf(
            $this->getLanguageService()->sL('LLL:EXT:adminpanel/Resources/Private/Language/locallang_debug.xlf:module.shortinfoErrorsAndSQL'),
            count($errorsAndWarnings),
            $queryInformation->offsetGet('totalQueries'),
            round($queryInformation->offsetGet('totalTime'))
        );
    }
}
