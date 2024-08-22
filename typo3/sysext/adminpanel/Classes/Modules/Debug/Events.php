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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\VarDumper\Cloner\Data;
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\AbstractDumper;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Adminpanel\Utility\HtmlDumper;
use TYPO3\CMS\Core\Core\RequestId;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * Shows all dispatched Events of the current request
 */
#[Autoconfigure(public: true)]
class Events extends AbstractSubModule implements DataProviderInterface
{
    /**
     * @todo: See comment in MainController why DI in adminpanel modules that
     *        implement DataProviderInterface is a *bad* idea.
     */
    public function __construct(private readonly RequestId $requestId) {}

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
        /** @var \TYPO3\CMS\Adminpanel\Service\EventDispatcher $eventDispatcher */
        $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $cloner = new VarCloner();
        $cloner->setMinDepth(2);
        $cloner->setMaxItems(10);
        return new ModuleData(
            [
                'events' => $cloner->cloneVar($eventDispatcher->getDispatchedEvents()),
            ]
        );
    }

    public function getContent(ModuleData $data): string
    {
        $viewFactoryData = new ViewFactoryData(
            templateRootPaths: ['EXT:adminpanel/Resources/Private/Templates'],
            partialRootPaths: ['EXT:adminpanel/Resources/Private/Partials'],
            layoutRootPaths: ['EXT:adminpanel/Resources/Private/Layouts'],
        );
        $viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
        $view = $viewFactory->create($viewFactoryData);
        $values = $data->getArrayCopy();
        $events = $values['events'] ?? null;

        $dumper = new HtmlDumper(null, null, AbstractDumper::DUMP_LIGHT_ARRAY);
        $dumper->setNonce($this->requestId->nonce);
        $dumper->setTheme('light');

        $view->assign('events', $events instanceof Data ? $dumper->dump($events, true) : null);
        $view->assign('languageKey', $this->getBackendUser()->user['lang'] ?? null);

        return $view->render('Modules/Debug/Events');
    }
}
