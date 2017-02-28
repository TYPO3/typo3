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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\SysAction\ActionTask;

/**
 * Adds action links to the backend's toolbar
 */
class ActionToolbarItem implements ToolbarItemInterface
{
    /**
     * @var array
     */
    protected $availableActions = [];

    /**
     * Render toolbar icon via Fluid
     *
     * @return string HTML
     */
    public function getItem()
    {
        return $this->getFluidTemplateObject('ToolbarItem.html')->render();
    }

    /**
     * Render drop down
     *
     * @return string HTML
     */
    public function getDropDown()
    {
        $view = $this->getFluidTemplateObject('DropDown.html');
        $view->assign('actions', $this->availableActions);
        return $view->render();
    }

    /**
     * Stores the entries for the action menu in $this->availableActions
     */
    protected function setAvailableActions()
    {
        $actionEntries = [];
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
            $actionRow['link'] = sprintf(
                '%s&SET[mode]=tasks&SET[function]=sys_action.%s&show=%u',
                BackendUtility::getModuleUrl('user_task'),
                ActionTask::class, // @todo: class name string is hand over as url parameter?!
                $actionRow['uid']
            );
            $actionEntries[] = $actionRow;
        }

        $this->availableActions = $actionEntries;
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
     * @return bool
     */
    public function checkAccess()
    {
        $this->setAvailableActions();
        return !empty($this->availableActions);
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
     * Returns a new standalone view, shorthand function
     *
     * @param string $filename Which templateFile should be used.
     * @return StandaloneView
     */
    protected function getFluidTemplateObject(string $filename): StandaloneView
    {
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths(['EXT:sys_action/Resources/Private/Layouts']);
        $view->setPartialRootPaths([
            'EXT:backend/Resources/Private/Partials/ToolbarItems',
            'EXT:sys_action/Resources/Private/Partials'
        ]);
        $view->setTemplateRootPaths(['EXT:sys_action/Resources/Private/Templates/ToolbarItems']);
        $view->setTemplate($filename);

        $view->getRequest()->setControllerExtensionName('SysAction');
        return $view;
    }
}
