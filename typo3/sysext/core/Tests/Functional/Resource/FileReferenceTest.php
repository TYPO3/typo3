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

namespace TYPO3\CMS\Core\Tests\Functional\Resource;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FileReferenceTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FileReference.csv');
    }

    #[Test]
    public function fileReferenceCanBeDeleted(): void
    {
        $fileReference = GeneralUtility::makeInstance(FileReference::class, ['uid' => 1, 'uid_local' => 1]);
        self::assertTrue($fileReference->delete());

        // Ensure file reference is really deleted in table
        $row = (new ConnectionPool())
            ->getConnectionForTable('sys_file_reference')
            ->select(
                ['uid'],
                'sys_file_reference',
                ['uid' => 1]
            )
            ->fetchAssociative();
        self::assertFalse($row);
    }
}
