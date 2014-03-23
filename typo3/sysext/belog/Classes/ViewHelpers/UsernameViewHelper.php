<?php
namespace TYPO3\CMS\Belog\ViewHelpers;

/**
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
 * Get username from backend user id
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class UsernameViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Repository\BackendUserRepository
	 * @inject
	 */
	protected $backendUserRepository;

	/**
	 * First level cache of user names
	 *
	 * @var array
	 */
	static protected $usernameRuntimeCache = array();

	/**
	 * Resolve user name from backend user id.
	 *
	 * @param integer $uid Uid of the user
	 * @return string Username or an empty string if there is no user with that UID
	 */
	public function render($uid) {
		if (isset(static::$usernameRuntimeCache[$uid])) {
			return static::$usernameRuntimeCache[$uid];
		}

		/** @var $user \TYPO3\CMS\Extbase\Domain\Model\BackendUser */
		$user = $this->backendUserRepository->findByUid($uid);
		// $user may be NULL if user was deleted from DB, set it to empty string to always return a string
		static::$usernameRuntimeCache[$uid] = ($user === NULL) ? '' : $user->getUserName();
		return static::$usernameRuntimeCache[$uid];
	}

}
