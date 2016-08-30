<?php
namespace TYPO3\CMS\Saltedpasswords\Task;

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
 * Update plaintext and hashed passwords of existing users to salted passwords.
 */
class BulkUpdateTask extends \TYPO3\CMS\Scheduler\Task\AbstractTask
{
    /**
     * @var bool Whether or not the task is allowed to deactivate itself after processing all existing user records.
     */
    protected $canDeactivateSelf = true;

    /**
     * Converting a password to a salted hash takes some milliseconds (~100ms on an entry system in 2010).
     * If all users are updated in one run, the task might run a long time if a lot of users must be handled.
     * Therefore only a small number of frontend and backend users are processed.
     * If saltedpasswords is enabled for both frontend and backend 2 * numberOfRecords will be handled.
     *
     * @var int Number of records
     */
    protected $numberOfRecords = 250;

    /**
     * @var int Pointer to last handled frontend and backend user row
     */
    protected $userRecordPointer = [];

    /**
     * Constructor initializes user record pointer
     */
    public function __construct()
    {
        parent::__construct();
        $this->userRecordPointer = [
            'FE' => 0,
            'BE' => 0
        ];
    }

    /**
     * Execute task
     *
     * @return bool
     */
    public function execute()
    {
        $processedAllRecords = true;
        // For frontend and backend
        foreach ($this->userRecordPointer as $mode => $pointer) {
            // If saltedpasswords is active for frontend / backend
            if (\TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled($mode)) {
                $usersToUpdate = $this->findUsersToUpdate($mode);
                $numberOfRows = count($usersToUpdate);
                if ($numberOfRows > 0) {
                    $processedAllRecords = false;
                    $this->activateSelf();
                    $this->incrementUserRecordPointer($mode, $numberOfRows);
                    $this->convertPasswords($mode, $usersToUpdate);
                }
            }
        }
        if ($processedAllRecords) {
            // Reset the user record pointer
            $this->userRecordPointer = [
                'FE' => 0,
                'BE' => 0
            ];
            // Determine if task should disable itself
            if ($this->canDeactivateSelf) {
                $this->deactivateSelf();
            }
        }
        // Use save() of parent class \TYPO3\CMS\Scheduler\Task\AbstractTask to persist changed task variables
        $this->save();
        return true;
    }

    /**
     * Get additional information
     *
     * @return string Additional information
     */
    public function getAdditionalInformation()
    {
        $information = $GLOBALS['LANG']->sL('LLL:EXT:saltedpasswords/Resources/Private/Language/locallang.xlf:ext.saltedpasswords.tasks.bulkupdate.label.additionalinformation.deactivateself') . $this->getCanDeactivateSelf() . '; ' . $GLOBALS['LANG']->sL('LLL:EXT:saltedpasswords/Resources/Private/Language/locallang.xlf:ext.saltedpasswords.tasks.bulkupdate.label.additionalinformation.numberofrecords') . $this->getNumberOfRecords();
        return $information;
    }

    /**
     * Finds next set of frontend or backend users to update.
     *
     * @param string $mode 'FE' for frontend, 'BE' for backend user records
     * @return array Rows with uid and password
     */
    protected function findUsersToUpdate($mode)
    {
        $usersToUpdate = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid, password', strtolower($mode) . '_users', '1 = 1', '', 'uid ASC', $this->userRecordPointer[$mode] . ', ' . $this->numberOfRecords);
        return $usersToUpdate;
    }

    /**
     * Iterates over given user records and update password if needed.
     *
     * @param string $mode 'FE' for frontend, 'BE' for backend user records
     * @param array $users With user uids and passwords
     * @return void
     */
    protected function convertPasswords($mode, array $users)
    {
        $updateUsers = [];
        foreach ($users as $user) {
            // If a password is already a salted hash it must not be updated
            if ($this->isSaltedHash($user['password'])) {
                continue;
            }
            $updateUsers[] = $user;
        }
        if (!empty($updateUsers)) {
            $this->updatePasswords($mode, $updateUsers);
        }
    }

    /**
     * Updates password and persist salted hash.
     *
     * @param string $mode 'FE' for frontend, 'BE' for backend user records
     * @param array $users With user uids and passwords
     * @return void
     */
    protected function updatePasswords($mode, array $users)
    {
        /** @var $saltedpasswordsInstance \TYPO3\CMS\Saltedpasswords\Salt\SaltInterface */
        $saltedpasswordsInstance = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(null, $mode);
        foreach ($users as $user) {
            $newPassword = $saltedpasswordsInstance->getHashedPassword($user['password']);
            // If a given password is a md5 hash (usually default be_users without saltedpasswords activated),
            // result of getHashedPassword() is a salted hashed md5 hash.
            // We prefix those with 'M', saltedpasswords will then update this password
            // to a usual salted hash upon first login of the user.
            if ($this->isMd5Password($user['password'])) {
                $newPassword = 'M' . $newPassword;
            }
            // Persist updated password
            $GLOBALS['TYPO3_DB']->exec_UPDATEquery(strtolower($mode) . '_users', 'uid = ' . $user['uid'], [
                'password' => $newPassword
            ]);
        }
    }

    /**
     * Passwords prefixed with M or C might be salted passwords:
     * M means: originally a md5 hash before it was salted (eg. default be_users).
     * C means: originally a cleartext password with lower hash looping count generated by t3sec_saltedpw.
     * Both M and C will be updated to usual salted hashes on first login of user.
     *
     * If a password does not start with M or C determine if a password is already a usual salted hash.
     *
     * @param string $password Password
     * @return bool TRUE if password is a salted hash
     */
    protected function isSaltedHash($password)
    {
        $isSaltedHash = false;
        if (strlen($password) > 2 && (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($password, 'C$') || \TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($password, 'M$'))) {
            // Cut off M or C and test if we have a salted hash
            $isSaltedHash = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::determineSaltingHashingMethod(substr($password, 1));
        }
        // Test if given password is already a usual salted hash
        if (!$isSaltedHash) {
            $isSaltedHash = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::determineSaltingHashingMethod($password);
        }
        return $isSaltedHash;
    }

    /**
     * Checks if a given password is a md5 hash, the default for be_user records before saltedpasswords.
     *
     * @param string $password The password to test
     * @return bool TRUE if password is md5
     */
    protected function isMd5Password($password)
    {
        return (bool)preg_match('/[0-9abcdef]{32,32}/i', $password);
    }

    /**
     * Increments current user record counter by number of handled rows.
     *
     * @param string $mode 'FE' for frontend, 'BE' for backend user records
     * @param int $number Number of handled rows
     * @return void
     */
    protected function incrementUserRecordPointer($mode, $number)
    {
        $this->userRecordPointer[$mode] += $number;
    }

    /**
     * Activates this task instance.
     * Uses setDisabled() method of parent \TYPO3\CMS\Scheduler\Task\AbstractTask
     *
     * @return void
     */
    protected function activateSelf()
    {
        $this->setDisabled(false);
    }

    /**
     * Deactivates this task instance.
     * Uses setDisabled() method of parent \TYPO3\CMS\Scheduler\Task\AbstractTask
     *
     * @return void
     */
    protected function deactivateSelf()
    {
        $this->setDisabled(true);
    }

    /**
     * Set if it can deactivate self
     *
     * @param bool $canDeactivateSelf
     * @return void
     */
    public function setCanDeactivateSelf($canDeactivateSelf)
    {
        $this->canDeactivateSelf = $canDeactivateSelf;
    }

    /**
     * Get if it can deactivate self
     *
     * @return bool TRUE if task shall deactivate itself, FALSE otherwise
     */
    public function getCanDeactivateSelf()
    {
        return $this->canDeactivateSelf;
    }

    /**
     * Set number of records
     *
     * @param int $numberOfRecords
     * @return void
     */
    public function setNumberOfRecords($numberOfRecords)
    {
        $this->numberOfRecords = $numberOfRecords;
    }

    /**
     * Get number of records
     *
     * @return int The number of records
     */
    public function getNumberOfRecords()
    {
        return $this->numberOfRecords;
    }
}
