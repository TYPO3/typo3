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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * Admin Panel Page Title module for showing the Page title providers
 *
 * @internal
 */
#[Autoconfigure(public: true)]
class PageTitle extends AbstractSubModule implements DataProviderInterface
{
    /**
     * Log component
     */
    protected const LOG_COMPONENT = 'TYPO3.CMS.Core.PageTitle.PageTitleProviderManager';

    public function __construct(
        private readonly ViewFactoryInterface $viewFactory,
    ) {}

    /**
     * Identifier for this Sub-module,
     * for example "preview" or "cache"
     */
    public function getIdentifier(): string
    {
        return 'debug_pagetitle';
    }

    /**
     * Sub-Module label
     */
    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_debug.xlf:submodule.pageTitle.label'
        );
    }

    public function getDataToStore(ServerRequestInterface $request, ResponseInterface $response): ModuleData
    {
        $data = [
            'cacheEnabled' => true,
        ];
        if (!$this->isCachingAllowed($request)) {
            $data = [
                'orderedProviders' => [],
                'usedProvider' => null,
                'skippedProviders' => [],
            ];
            $logRecords = GeneralUtility::makeInstance(InMemoryLogWriter::class)->getLogEntries();
            foreach ($logRecords as $logEntry) {
                if ($logEntry->getComponent() === self::LOG_COMPONENT) {
                    $logEntryData = $logEntry->getData();
                    if (isset($logEntryData['orderedTitleProviders'])) {
                        $data['orderedProviders'] = $logEntryData['orderedTitleProviders'];
                    } elseif (isset($logEntryData['providerUsed'])) {
                        $data['usedProvider'] = $logEntryData;
                    } elseif (isset($logEntry->getData()['skippedProvider'])) {
                        $data['skippedProviders'][] = $logEntryData;
                    }
                }
            }
        }
        return new ModuleData($data);
    }

    /**
     * @return string Returns content of admin panel
     */
    public function getContent(ModuleData $data): string
    {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:adminpanel/Resources/Private/Templates'],
            partialRootPaths: ['EXT:adminpanel/Resources/Private/Partials'],
            layoutRootPaths: ['EXT:adminpanel/Resources/Private/Layouts'],
        );
        $view = $this->viewFactory->create($viewFactoryData);
        $view->assignMultiple($data->getArrayCopy());
        $view->assign('languageKey', $this->getBackendUser()->user['lang'] ?? null);
        return $view->render('Modules/Debug/PageTitle');
    }

    protected function isCachingAllowed(ServerRequestInterface $request): bool
    {
        return $request->getAttribute('frontend.cache.instruction')->isCachingAllowed();
    }
}
