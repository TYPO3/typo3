<?php
namespace TYPO3\CMS\Backend\Backend\ToolbarItems;

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

use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\Domain\Model\Module\BackendModule;
use TYPO3\CMS\Backend\Domain\Repository\Module\BackendModuleRepository;
use TYPO3\CMS\Backend\Toolbar\ToolbarItemInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * User toolbar item
 */
class UserToolbarItem implements ToolbarItemInterface
{
    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Item is always enabled
     *
     * @return bool TRUE
     */
    public function checkAccess()
    {
        return true;
    }

    /**
     * Render username
     *
     * @return string HTML
     */
    public function getItem()
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();

        /** @var Avatar $avatar */
        $avatar =  GeneralUtility::makeInstance(Avatar::class);
        $icon = $avatar->render();

        $realName = $backendUser->user['realName'];
        $username = $backendUser->user['username'];
        $label = $realName ?: $username;
        $title = $username;

        // Switch user mode
        if ($backendUser->user['ses_backuserid']) {
            $title = $languageService->getLL('switchtouser') . ': ' . $username;
            $label = $languageService->getLL('switchtousershort') . ' ' . ($realName ? $realName . ' (' . $username . ')' : $username);
        }

        $html = [];
        $html[] = $icon;
        $html[] = '<span title="' . htmlspecialchars($title) . '">';
        $html[] = htmlspecialchars($label);
        $html[] = '<span class="caret"></span></span>';

        return implode(LF, $html);
    }

    /**
     * Render drop down
     *
     * @return string HTML
     */
    public function getDropDown()
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();

        $dropdown = [];
        $dropdown[] = '<ul class="dropdown-list">';

        /** @var BackendModuleRepository $backendModuleRepository */
        $backendModuleRepository = GeneralUtility::makeInstance(BackendModuleRepository::class);
        /** @var \TYPO3\CMS\Backend\Domain\Model\Module\BackendModule $userModuleMenu */
        $userModuleMenu = $backendModuleRepository->findByModuleName('user');
        if ($userModuleMenu != false && $userModuleMenu->getChildren()->count() > 0) {
            foreach ($userModuleMenu->getChildren() as $module) {
                /** @var BackendModule $module */
                $dropdown[] ='<li'
                    . ' id="' . htmlspecialchars($module->getName()) . '"'
                    . ' class="typo3-module-menu-item submodule mod-' . htmlspecialchars($module->getName()) . '" '
                    . ' data-modulename="' . htmlspecialchars($module->getName()) . '"'
                    . ' data-navigationcomponentid="' . htmlspecialchars($module->getNavigationComponentId()) . '"'
                    . ' data-navigationframescript="' . htmlspecialchars($module->getNavigationFrameScript()) . '"'
                    . ' data-navigationframescriptparameters="' . htmlspecialchars($module->getNavigationFrameScriptParameters()) . '"'
                    . '>';
                $dropdown[] = '<a title="' . htmlspecialchars($module->getDescription()) . '" href="' . htmlspecialchars($module->getLink()) . '" class="dropdown-list-link modlink">';
                $dropdown[] = '<span class="submodule-icon typo3-app-icon"><span><span>' . $module->getIcon() . '</span></span></span>';
                $dropdown[] = '<span class="submodule-label">' . htmlspecialchars($module->getTitle()) . '</span>';
                $dropdown[] = '</a>';
                $dropdown[] = '</li>';
            }
            $dropdown[] = '<li class="divider"></li>';
        }

        // Logout button
        $buttonLabel = 'LLL:EXT:lang/locallang_core.xlf:' . ($backendUser->user['ses_backuserid'] ? 'buttons.exit' : 'buttons.logout');
        $dropdown[] = '<li class="reset-dropdown">';
        $dropdown[] = '<a href="' . htmlspecialchars(BackendUtility::getModuleUrl('logout')) . '" class="btn btn-danger pull-right" target="_top">';
        $dropdown[] = $this->iconFactory->getIcon('actions-logout', Icon::SIZE_SMALL)->render('inline') . ' ';
        $dropdown[] = $languageService->sL($buttonLabel, true);
        $dropdown[] = '</a>';
        $dropdown[] = '</li>';

        $dropdown[] = '</ul>';

        return implode(LF, $dropdown);
    }

    /**
     * Returns an additional class if user is in "switch user" mode
     *
     * @return array
     */
    public function getAdditionalAttributes()
    {
        $result = [];
        if ($this->getBackendUser()->user['ses_backuserid']) {
            $result['class'] = 'su-user';
        }
        return $result;
    }

    /**
     * This item has a drop down
     *
     * @return bool
     */
    public function hasDropDown()
    {
        return true;
    }

    /**
     * Position relative to others
     *
     * @return int
     */
    public function getIndex()
    {
        return 80;
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
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
