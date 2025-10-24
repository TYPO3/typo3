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

namespace TYPO3\CMS\Backend\Template;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ComponentFactory;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * A factory class creating backend related ModuleTemplate view objects.
 */
#[Autoconfigure(public: true, shared: false)]
final readonly class ModuleTemplateFactory
{
    public function __construct(
        private PageRenderer $pageRenderer,
        private IconFactory $iconFactory,
        private UriBuilder $uriBuilder,
        private ModuleProvider $moduleProvider,
        private FlashMessageService $flashMessageService,
        private ExtensionConfiguration $extensionConfiguration,
        private BackendViewFactory $viewFactory,
        private ComponentFactory $componentFactory,
    ) {}

    public function create(ServerRequestInterface $request): ModuleTemplate
    {
        return new ModuleTemplate(
            $this->pageRenderer,
            $this->iconFactory,
            $this->uriBuilder,
            $this->moduleProvider,
            $this->flashMessageService,
            $this->extensionConfiguration,
            $this->viewFactory->create($request),
            $this->componentFactory,
            $request,
        );
    }
}
