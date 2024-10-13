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

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Adminpanel\Service\EventDispatcher;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * Shows all dispatched Events of the current request
 */
#[Autoconfigure(public: true)]
class Events extends AbstractSubModule implements DataProviderInterface
{
    public function __construct(
        private readonly ViewFactoryInterface $viewFactory,
        // We need admin panel EventDispatcher explicitly, not EventDispatcherInterface
        private readonly EventDispatcher $eventDispatcher,
    ) {}

    public function getIdentifier(): string
    {
        return 'debug_events';
    }

    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_debug.xlf:submodule.events.label'
        );
    }

    public function getDataToStore(ServerRequestInterface $request): ModuleData
    {
        return new ModuleData($this->eventDispatcher->getDispatchedEvents());
    }

    public function getContent(ModuleData $data): string
    {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:adminpanel/Resources/Private/Templates'],
            partialRootPaths: ['EXT:adminpanel/Resources/Private/Partials'],
            layoutRootPaths: ['EXT:adminpanel/Resources/Private/Layouts'],
        );
        $view = $this->viewFactory->create($viewFactoryData);
        $events = $data->getArrayCopy();
        arsort($events, SORT_NUMERIC);
        $view->assignMultiple([
            'totalEvents' => array_sum($events),
            'events' => $events,
            'languageKey' => $this->getBackendUser()->user['lang'] ?? null,
        ]);
        return $view->render('Modules/Debug/Events');
    }
}
