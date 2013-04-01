<?php
namespace TYPO3\CMS\SysNote\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Georg Ringer <typo3@ringerge.org>
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
 * Sys_note repository
 *
 * @author Georg Ringer <typo3@ringerge.org>
 * @author Kai Vogel <kai.vogel@speedprogs.de>
 */
class SysNoteRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	/**
	 * Initialize the repository
	 *
	 * @return void
	 */
	public function initializeObject() {
		$querySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\Typo3QuerySettings');
		$querySettings->setRespectStoragePage(FALSE);
		$this->setDefaultQuerySettings($querySettings);
	}

	/**
	 * Find notes by given pids and author
	 *
	 * @param string $pids Single PID or comma separated list of PIDs
	 * @param \TYPO3\CMS\Extbase\Domain\Model\BackendUser $author The author
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
	 */
	public function findByPidsAndAuthor($pids, \TYPO3\CMS\Extbase\Domain\Model\BackendUser $author) {
		$pids = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', (string) $pids);
		$query = $this->createQuery();
		$query->setOrderings(array(
			'sorting' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING,
			'creationDate' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
		));
		$query->matching(
			$query->logicalAnd(
				$query->in('pid', $pids),
				$query->logicalOr(
					$query->equals('personal', 0),
					$query->equals('author', $author)
				)
			)
		);
		return $query->execute();
	}

}
?>