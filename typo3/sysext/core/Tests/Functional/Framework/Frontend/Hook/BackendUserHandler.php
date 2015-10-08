<?php
namespace TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\Hook;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handler for backend user
 */
class BackendUserHandler implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @param array $parameters
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontendController
     */
    public function initialize(array $parameters, \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontendController)
    {
        $backendUserId = (int)GeneralUtility::_GP('backendUserId');
        $workspaceId = (int)GeneralUtility::_GP('workspaceId');

        if (empty($backendUserId) || empty($workspaceId)) {
            return;
        }

        $backendUser = $this->createBackendUser();
        $backendUser->user = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'be_users', 'uid=' . $backendUserId);
        $backendUser->setTemporaryWorkspace($workspaceId);
        $frontendController->beUserLogin = true;

        $parameters['BE_USER'] = $backendUser;
        $GLOBALS['BE_USER'] = $backendUser;
    }

    /**
     * @return \TYPO3\CMS\Backend\FrontendBackendUserAuthentication
     */
    protected function createBackendUser()
    {
        return GeneralUtility::makeInstance(
            \TYPO3\CMS\Backend\FrontendBackendUserAuthentication::class
        );
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
