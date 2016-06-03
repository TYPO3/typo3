<?php
namespace TYPO3\CMS\Belog\Controller;

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

use TYPO3\CMS\Backend\Backend\ToolbarItems\SystemInformationToolbarItem;
use TYPO3\CMS\Backend\Toolbar\Enumeration\InformationStatus;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Belog\Domain\Model\Constraint;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;

/**
 * Count newest exceptions for the system information menu
 */
class SystemInformationController extends AbstractController
{
    /**
     * Modifies the SystemInformation array
     *
     * @param SystemInformationToolbarItem $systemInformationToolbarItem
     */
    public function appendMessage(SystemInformationToolbarItem $systemInformationToolbarItem)
    {
        $constraint = $this->getConstraintFromBeUserData();
        if ($constraint === null) {
            $constraint = $this->objectManager->get(Constraint::class);
        }

        $timestamp = $constraint->getStartTimestamp();
        $backendUser = $this->getBackendUserAuthentication();
        if (isset($backendUser->uc['systeminformation'])) {
            $systemInformationUc = json_decode($backendUser->uc['systeminformation'], true);
            if (isset($systemInformationUc['system_BelogLog']['lastAccess'])) {
                $timestamp = $systemInformationUc['system_BelogLog']['lastAccess'];
            }
        }

        $this->setStartAndEndTimeFromTimeSelector($constraint);
        // we can't use the extbase repository here as the required TypoScript may not be parsed yet
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
        $count = $queryBuilder->count('error')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->gte('tstamp', $timestamp),
                $queryBuilder->expr()->in('error', [-1, 1, 2])
            )
            ->execute()
            ->fetchColumn(0);

        if ($count > 0) {
            $systemInformationToolbarItem->addSystemMessage(
                sprintf(LocalizationUtility::translate('systemmessage.errorsInPeriod', 'belog'), $count, BackendUtility::getModuleUrl('system_BelogLog')),
                InformationStatus::STATUS_ERROR,
                $count,
                'system_BelogLog'
            );
        }
    }

    /**
     * Get module states (the constraint object) from user data
     *
     * @return \TYPO3\CMS\Belog\Domain\Model\Constraint|NULL
     */
    protected function getConstraintFromBeUserData()
    {
        $serializedConstraint = $this->getBackendUserAuthentication()->getModuleData(ToolsController::class);
        if (!is_string($serializedConstraint) || empty($serializedConstraint)) {
            return null;
        }
        return @unserialize($serializedConstraint);
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
