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

namespace TYPO3\CMS\Core\Tests\Functional\Resource\Search;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Resource\Search\FileSearchQuery;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileSearchQueryTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function countMatchesNumberOfFoundFilesWhenIdentifierExistsInMultipleStorages(): void
    {
        $connection = $this->getConnectionPool()->getConnectionForTable('sys_file');
        foreach ([[1, 1], [2, 2], [3, 1]] as [$uid, $storage]) {
            $identifier = $uid === 3 ? '/unique-needle.txt' : '/needle.txt';
            $connection->insert('sys_file', [
                'uid' => $uid,
                'storage' => $storage,
                'identifier' => $identifier,
                'identifier_hash' => sha1($identifier),
                'folder_hash' => sha1('/'),
                'name' => basename($identifier),
                'missing' => 0,
            ]);
            $this->getConnectionPool()->getConnectionForTable('sys_file_metadata')->insert('sys_file_metadata', [
                'uid' => $uid,
                'file' => $uid,
            ]);
        }
        $demand = FileSearchDemand::createForSearchTerm('needle');

        $count = (int)FileSearchQuery::createCountForSearchDemand($demand)->execute()->fetchOne();
        $found = FileSearchQuery::createForSearchDemand($demand)->execute()->fetchAllAssociative();

        self::assertCount(3, $found);
        self::assertSame(count($found), $count);
    }
}
