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

namespace TYPO3\CMS\Backend\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Package\PackageManager;

/**
 * Module 'about' shows some standard information for TYPO3 CMS:
 * About-text, version number, available modules and so on.
 *
 * @internal This is a specific Backend Controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class AboutController
{
    public function __construct(
        protected readonly Typo3Version $version,
        protected readonly Typo3Information $typo3Information,
        protected readonly ModuleProvider $moduleProvider,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly PackageManager $packageManager,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {
    }

    /**
     * Main action: Show standard information
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $event = new Event\ModifyGenericBackendMessagesEvent();
        $event = $this->eventDispatcher->dispatch($event);
        $view = $this->moduleTemplateFactory->create($request);
        $view->assignMultiple([
            'typo3Info' => $this->typo3Information,
            'typo3Version' => $this->version,
            'donationUrl' => $this->typo3Information::URL_DONATE,
            'loadedExtensions' => $this->getLoadedExtensions(),
            'messages' => $event->getMessages(),
            'modules' => $this->moduleProvider->getModules($this->getBackendUser()),
        ]);
        return $view->renderResponse('About/Index');
    }

    /**
     * Fetches a list of all active (loaded) extensions in the current system
     */
    protected function getLoadedExtensions(): array
    {
        $extensions = [];
        foreach ($this->packageManager->getActivePackages() as $package) {
            // Skip system extensions
            if ($package->getPackageMetaData()->isFrameworkType()) {
                continue;
            }
            $extensions[] = [
                'key' => $package->getPackageKey(),
                'title' => $package->getPackageMetaData()->getDescription(),
                'authors' => $package->getValueFromComposerManifest('authors'),
            ];
        }
        return $extensions;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
