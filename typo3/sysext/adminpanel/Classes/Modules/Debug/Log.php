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

namespace TYPO3\CMS\Adminpanel\Modules\Debug;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Adminpanel\Log\InMemoryLogWriter;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleSettingsProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\RequestEnricherInterface;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * Log Sub Module of the AdminPanel
 *
 * @internal
 */
#[Autoconfigure(public: true)]
class Log extends AbstractSubModule implements DataProviderInterface, ModuleSettingsProviderInterface, RequestEnricherInterface
{
    protected int $logLevel;

    public function __construct(
        private readonly ConfigurationService $configurationService,
        private readonly ViewFactoryInterface $viewFactory,
    ) {
        $this->logLevel = LogLevel::normalizeLevel(\Psr\Log\LogLevel::INFO);
    }

    public function getIdentifier(): string
    {
        return 'debug_log';
    }

    /**
     * Sub-Module label
     */
    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_debug.xlf:submodule.log.label'
        );
    }

    public function getDataToStore(ServerRequestInterface $request, ResponseInterface $response): ModuleData
    {
        $maxLevel = LogLevel::normalizeLevel(\Psr\Log\LogLevel::DEBUG);
        $levels = [];
        for ($i = 1; $i <= $maxLevel; $i++) {
            $levels[] = [
                'level' => $i,
                'levelName' => LogLevel::getName($i),
            ];
        }

        $logRecords = GeneralUtility::makeInstance(InMemoryLogWriter::class)->getLogEntries();

        $logArray = [];
        foreach ($logRecords as $logRecord) {
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

    public function getSettings(): string
    {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:adminpanel/Resources/Private/Templates'],
            partialRootPaths: ['EXT:adminpanel/Resources/Private/Partials'],
            layoutRootPaths: ['EXT:adminpanel/Resources/Private/Layouts'],
        );
        $view = $this->viewFactory->create($viewFactoryData);
        $maxLevel = LogLevel::normalizeLevel(\Psr\Log\LogLevel::DEBUG);
        $levels = [];
        for ($i = 1; $i <= $maxLevel; $i++) {
            $levels[] = [
                'level' => $i,
                'levelName' => LogLevel::getName($i),
            ];
        }
        $view->assignMultiple(
            [
                'languageKey' => $this->getBackendUser()->user['lang'] ?? null,
                'levels' => $levels,
                'startLevel' => (int)$this->getConfigOption('startLevel'),
                'groupByComponent' => $this->getConfigOption('groupByComponent'),
                'groupByLevel' => $this->getConfigOption('groupByLevel'),
            ]
        );
        return $view->render('Modules/Debug/LogSettings');
    }

    /**
     * Sub-Module content as rendered HTML
     */
    public function getContent(ModuleData $data): string
    {
        $this->logLevel = (int)$this->getConfigOption('startLevel');
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:adminpanel/Resources/Private/Templates'],
            partialRootPaths: ['EXT:adminpanel/Resources/Private/Partials'],
            layoutRootPaths: ['EXT:adminpanel/Resources/Private/Layouts'],
        );
        $viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
        $view = $viewFactory->create($viewFactoryData);
        $sortedLog = [];
        // settings for this module
        $groupByComponent = $this->getConfigOption('groupByComponent');
        $groupByLevel = $this->getConfigOption('groupByLevel');

        foreach ($data['log'] as $logRecord) {
            if (LogLevel::normalizeLevel($logRecord['level']) > $this->logLevel) {
                continue;
            }
            if ($groupByComponent && $groupByLevel) {
                $sortedLog[$logRecord['component']][$logRecord['level']][] = $logRecord;
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
        $view->assign('languageKey', $this->getBackendUser()->user['lang'] ?? null);

        return $view->render('Modules/Debug/Log');
    }

    public function enrich(ServerRequestInterface $request): ServerRequestInterface
    {
        $this->logLevel = (int)$this->getConfigOption('startLevel');

        // set inMemoryLogWriter recursively for all configured namespaces/areas so we don't lose log entries
        $configWithInMemoryWriter = $this->setLoggingConfigRecursive($GLOBALS['TYPO3_CONF_VARS']['LOG'] ?? []);

        // in case there are empty array parts, remove them
        $GLOBALS['TYPO3_CONF_VARS']['LOG'] = array_filter(
            $configWithInMemoryWriter
        );
        return $request;
    }

    protected function setLoggingConfigRecursive(array $logConfig): array
    {
        foreach ($logConfig as $key => $value) {
            if ($key === 'writerConfiguration') {
                $logConfig[$key] = $value;
                $logConfig[$key][\Psr\Log\LogLevel::DEBUG][InMemoryLogWriter::class] = [];
            } elseif (is_array($value)) {
                $logConfig[$key] = $this->setLoggingConfigRecursive($value);
            }
        }
        return $logConfig;
    }

    protected function getConfigOption(string $option): string
    {
        return $this->configurationService->getConfigurationOption('debug_log', $option);
    }
}
