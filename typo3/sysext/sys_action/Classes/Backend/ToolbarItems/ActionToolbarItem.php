<?php
namespace TYPO3\CMS\SysAction\Backend\ToolbarItems;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Adds action links to the backend's toolbar
 */
class ActionToolbarItem implements ToolbarItemInterface
{
    /**
     * @var array List of action entries
     */
    protected $actionEntries = [];

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->getLanguageService()->includeLLFile('EXT:sys_action/Resources/Private/Language/locallang.xlf');
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->initializeActionEntries();
    }

    /**
     * Render toolbar icon
     *
     * @return string HTML
     */
    public function getItem()
    {
        $title = $this->getLanguageService()->getLL('action_toolbaritem', true);
        return '<span title="' . $title . '">' . $this->iconFactory->getIcon('apps-toolbar-menu-actions', Icon::SIZE_SMALL)->render('inline') . '</span>';
    }

    /**
     * Render drop down
     *
     * @return string HTML
     */
    public function getDropDown()
    {
        $actionMenu = [];
        $actionMenu[] = '<ul class="dropdown-list">';
        foreach ($this->actionEntries as $linkConf) {
            $actionMenu[] = '<li>';
            $actionMenu[] = '<a href="' . htmlspecialchars($linkConf[1]) . '" target="content" class="dropdown-list-link">';
            $actionMenu[] = $linkConf[2] . ' ' . htmlspecialchars($linkConf[0]);
            $actionMenu[] = '</a>';
            $actionMenu[] = '</li>';
        }
        $actionMenu[] = '</ul>';
        return implode(LF, $actionMenu);
    }

    /**
     * Gets the entries for the action menu
     *
     * @return array Array of action menu entries
     */
    protected function initializeActionEntries()
    {
        $backendUser = $this->getBackendUser();
        $databaseConnection = $this->getDatabaseConnection();
        $actions = [];
        if ($backendUser->isAdmin()) {
            $queryResource = $databaseConnection->exec_SELECTquery('*', 'sys_action', 'pid = 0 AND hidden=0', '', 'sys_action.sorting');
        } else {
            $groupList = 0;
            if ($backendUser->groupList) {
                $groupList = $backendUser->groupList;
            }
            $queryResource = $databaseConnection->exec_SELECT_mm_query(
                'sys_action.*',
                'sys_action',
                'sys_action_asgr_mm',
                'be_groups',
                ' AND be_groups.uid IN (' . $groupList . ') AND sys_action.pid = 0 AND sys_action.hidden = 0',
                'sys_action.uid',
                'sys_action.sorting'
            );
        }

        if ($queryResource) {
            while ($actionRow = $databaseConnection->sql_fetch_assoc($queryResource)) {
                $actions[] = [
                    $actionRow['title'],
                    BackendUtility::getModuleUrl('user_task') . '&SET[mode]=tasks&SET[function]=sys_action.TYPO3\\CMS\\SysAction\\ActionTask&show=' . $actionRow['uid'],
                    $this->iconFactory->getIconForRecord('sys_action', $actionRow, Icon::SIZE_SMALL)->render()
                ];
            }
            $databaseConnection->sql_free_result($queryResource);
        }
        $this->actionEntries = $actions;
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
        return true;
    }

    /**
     * This toolbar is rendered if there are action entries, no further user restriction
     *
     * @return bool TRUE
     */
    public function checkAccess()
    {
        $result = false;
        if (!empty($this->actionEntries)) {
            $result = true;
        }
        return $result;
    }

    /**
     * Position relative to others
     *
     * @return int
     */
    public function getIndex()
    {
        return 35;
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

    /**
     * Return DatabaseConnection
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
