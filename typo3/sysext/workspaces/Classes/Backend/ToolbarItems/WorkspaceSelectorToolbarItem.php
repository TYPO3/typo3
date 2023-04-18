<?php

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

namespace TYPO3\CMS\Workspaces\Backend\ToolbarItems;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\RequestAwareToolbarItemInterface;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Class to render the workspace selector
 *
 * @internal
 */
class WorkspaceSelectorToolbarItem implements ToolbarItemInterface, RequestAwareToolbarItemInterface
{
    private ServerRequestInterface $request;
    protected array $availableWorkspaces;

    public function __construct(
        private readonly WorkspaceService $workspaceService,
        private readonly UriBuilder $uriBuilder,
        private readonly BackendViewFactory $backendViewFactory,
        private readonly ModuleProvider $moduleProvider,
    ) {
        $this->availableWorkspaces = $workspaceService->getAvailableWorkspaces();
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    /**
     * Checks whether the user has access to this toolbar item.
     */
    public function checkAccess(): bool
    {
        return count($this->availableWorkspaces) > 1;
    }

    /**
     * Render item.
     */
    public function getItem(): string
    {
        if (empty($this->availableWorkspaces)) {
            return '';
        }
        $currentWorkspace = $this->getBackendUser()->workspace;
        $view = $this->backendViewFactory->create($this->request, ['typo3/cms-workspaces']);
        $view->assign('workspaceTitle', $currentWorkspace > 0 ? $this->workspaceService->getWorkspaceTitle($currentWorkspace) : '');
        return $view->render('ToolbarItems/ToolbarItem');
    }

    /**
     * Render drop-down.
     */
    public function getDropDown(): string
    {
        $topItem = null;
        $additionalItems = [];
        $backendUser = $this->getBackendUser();
        $view = $this->backendViewFactory->create($this->request, ['typo3/cms-workspaces']);
        $activeWorkspace = $backendUser->workspace;
        foreach ($this->availableWorkspaces as $workspaceId => $label) {
            $workspaceId = (int)$workspaceId;
            $item = [
                'isActive'    => $workspaceId === $activeWorkspace,
                'label'       => $label,
                'link'        => (string)$this->uriBuilder->buildUriFromRoute('main', ['changeWorkspace' => $workspaceId]),
                'workspaceId' => $workspaceId,
            ];
            if ($topItem === null) {
                $topItem = $item;
            } else {
                $additionalItems[] = $item;
            }
        }
        // Add the "Go to workspace module" link if there is at least one icon on top and if the access rights are there
        if ($topItem !== null && $this->moduleProvider->accessGranted('workspaces_admin', $backendUser)) {
            $view->assign('showLinkToModule', true);
        }
        $view->assign('topItem', $topItem);
        $view->assign('additionalItems', $additionalItems);
        return $view->render('ToolbarItems/DropDown');
    }

    /**
     * This toolbar needs no additional attributes.
     */
    public function getAdditionalAttributes(): array
    {
        return [];
    }

    /**
     * This item has a drop-down.
     */
    public function hasDropDown(): bool
    {
        return !empty($this->availableWorkspaces);
    }

    /**
     * Position relative to others
     */
    public function getIndex(): int
    {
        return 40;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
