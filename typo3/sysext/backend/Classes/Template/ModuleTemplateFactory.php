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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * A factory class creating backend related ModuleTemplate view objects.
 */
final class ModuleTemplateFactory
{
    public function __construct(
        protected readonly PageRenderer $pageRenderer,
        protected readonly IconFactory $iconFactory,
        protected readonly FlashMessageService $flashMessageService,
        protected readonly ExtensionConfiguration $extensionConfiguration,
        protected readonly BackendViewFactory $viewFactory,
        protected readonly ResponseFactoryInterface $responseFactory,
        protected readonly StreamFactoryInterface $streamFactory,
    ) {
    }

    public function create(ServerRequestInterface $request): ModuleTemplate
    {
        return new ModuleTemplate(
            $this->pageRenderer,
            $this->iconFactory,
            $this->flashMessageService,
            $this->extensionConfiguration,
            $this->viewFactory->create($request),
            $this->responseFactory,
            $this->streamFactory,
            $request
        );
    }
}
