<?php
namespace ExtbaseTeam\BlogExample\Domain\Repository;

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
 * A repository for blogs
 */
class BlogRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    protected $defaultOrderings = [
        'crdate' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING,
        'uid' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING
    ];

    /**
     * Life cycle method.
     *
     * @return void
     */
    public function initializeObject()
    {
        /*
        $querySettings = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class);
        $querySettings->setRespectStoragePage(FALSE);
        $this->setDefaultQuerySettings($querySettings);
         */
    }
}
