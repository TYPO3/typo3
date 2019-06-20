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
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\InitializableInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleSettingsProviderInterface;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Log Sub Module of the AdminPanel
 *
 * @internal
 */
class Log extends AbstractSubModule implements DataProviderInterface, ModuleSettingsProviderInterface, InitializableInterface
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
     * @inheritdoc
     */
    public function getDataToStore(ServerRequestInterface $request): ModuleData
    {
        $levels = [];
        for ($i = 1; $i <= LogLevel::DEBUG; $i++) {
            $levels[] = [
                'level' => $i,
                'levelName' => LogLevel::getName($i),
            ];
        }

        $log = InMemoryLogWriter::$log;

        $logArray = [];
        /** @var \TYPO3\CMS\Core\Log\LogRecord $logRecord */
        foreach ($log as $logRecord) {
            $entry = $logRecord->toArray();
            // store only necessary info
            unset($entry['data']);
            $logArray[] = $entry;
        }
        return new ModuleData(
            [
                'levels' => $levels,
                'startLevel' => (int)$this->getConfigOption('startLevel'),
                'log' => $logArray,
            ]
        );
    }

    /**
     * @inheritdoc
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
                'levelName' => LogLevel::getName($i),
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
     * @param \TYPO3\CMS\Adminpanel\ModuleApi\ModuleData $data
     * @return string
     */
    public function getContent(ModuleData $data): string
    {
        $this->logLevel = $this->getConfigOption('startLevel');
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:adminpanel/Resources/Private/Templates/Modules/Debug/Log.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);
        $sortedLog = [];
        // settings for this module
        $groupByComponent = $this->getConfigOption('groupByComponent');
        $groupByLevel = $this->getConfigOption('groupByLevel');

        foreach ($data['log'] as $logRecord) {
            if ($logRecord['level'] > $this->logLevel) {
                continue;
            }
            if ($groupByComponent && $groupByLevel) {
                $sortedLog[$logRecord['component']][LogLevel::getName($logRecord['level'])][] = $logRecord;
            } elseif ($groupByComponent) {
                $sortedLog[$logRecord['component']][] = $logRecord;
            } elseif ($groupByLevel) {
                $sortedLog[$logRecord['level']][] = $logRecord;
            } else {
                $sortedLog[] = $logRecord;
            }
        }
        $data['log'] = $sortedLog;
        $data['groupByComponent'] = $groupByComponent;
        $data['groupByLevel'] = $groupByLevel;
        $view->assignMultiple($data->getArrayCopy());

        return $view->render();
    }

    /**
     * @inheritdoc
     */
    public function initializeModule(ServerRequestInterface $request): void
    {
        $this->logLevel = $this->getConfigOption('startLevel');

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
                $logConfig[$key][LogLevel::DEBUG][InMemoryLogWriter::class] = [];
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
