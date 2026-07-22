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

namespace TYPO3\CMS\Core\Tests\Functional\Upgrades;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Upgrades\DatabaseRowsUpdateWizard;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DatabaseRowsUpdateWizardTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_rowupdater',
    ];

    #[Test]
    public function rowUpdaterChangesAreWrittenToDatabase(): void
    {
        $this->getConnectionPool()->getConnectionForTable('tt_content')
            ->insert('tt_content', ['uid' => 1, 'pid' => 0, 'header' => 'Original']);

        $subject = $this->get(DatabaseRowsUpdateWizard::class);
        self::assertTrue($subject->updateNecessary());
        $subject->executeUpdate();
        self::assertFalse($subject->updateNecessary());

        self::assertSame('Updated by row updater', $this->getAllRecords('tt_content')[0]['header']);
    }

    #[Test]
    public function rowOfTableWithSoftDeleteCapabilityIsUpdated(): void
    {
        $this->getConnectionPool()->getConnectionForTable('pages')
            ->insert('pages', ['uid' => 1, 'pid' => 0, 'title' => 'Original', 'deleted' => 0]);

        $this->get(DatabaseRowsUpdateWizard::class)->executeUpdate();

        $rows = $this->getAllRecords('pages');
        self::assertCount(1, $rows);
        self::assertSame(1, (int)$rows[0]['deleted']);
    }

    #[Test]
    public function rowOfTableWithoutSoftDeleteCapabilityIsHardDeleted(): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_file')->insert('sys_file', [
            'uid' => 1,
            'pid' => 0,
            'storage' => 0,
            'identifier' => '/test.txt',
            'identifier_hash' => 'abc',
            'folder_hash' => 'def',
            'extension' => 'txt',
            'name' => 'test.txt',
        ]);

        $this->get(DatabaseRowsUpdateWizard::class)->executeUpdate();

        self::assertCount(0, $this->getAllRecords('sys_file'));
    }
}
