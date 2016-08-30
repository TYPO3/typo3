<?php
namespace TYPO3\CMS\SysNote\Domain\Repository;

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
 * Sys_note repository
 */
class SysNoteRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * Initialize the repository
     *
     * @return void
     */
    public function initializeObject()
    {
        $querySettings = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Find notes by given pids and author
     *
     * @param string $pids Single PID or comma separated list of PIDs
     * @param \TYPO3\CMS\Extbase\Domain\Model\BackendUser $author The author
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface
     */
    public function findByPidsAndAuthor($pids, \TYPO3\CMS\Extbase\Domain\Model\BackendUser $author)
    {
        $pids = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', (string)$pids);
        $query = $this->createQuery();
        $query->setOrderings([
            'sorting' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING,
            'creationDate' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
        ]);
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
