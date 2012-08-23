<?php
namespace TYPO3\CMS\Extensionmanager\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog, <susanne.moog@typo3.org>
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
 * A repository for extension repositories
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package Extension Manager
 * @subpackage Repository
 */
class RepositoryRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	/**
	 * Updates ExtCount and lastUpdated in Repository eg after import
	 *
	 * @param integer $extCount
	 * @param integer $uid
	 * @return void
	 */
	public function updateRepositoryCount($extCount, $uid = 1) {
		$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_ter', 'uid=' . intval($uid), array(
			'lastUpdated' => time(),
			'extCount' => intval($extCount)
		));
	}

}


?>