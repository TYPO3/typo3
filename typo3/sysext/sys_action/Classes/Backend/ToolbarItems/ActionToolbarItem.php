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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\RootLevelRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\SysAction\ActionTask;

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
        $title = htmlspecialchars($this->getLanguageService()->getLL('action_toolbaritem'));
        $icon = $this->iconFactory->getIcon('apps-toolbar-menu-actions', Icon::SIZE_SMALL)->render('inline');
        return '
            <span class="toolbar-item-icon" title="' . $title . '">' . $icon . '</span>
            <span class="toolbar-item-title">' . $title . '</span>
        ';
    }

    /**
     * Render drop down
     *
     * @return string HTML
     */
    public function getDropDown()
    {
        $actionMenu = [];
        $actionMenu[] = '<h3 class="dropdown-headline">' . htmlspecialchars($this->getLanguageService()->getLL('sys_action')) . '</h3>';
        $actionMenu[] = '<hr>';
        $actionMenu[] = '<div class="dropdown-table">';
        foreach ($this->actionEntries as $linkConf) {
            $actionMenu[] = '<div class="dropdown-table-row">';
            $actionMenu[] = '<div class="dropdown-table-column dropdown-table-icon">';
            $actionMenu[] = $linkConf[2];
            $actionMenu[] = '</div>';
            $actionMenu[] = '<div class="dropdown-table-column dropdown-table-title">';
            $actionMenu[] = '<a class="t3js-topbar-link" href="' . htmlspecialchars($linkConf[1]) . '" target="list_frame">';
            $actionMenu[] = htmlspecialchars($linkConf[0]);
            $actionMenu[] = '</a>';
            $actionMenu[] = '</div>';
            $actionMenu[] = '</div>';
        }
        $actionMenu[] = '</div>';
        return implode(LF, $actionMenu);
    }

    /**
     * Gets the entries for the action menu
     */
    protected function initializeActionEntries()
    {
        $backendUser = $this->getBackendUser();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_action');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(HiddenRestriction::class))
            ->add(GeneralUtility::makeInstance(RootLevelRestriction::class, [
                'sys_action'
            ]));

        $queryBuilder
            ->select('sys_action.*')
            ->from('sys_action');

        if (!empty($GLOBALS['TCA']['sys_action']['ctrl']['sortby'])) {
            $queryBuilder->orderBy('sys_action.' . $GLOBALS['TCA']['sys_action']['ctrl']['sortby']);
        }

        $actions = [];
        if (!$backendUser->isAdmin()) {
            $groupList = $backendUser->groupList ?: '0';

            $queryBuilder
                ->join(
                    'sys_action',
                    'sys_action_asgr_mm',
                    'sys_action_asgr_mm',
                    $queryBuilder->expr()->eq(
                        'sys_action_asgr_mm.uid_local',
                        $queryBuilder->quoteIdentifier('sys_action.uid')
                    )
                )
                ->join(
                    'sys_action_asgr_mm',
                    'be_groups',
                    'be_groups',
                    $queryBuilder->expr()->eq(
                        'sys_action_asgr_mm.uid_foreign',
                        $queryBuilder->quoteIdentifier('be_groups.uid')
                    )
                )
                ->where(
                    $queryBuilder->expr()->in(
                        'be_groups.uid',
                        $queryBuilder->createNamedParameter(
                            GeneralUtility::intExplode(',', $groupList, true),
                            Connection::PARAM_INT_ARRAY
                        )
                    )
                )
                ->groupBy('sys_action.uid');
        }

        $result = $queryBuilder->execute();
        while ($actionRow = $result->fetch()) {
            $actions[] = [
                $actionRow['title'],
                sprintf(
                    '%s&SET[mode]=tasks&SET[function]=sys_action.%s&show=%u',
                    BackendUtility::getModuleUrl('user_task'),
                    ActionTask::class, // @todo: class name string is hand over as url parameter?!
                    $actionRow['uid']
                ),
                $this->iconFactory->getIconForRecord('sys_action', $actionRow, Icon::SIZE_SMALL)->render()
            ];
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
}
