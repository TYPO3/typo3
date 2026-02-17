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

namespace TYPO3\CMS\Workspaces\EventListener;

use TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Loads the workspace state module in the backend.
 *
 * The workspace state module provides workspace information and switching
 * functionality to all backend components.
 *
 * @internal
 */
final readonly class AfterBackendPageRenderEventListener
{
    public function __construct(
        private PageRenderer $pageRenderer,
        private WorkspaceService $workspaceService,
    ) {}

    #[AsEventListener(event: AfterBackendPageRenderEvent::class)]
    public function __invoke(): void
    {
        if (!$this->workspaceService->hasAccessToWorkspaces()) {
            return;
        }
        $this->pageRenderer->loadJavaScriptModule('@typo3/workspaces/workspace-state.js');
    }
}
