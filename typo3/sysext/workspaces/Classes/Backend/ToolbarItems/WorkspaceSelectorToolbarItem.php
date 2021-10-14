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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Class to render the workspace selector
 *
 * @internal
 */
class WorkspaceSelectorToolbarItem implements ToolbarItemInterface
{
    /**
     * @var array
     */
    protected $availableWorkspaces;

    /**
     * Constructor
     */
    public function __construct()
    {
        $currentWorkspace = $this->getBackendUser()->workspace;
        $this->availableWorkspaces = GeneralUtility::makeInstance(WorkspaceService::class)
            ->getAvailableWorkspaces();

        $pageRenderer = $this->getPageRenderer();
        $pageRenderer->addInlineLanguageLabel('Workspaces.workspaceTitle', $currentWorkspace !== -99 ? WorkspaceService::getWorkspaceTitle($currentWorkspace) : '');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Workspaces/Toolbar/WorkspacesMenu');
    }

    /**
     * Checks whether the user has access to this toolbar item
     *
     * @return bool TRUE if user has access, FALSE if not
     */
    public function checkAccess()
    {
        return count($this->availableWorkspaces) > 1;
    }

    /**
     * Render item
     *
     * @return string HTML
     */
    public function getItem()
    {
        if (empty($this->availableWorkspaces)) {
            return '';
        }
        return $this->getFluidTemplateObject('ToolbarItem.html')->render();
    }

    /**
     * Get drop down
     *
     * @return string
     */
    public function getDropDown()
    {
        $topItem = null;
        $additionalItems = [];
        $backendUser = $this->getBackendUser();
        $view = $this->getFluidTemplateObject('DropDown.html');
        $activeWorkspace = (int)$backendUser->workspace;
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        foreach ($this->availableWorkspaces as $workspaceId => $label) {
            $workspaceId = (int)$workspaceId;
            $item = [
                'isActive'    => $workspaceId === $activeWorkspace,
                'label'       => $label,
                'link'        => (string)$uriBuilder->buildUriFromRoute('main', ['changeWorkspace' => $workspaceId]),
                'workspaceId' => $workspaceId,
            ];
            if ($topItem === null) {
                $topItem = $item;
            } else {
                $additionalItems[] = $item;
            }
        }

        // Add the "Go to workspace module" link
        // if there is at least one icon on top and if the access rights are there
        if ($topItem !== null && $backendUser->check('modules', 'web_WorkspacesWorkspaces')) {
            $view->assign('showLinkToModule', true);
        }
        $view->assign('topItem', $topItem);
        $view->assign('additionalItems', $additionalItems);
        return $view->render();
    }

    /**
     * This toolbar needs no additional attributes
     *
     * @return array
     */
    public function getAdditionalAttributes()
    {
        return [];
    }

    /**
     * This item has a drop down
     *
     * @return bool
     */
    public function hasDropDown()
    {
        return !empty($this->availableWorkspaces);
    }

    /**
     * Position relative to others
     *
     * @return int
     */
    public function getIndex()
    {
        return 40;
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns current PageRenderer
     *
     * @return PageRenderer
     */
    protected function getPageRenderer()
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $filename Which templateFile should be used.
     * @return StandaloneView
     */
    protected function getFluidTemplateObject(string $filename): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:workspaces/Resources/Private/Layouts']);
        $view->setPartialRootPaths([
            'EXT:backend/Resources/Private/Partials/ToolbarItems',
            'EXT:workspaces/Resources/Private/Partials/ToolbarItems',
        ]);
        $view->setTemplateRootPaths(['EXT:workspaces/Resources/Private/Templates/ToolbarItems']);

        $view->setTemplate($filename);

        $view->getRequest()->setControllerExtensionName('Workspaces');
        return $view;
    }
}
