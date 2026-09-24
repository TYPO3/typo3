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

namespace TYPO3\CMS\IndexedSearch\Tests\Functional\Hook;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\IndexedSearch\Hook\DeleteIndexedData;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DeleteIndexedDataTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'indexed_search',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'indexed_search' => [
                'deleteFromIndexAfterEditing' => '1',
            ],
        ],
    ];

    #[Test]
    public function deleteRemovesIndexedPagesOfEditedPage(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('index_phash');
        $connection->insert('index_phash', ['phash' => 'phash-of-page-5', 'phash_grouping' => 'grouping', 'data_page_id' => 5, 'contentHash' => 'hash']);
        $connection->insert('index_phash', ['phash' => 'phash-of-page-6', 'phash_grouping' => 'grouping', 'data_page_id' => 6, 'contentHash' => 'hash']);

        $sectionConnection = $this->get(ConnectionPool::class)->getConnectionForTable('index_section');
        $sectionConnection->insert('index_section', ['phash' => 'phash-of-page-5', 'phash_t3' => 'phash-of-page-5', 'page_id' => 5]);
        $sectionConnection->insert('index_section', ['phash' => 'phash-of-page-6', 'phash_t3' => 'phash-of-page-6', 'page_id' => 6]);

        // The hook is resolved like DataHandler does it
        GeneralUtility::makeInstance(DeleteIndexedData::class)->delete(['uid_page' => 5]);

        $remaining = $connection->select(['phash'], 'index_phash')->fetchFirstColumn();
        self::assertSame(['phash-of-page-6'], $remaining);
    }
}
