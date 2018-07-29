<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Modules\Debug;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\Log\InMemoryLogWriter;
use TYPO3\CMS\Adminpanel\Modules\AbstractSubModule;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Log Sub Module of the AdminPanel
 */
class Log extends AbstractSubModule
{
    protected $logLevel = LogLevel::INFO;

    /**
     * @var ConfigurationService
     */
    protected $configurationService;

    public function __construct()
    {
        $this->configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
    }

    /**
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'debug_log';
    }

    /**
     * Sub-Module label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_debug.xlf:submodule.log.label'
        );
    }

    /**
     * @return string
     */
    public function getSettings(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:adminpanel/Resources/Private/Templates/Modules/Debug/LogSettings.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);

        $levels = [];
        for ($i = 1; $i <= LogLevel::DEBUG; $i++) {
            $levels[] = [
                'level' => $i,
                'levelName' => LogLevel::getName($i)
            ];
        }
        $view->assignMultiple(
            [
                'levels' => $levels,
                'startLevel' => (int)$this->getConfigOption('startLevel'),
                'groupByComponent' => $this->getConfigOption('groupByComponent'),
                'groupByLevel' => $this->getConfigOption('groupByLevel'),
            ]
        );

        return $view->render();
    }

    /**
     * Sub-Module content as rendered HTML
     *
     * @return string
     */
    public function getContent(): string
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:adminpanel/Resources/Private/Templates/Modules/Debug/Log.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);

        // settings for this module
        $groupByComponent = $this->getConfigOption('groupByComponent');
        $groupByLevel = $this->getConfigOption('groupByLevel');

        $log = InMemoryLogWriter::$log;

        $sortedLog = [];
        /** @var \TYPO3\CMS\Core\Log\LogRecord $logRecord */
        foreach ($log as $logRecord) {
            if ($logRecord->getLevel() > $this->logLevel) {
                continue;
            }
            if ($groupByComponent && $groupByLevel) {
                $sortedLog[$logRecord->getComponent()][LogLevel::getName($logRecord->getLevel())][] = $logRecord;
            } elseif ($groupByComponent) {
                $sortedLog[$logRecord->getComponent()][] = $logRecord;
            } elseif ($groupByLevel) {
                $sortedLog[LogLevel::getName($logRecord->getLevel())][] = $logRecord;
            } else {
                $sortedLog[] = $logRecord;
            }
        }
        $view->assignMultiple(
            [
                'log' => $sortedLog,
                'groupByComponent' => $groupByComponent,
                'groupByLevel' => $groupByLevel,
            ]
        );

        return $view->render();
    }

    public function initializeModule(ServerRequestInterface $request): void
    {
        $this->logLevel = $this->getConfigOption('startLevel') ?: LogLevel::INFO;

        // debug is set in ext_localconf as we do not have any config there yet but don't want to miss
        // potentially relevant log entries
        unset($GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][LogLevel::DEBUG][InMemoryLogWriter::class]);
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['writerConfiguration'][$this->logLevel][InMemoryLogWriter::class] = [];

        // set inMemoryLogWriter recursively for all configured namespaces/areas so we don't lose log entries
        $configWithInMemoryWriter = $this->setLoggingConfigRecursive($GLOBALS['TYPO3_CONF_VARS']['LOG'] ?? []);

        // in case there are empty array parts, remove them
        $GLOBALS['TYPO3_CONF_VARS']['LOG'] = array_filter(
            $configWithInMemoryWriter
        );
    }

    protected function setLoggingConfigRecursive(array $logConfig): array
    {
        foreach ($logConfig as $key => $value) {
            if ($key === 'writerConfiguration') {
                $logConfig[$key] = $value;
                $logConfig[$key][$this->logLevel][InMemoryLogWriter::class] = [];
            } elseif (is_array($value)) {
                $logConfig[$key] = $this->setLoggingConfigRecursive($value);
            }
        }
        return $logConfig;
    }

    /**
     * @param string $option
     * @return string
     */
    protected function getConfigOption(string $option): string
    {
        return $this->configurationService->getConfigurationOption('debug_log', $option);
    }
}
