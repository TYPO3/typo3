<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Christian Kuhn <lolli@schwarzbu.ch>
*		Marcus Krause <marcus#exp2010@t3sec.info>
*
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Update plaintext and hashed passwords of existing users to salted passwords.
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @author Marcus Krause <marcus#exp2010@t3sec.info>
 * @package TYPO3
 * @subpackage saltedpasswords
 */
class tx_saltedpasswords_Tasks_BulkUpdate extends tx_scheduler_Task {
	/**
	 * @var boolean Whether or not the task is allowed to deactivate itself after processing all existing user records.
	 * @TODO: This could be set with an additional field later on.
	 *		The idea is to not disable the task after all initial users where handled.
	 *		This could be handy for example if new users are imported regularily from some external source.
	 */
	protected $canDeactivateSelf = TRUE;

	/**
	 * Converting a password to a salted hash takes some milliseconds (~100ms on an entry system in 2010).
	 * If all users are updated in one run, the task might run a long time if a lot of users must be handled.
	 * Therefore only a small number of frontend and backend users are processed.
	 * If saltedpasswords is enabled for both frontend and backend 2 * numberOfRecords will be handled.
	 *
	 * @var integer Number of records
	 * @TODO: This could be set with an additional field later on
	 */
	protected $numberOfRecords = 250;

	/**
	 * @var integer Pointer to last handled frontend and backend user row
	 */
	protected $userRecordPointer = array();

	/**
	 * Constructor initializes user record pointer
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();

		$this->userRecordPointer = array(
			'FE' => 0,
			'BE' => 0,
		);
	}

	/**
	 * Execute task
	 *
	 * @return void
	 */
	public function execute() {
		$saltedpasswordsInstanceBE = tx_saltedpasswords_salts_factory::getSaltingInstance(NULL, 'BE');
		$saltedpasswordsInstanceFE = tx_saltedpasswords_salts_factory::getSaltingInstance(NULL, 'FE');
		if ($saltedpasswordsInstanceBE instanceof tx_saltedpasswords_salts_blowfish || $saltedpasswordsInstanceFE instanceof tx_saltedpasswords_salts_blowfish) {
			$fieldInformationFE = $GLOBALS['TYPO3_DB']->admin_get_fields('fe_users');
			$fieldInformationBE = $GLOBALS['TYPO3_DB']->admin_get_fields('be_users');
			if ($fieldInformationBE['password']['Type'] === 'varchar(60)' || $fieldInformationFE['password']['Type'] === 'varchar(60)') {
				throw new RuntimeException('You need to execute a database compare within the install tool or increase the size of the password fields to at least varchar(61).', 1343568853);
			}
		}

		$processedAllRecords = TRUE;

			// For frontend and backend
		foreach ($this->userRecordPointer as $mode => $pointer) {
				// If saltedpasswords is active for frontend / backend
			if (tx_saltedpasswords_div::isUsageEnabled($mode)) {
				$usersToUpdate = $this->findUsersToUpdate($mode);
				$numberOfRows = count($usersToUpdate);
				if ($numberOfRows > 0) {
					$processedAllRecords = FALSE;
					$this->incrementUserRecordPointer($mode, $numberOfRows);
					$this->convertPasswords($mode, $usersToUpdate);
				}
			}
		}

			// Determine if task should disable itself
		if ($this->canDeactivateSelf && $processedAllRecords) {
			$this->deactivateSelf();
		}

			// Use save() of parent class tx_scheduler_Task to persist
			// changed task variables: $this->userRecordPointer and $this->disabled
		$this->save();

		return(TRUE);
	}

	/**
	 * Find next set of frontend or backend users to update.
	 *
	 * @param string 'FE' for frontend, 'BE' for backend user records
	 * @return array Rows with uid and password
	 */
	protected function findUsersToUpdate($mode) {
		$usersToUpdate = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'uid, password',
			strtolower($mode) . '_users',
			'1 = 1',  // retrieve and update all records (also disabled/deleted) for security reasons
			'',
			'uid ASC',
			$this->userRecordPointer[$mode] . ', ' . $this->numberOfRecords
		);

		return $usersToUpdate;
	}

	/**
	 * Iterate over given user records and update password if needed.
	 *
	 * @param string 'FE' for frontend, 'BE' for backend user records
	 * @param array with user uids and passwords
	 * @return void
	 */
	protected function convertPasswords($mode, $users) {
		$updateUsers = array();
		foreach ($users as $user) {
				// If a password is already a salted hash it must not be updated
			if ($this->isSaltedHash($user['password'])) {
				continue;
			}

			$updateUsers[] = $user;
		}

		if (count($updateUsers) > 0) {
			$this->updatePasswords($mode, $updateUsers);
		}
	}

	/**
	 * Update password and persist salted hash.
	 *
	 * @param string 'FE' for frontend, 'BE' for backend user records
	 * @param array with user uids and passwords
	 * @return void
	 */
	protected function updatePasswords($mode, $users) {
			// Get a default saltedpasswords instance
		$saltedpasswordsInstance = tx_saltedpasswords_salts_factory::getSaltingInstance(NULL, $mode);

		foreach ($users as $user) {
			$newPassword = $saltedpasswordsInstance->getHashedPassword($user['password']);

				// If a given password is a md5 hash (usually default be_users without saltedpasswords activated),
				// result of getHasedPassword() is a salted hashed md5 hash.
				// We prefix those with 'M', saltedpasswords will then update this password
				// to a usual salted hash upon first login of the user.
			if ($this->isMd5Password($user['password'])) {
				$newPassword = 'M' . $newPassword;
			}

				// Persist updated password
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery(
				strtolower($mode) . '_users',
				'uid = ' . $user['uid'],
				array(
					'password' => $newPassword
				)
			);
		}
	}

	/**
	 * Passwords prefixed with M or C might be salted passwords:
	 *	M means: originally a md5 hash before it was salted (eg. default be_users).
	 *	C means: originally a cleartext password with lower hash looping count generated by t3sec_saltedpw.
	 * Both M and C will be updated to usual salted hashes on first login of user.
	 *
	 * If a password does not start with M or C determine if a password is already a usual salted hash.
	 *
	 * @param string Password
	 * @return boolean TRUE if password is a salted hash
	 */
	protected function isSaltedHash($password) {
		$isSaltedHash = FALSE;
		if (strlen($password) > 2 && (t3lib_div::isFirstPartOfStr($password, 'C$') || t3lib_div::isFirstPartOfStr($password, 'M$'))) {
				// Cut off M or C and test if we have a salted hash
			$isSaltedHash = tx_saltedpasswords_salts_factory::determineSaltingHashingMethod(substr($password, 1));
		}

			// Test if given password is a already a usual salted hash
		if (!$isSaltedHash) {
			$isSaltedHash = tx_saltedpasswords_salts_factory::determineSaltingHashingMethod($password);
		}

		return $isSaltedHash;
	}

	/**
	 * Check if a given password is a md5 hash, the default for be_user records before saltedpasswords.
	 *
	 * @return boolean TRUE if password is md5
	 */
	protected function isMd5Password($password) {
		return (bool) preg_match('/[0-9abcdef]{32,32}/i', $password);
	}

	/**
	 * Increment current user record counter by number of handled rows.
	 *
	 * @param string 'FE' for frontend, 'BE' for backend user records
	 * @param integer Number of handled rows
	 * @return void
	 */
	protected function incrementUserRecordPointer($mode, $number) {
		$this->userRecordPointer[$mode] += $number;
	}

	/**
	 * Deactivate this task instance.
	 * Uses setDisabled() method of parent class tx_scheduler_Task.
	 *
	 * @return void
	 */
	protected function deactivateSelf() {
		$this->setDisabled(TRUE);
	}
} // End of class

if (defined('TYPO3_MODE') && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/saltedpasswords/classes/tasks/class.tx_saltedpasswords_tasks_bulkupdate.php']) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/saltedpasswords/classes/tasks/class.tx_saltedpasswords_tasks_bulkupdate.php']);
}

?>