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
 * Handler for frontend user
 */
class FrontendUserHandler implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Initialize
     *
     * @param array $parameters
     * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontendController
     */
    public function initialize(array $parameters, \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontendController)
    {
        $frontendUserId = (int)GeneralUtility::_GP('frontendUserId');
        $frontendController->fe_user->checkPid = 0;

        $frontendUser = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'fe_users', 'uid =' . $frontendUserId);
        if (is_array($frontendUser)) {
            $frontendController->loginUser = 1;
            $frontendController->fe_user->createUserSession($frontendUser);
            $frontendController->fe_user->user = $GLOBALS['TSFE']->fe_user->fetchUserSession();
            $frontendController->initUserGroups();
        }
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
