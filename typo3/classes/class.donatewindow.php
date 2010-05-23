<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Oliver Hader <oliver@typo3.org>
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * AJAX handler for the donate window shown in the TYPO3 backend.
 *
 * @author Oliver Hader <oliver@typo3.org>
 */
class DonateWindow implements t3lib_Singleton {
	const FLAG_DonateWindowDisabled = 'DonateWindowDisabled';
	const FLAG_DonateWindowPostponed = 'DonateWindowPostponed';
	const VALUE_DonateWindowAppearsAfterDays = 90;
	const VALUE_DonateWindowPostponeDays = 14;

	/**
	 * @var t3lib_beUserAuth
	 */
	protected $backendUser;

	/**
	 * Constructs this object.
	 */
	public function __construct() {
		$this->setBackendUser($GLOBALS['BE_USER']);
	}

	/**
	 * Sets the backend user.
	 *
	 * @param t3lib_beUserAuth $backendUser
	 * @return void
	 */
	public function setBackendUser(t3lib_beUserAuth $backendUser) {
		$this->backendUser = $backendUser;
	}

	/**
	 * Disables the donate window - thus it won't be shown again for this user.
	 *
	 * @param	array		$parameters: Parameters (not used)
	 * @param	TYPO3AJAX	$ajaxObj: The calling parent AJAX object
	 * @return	void
	 */
	public function disable(array $parameters, TYPO3AJAX $ajaxObj) {
		$this->backendUser->uc[self::FLAG_DonateWindowDisabled] = TYPO3_version;
		$this->backendUser->writeUC();
	}

	/**
	 * Postpones the donate window - thus it will be shown again at a later time.
	 *
	 * @param	array		$parameters: Parameters (not used)
	 * @param	TYPO3AJAX	$ajaxObj: The calling parent AJAX object
	 * @return	void
	 */
	public function postpone(array $parameters, TYPO3AJAX $ajaxObj) {
		$this->backendUser->uc[self::FLAG_DonateWindowPostponed] = $GLOBALS['EXEC_TIME'];
		$this->backendUser->writeUC();
	}


	/**
	 * Determines whether the donate window is allowed to be displayed.
	 *
	 * @return boolean Whether the donate window is allowed to be displayed.
	 */
	public function isDonateWindowAllowed() {
		$uc = $this->backendUser->uc;
		$isAdmin = $this->backendUser->isAdmin();
		$firstLogin = $this->getFirstLoginTimeStamp();
		$isTriggered = ($firstLogin && $GLOBALS['EXEC_TIME'] - $firstLogin > self::VALUE_DonateWindowAppearsAfterDays * 86400);
		$isAllowed = (bool) $GLOBALS['TYPO3_CONF_VARS']['BE']['allowDonateWindow'];
		$isCancelled = (isset($uc[self::FLAG_DonateWindowDisabled]) && !empty($uc[self::FLAG_DonateWindowDisabled]));
		$isPostponed = (isset($uc[self::FLAG_DonateWindowPostponed]) && $uc[self::FLAG_DonateWindowPostponed] > $GLOBALS['EXEC_TIME'] - self::VALUE_DonateWindowPostponeDays * 86400);

		return ($isAdmin && $isAllowed && $isTriggered && !$isCancelled && !$isPostponed);
	}

	/**
	 * Gets the timestamp of the first login of the current backend user.
	 *
	 * @return integer Timestamp of the first login
	 */
	public function getFirstLoginTimeStamp() {
		$firstLogin = NULL;

		if (isset($this->backendUser->uc['firstLoginTimeStamp'])) {
			$firstLogin = $this->backendUser->uc['firstLoginTimeStamp'];
		}

		return $firstLogin;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.donatewindow.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/classes/class.donatewindow.php']);
}

?>