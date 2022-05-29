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
use Symfony\Component\VarDumper\Cloner\VarCloner;
use Symfony\Component\VarDumper\Dumper\AbstractDumper;
use Symfony\Component\VarDumper\Dumper\HtmlDumper;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Adminpanel\Service\EventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Shows all dispatched Events of the current request
 */
class Events extends AbstractSubModule implements DataProviderInterface
{
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
        /** @var EventDispatcher $eventDispatcher */
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
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $templateNameAndPath = 'EXT:adminpanel/Resources/Private/Templates/Modules/Debug/Events.html';
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName($templateNameAndPath));
        $view->setPartialRootPaths(['EXT:adminpanel/Resources/Private/Partials']);
        $values = $data->getArrayCopy();
        $events = $values['events'];

        $dumper = new HtmlDumper(null, null, AbstractDumper::DUMP_LIGHT_ARRAY);
        $dumper->setTheme('light');

        $view->assign('events', $dumper->dump($events, true));
        $view->assign('languageKey', $this->getBackendUser()->user['lang']);

        return $view->render();
    }
}
