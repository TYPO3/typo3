<?php
namespace TYPO3\CMS\Workspaces\Backend\ToolbarItems;

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

use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;

/**
 * Class to render the workspace selector
 */
class WorkspaceSelectorToolbarItem implements ToolbarItemInterface
{
    /**
     * @var array
     */
    protected $availableWorkspaces;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        /** @var \TYPO3\CMS\Workspaces\Service\WorkspaceService $wsService */
        $wsService = GeneralUtility::makeInstance(WorkspaceService::class);
        $this->availableWorkspaces = $wsService->getAvailableWorkspaces();

        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $pageRenderer = $this->getPageRenderer();
        $pageRenderer->addInlineLanguageLabel('Workspaces.workspaceTitle', WorkspaceService::getWorkspaceTitle($this->getBackendUser()->workspace));
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

        return '<span title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:toolbarItems.workspace', true) . '">'
            . $this->iconFactory->getIcon('apps-toolbar-menu-workspace', Icon::SIZE_SMALL)->render('inline') . '</span>';
    }

    /**
     * Get drop down
     *
     * @return string
     */
    public function getDropDown()
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();

        $index = 0;
        $activeWorkspace = (int)$backendUser->workspace;
        $stateCheckedIcon = $this->iconFactory->getIcon('status-status-checked', Icon::SIZE_SMALL)->render();
        $stateUncheckedIcon = '<span title="' . $languageService->getLL('bookmark_inactive', true) . '">' . $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        $workspaceSections = [
            'top' => [],
            'items' => [],
        ];

        foreach ($this->availableWorkspaces as $workspaceId => $label) {
            $workspaceId = (int)$workspaceId;
            $iconState = ($workspaceId === $activeWorkspace ? $stateCheckedIcon : $stateUncheckedIcon);
            $classValue = ($workspaceId === $activeWorkspace ? ' class="selected"' : '');
            $sectionName = ($index++ === 0 ? 'top' : 'items');
            $workspaceSections[$sectionName][] = '<li' . $classValue . '>'
                . '<a href="' . htmlspecialchars(\TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl('main', ['changeWorkspace' => $workspaceId])) . '" data-workspaceid="' . $workspaceId . '" class="dropdown-list-link tx-workspaces-switchlink">'
                . $iconState . ' ' . htmlspecialchars($label)
                . '</a></li>';
        }

        if (!empty($workspaceSections['top'])) {
            // Add the "Go to workspace module" link
            // if there is at least one icon on top and if the access rights are there
            if ($backendUser->check('modules', 'web_WorkspacesWorkspaces')) {
                $workspaceSections['top'][] = '<li><a target="content" data-module="web_WorkspacesWorkspaces" class="dropdown-list-link tx-workspaces-modulelink">'
                    . $stateUncheckedIcon . ' ' . $languageService->getLL('bookmark_workspace', true)
                    . '</a></li>';
            }
        } else {
            // no items on top (= no workspace to work in)
            $workspaceSections['top'][] = '<li>' . $stateUncheckedIcon . ' ' . $languageService->getLL('bookmark_noWSfound', true) . '</li>';
        }

        $workspaceMenu = [
            '<ul class="dropdown-list">' ,
                implode(LF, $workspaceSections['top']),
                (!empty($workspaceSections['items']) ? '<li class="divider"></li>' : ''),
                implode(LF, $workspaceSections['items']),
            '</ul>'
        ];

        return implode(LF, $workspaceMenu);
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
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
