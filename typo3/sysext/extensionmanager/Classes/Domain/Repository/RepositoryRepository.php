<?php
namespace TYPO3\CMS\Extensionmanager\Domain\Repository;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog, <susanne.moog@typo3.org>
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
 */
class RepositoryRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	/**
	 * Do not include pid in queries
	 *
	 * @return void
	 */
	public function initializeObject() {
		/** @var $defaultQuerySettings \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface */
		$defaultQuerySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface');
		$defaultQuerySettings->setRespectStoragePage(FALSE);
		$this->setDefaultQuerySettings($defaultQuerySettings);
	}

	/**
	 * Updates ExtCount and lastUpdated in Repository eg after import
	 *
	 * @param integer $extCount
	 * @param integer $uid
	 * @return void
	 */
	public function updateRepositoryCount($extCount, $uid = 1) {
		$repository = $this->findByUid($uid);

		$repository->setLastUpdate(new \DateTime());
		$repository->setExtensionCount(intval($extCount));

		$this->update($repository);
	}

	/**
	 * Find main typo3.org repository
	 *
	 * @return \TYPO3\CMS\Extensionmanager\Domain\Model\Repository
	 */
	public function findOneTypo3OrgRepository() {
		$allRepositories = $this->findAll();
		$typo3OrgRepository = NULL;
		foreach ($allRepositories as $repository) {
			/** @var $repository \TYPO3\CMS\Extensionmanager\Domain\Model\Repository */
			if ($repository->getTitle() === 'TYPO3.org Main Repository') {
				$typo3OrgRepository = $repository;
				break;
			}
		}
		return $typo3OrgRepository;
	}
}
?>