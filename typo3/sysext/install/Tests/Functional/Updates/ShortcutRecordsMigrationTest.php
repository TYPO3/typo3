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

namespace TYPO3\CMS\Install\Tests\Functional\Updates;

use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Types\StringType;
use Doctrine\DBAL\Types\TextType;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Schema\TableDiff;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\ShortcutRecordsMigration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ShortcutRecordsMigrationTest extends FunctionalTestCase
{
    private const TABLE_NAME = 'sys_be_shortcuts';

    /**
     * Require additional core extensions so the routes
     * of the modules in the fixture are available.
     *
     * @var string[]
     */
    protected $coreExtensionsToLoad = ['beuser', 'filelist', 'form', 'info', 'lowlevel'];

    protected string $baseDataSet = 'typo3/sysext/install/Tests/Functional/Updates/Fixtures/ShortcutsBase.csv';
    protected string $resultDataSet = 'typo3/sysext/install/Tests/Functional/Updates/Fixtures/ShortcutsMigratedToRoutes.csv';

    /**
     * @test
     */
    public function shortcutRecordsUpdated(): void
    {
        $subject = new ShortcutRecordsMigration();

        $schemaManager = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable(self::TABLE_NAME)
            ->createSchemaManager();

        $schemaManager->alterTable(
            new TableDiff(
                self::TABLE_NAME,
                [
                    new Column('module_name', new StringType(), ['length' => 255, 'default' => '']),
                    new Column('url', new TextType(), ['notnull' => false]),
                ]
            )
        );

        $this->importCSVDataSet(GeneralUtility::getFileAbsFileName($this->baseDataSet));
        self::assertTrue($subject->updateNecessary());
        $subject->executeUpdate();
        self::assertFalse($subject->updateNecessary());
        $this->assertCSVDataSet(GeneralUtility::getFileAbsFileName($this->resultDataSet));

        // Just ensure that running the upgrade again does not change anything
        $subject->executeUpdate();
        $this->assertCSVDataSet(GeneralUtility::getFileAbsFileName($this->resultDataSet));
    }
}
