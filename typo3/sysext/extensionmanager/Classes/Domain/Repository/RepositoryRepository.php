<?php
namespace TYPO3\CMS\Extensionmanager\Domain\Repository;

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
 * A repository for extension repositories
 */
class RepositoryRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * Do not include pid in queries
     *
     * @return void
     */
    public function initializeObject()
    {
        /** @var $defaultQuerySettings \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface */
        $defaultQuerySettings = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class);
        $defaultQuerySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($defaultQuerySettings);
    }

    /**
     * Updates ExtCount and lastUpdated in Repository eg after import
     *
     * @param int $extCount
     * @param int $uid
     * @return void
     */
    public function updateRepositoryCount($extCount, $uid = 1)
    {
        $repository = $this->findByUid($uid);

        $repository->setLastUpdate(new \DateTime());
        $repository->setExtensionCount((int)$extCount);

        $this->update($repository);
    }

    /**
     * Find main typo3.org repository
     *
     * @return \TYPO3\CMS\Extensionmanager\Domain\Model\Repository
     */
    public function findOneTypo3OrgRepository()
    {
        $allRepositories = $this->findAll();
        $typo3OrgRepository = null;
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
