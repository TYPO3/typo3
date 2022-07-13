<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Recycler\Tests\Functional\Recycle;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recycler\Domain\Model\DeletedRecords;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractRecycleTestCase extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['recycler'];

    /**
     * Retrieves deleted pages using the recycler domain model "deletedRecords" class.
     *
     * @param int $pageUid
     * @param int $depth
     * @return array Retrieved deleted records
     */
    protected function getDeletedPages($pageUid, $depth = 0): array
    {
        $deletedRecords = GeneralUtility::makeInstance(DeletedRecords::class);
        $deletedRecords->loadData($pageUid, 'pages', $depth);
        return $deletedRecords->getDeletedRows();
    }
}
