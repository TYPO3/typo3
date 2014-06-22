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
	 * Resolve user name from backend user id.
	 *
	 * @param integer $uid Uid of the user
	 * @return string Username or an empty string if there is no user with that UID
	 */
	public function render($uid) {
		/** @var $user \TYPO3\CMS\Extbase\Domain\Model\BackendUser */
		$user = $this->backendUserRepository->findByUid($uid);
		if ($user === NULL) {
			return '';
		}
		return $user->getUserName();
	}

}
