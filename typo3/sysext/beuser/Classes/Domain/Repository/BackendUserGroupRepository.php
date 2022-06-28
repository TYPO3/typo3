<?php

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

namespace TYPO3\CMS\Beuser\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for \TYPO3\CMS\Beuser\Domain\Model\BackendUserGroup
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendUserGroupRepository extends Repository
{
    protected $defaultOrderings = [
        'title' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * Overwrite createQuery to don't respect enable fields
     *
     * @return \TYPO3\CMS\Extbase\Persistence\QueryInterface
     */
    public function createQuery()
    {
        $query = parent::createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        return $query;
    }

    /**
     * Finds Backend Usergroups on a given list of uids
     *
     * @param array $uidList
     * @return array
     */
    public function findByUidList(array $uidList): array
    {
        $items = [];

        foreach ($uidList as $id) {
            $query = $this->createQuery();
            $query->matching($query->equals('uid', $id));
            $result = $query->execute(true);
            if ($result) {
                $items[] = $result[0];
            }
        }
        return $items;
    }
}
