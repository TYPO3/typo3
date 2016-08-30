<?php
namespace TYPO3\CMS\Install\Updates;

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

/**
 * Update backend user setting startModule if set to "help_aboutmodules"
 */
class BackendUserStartModuleUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Update backend user setting "startModule"';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        $backendUsersCount = $this->getDatabaseConnection()->exec_SELECTcountRows('uid', 'be_users');
        if ($this->isWizardDone() || $backendUsersCount === 0) {
            return false;
        }

        $description = 'The backend user setting startModule is changed for the extension aboutmodules. Update all backend users that use ext:aboutmodules as startModule.';

        return true;
    }

    /**
     * Performs the database update if backend user's startmodule is help_aboutmodules
     *
     * @param array &$databaseQueries Queries done in this update
     * @param mixed &$customMessages Custom messages
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessages)
    {
        $db = $this->getDatabaseConnection();
        $backendUsers = $db->exec_SELECTgetRows('uid,uc', 'be_users', '1=1');
        if (!empty($backendUsers)) {
            foreach ($backendUsers as $backendUser) {
                if ($backendUser['uc'] !== null) {
                    $userConfig = unserialize($backendUser['uc']);
                    if ($userConfig['startModule'] === 'help_aboutmodules') {
                        $userConfig['startModule'] = 'help_AboutmodulesAboutmodules';
                        $db->exec_UPDATEquery(
                            'be_users',
                            'uid=' . (int)$backendUser['uid'],
                            [
                                'uc' => serialize($userConfig),
                            ]
                        );
                        $databaseQueries[] = $db->debug_lastBuiltQuery;
                    }
                }
            }
        }

        $this->markWizardAsDone();
        return true;
    }
}
