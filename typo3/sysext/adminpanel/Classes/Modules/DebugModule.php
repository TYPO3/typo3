<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Modules;

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

use TYPO3\CMS\Adminpanel\Log\InMemoryLogWriter;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractModule;
use TYPO3\CMS\Adminpanel\ModuleApi\ShortInfoProviderInterface;
use TYPO3\CMS\Core\Log\LogRecord;

/**
 * Debug Module of the AdminPanel
 */
class DebugModule extends AbstractModule implements ShortInfoProviderInterface
{

    /**
     * @inheritdoc
     */
    public function getIdentifier(): string
    {
        return 'debug';
    }

    /**
     * @inheritdoc
     */
    public function getIconIdentifier(): string
    {
        return 'actions-debug';
    }

    /**
     * @inheritdoc
     */
    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_debug.xlf:module.label'
        );
    }

    public function getShortInfo(): string
    {
        $errorsAndWarnings = array_filter(InMemoryLogWriter::$log, function (LogRecord $entry) {
            return $entry->getLevel() <= 4;
        });
        return sprintf($this->getLanguageService()->sL(
                'LLL:EXT:adminpanel/Resources/Private/Language/locallang_debug.xlf:module.shortinfo'
            ), count($errorsAndWarnings));
    }
}
