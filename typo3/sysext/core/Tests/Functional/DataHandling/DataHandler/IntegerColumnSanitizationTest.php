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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * A relation field ("group" with maxitems=1 and a single allowed table) is commonly stored in an
 * integer database column. Creating a workspace version recalculates the relation, which yields an
 * empty string when no relation is set. That empty string must not end up as a value for an integer
 * column: MariaDB / MySQL in strict mode reject it with "Incorrect integer value: '' for column ...",
 * and it is written verbatim into the sys_history payload.
 */
final class IntegerColumnSanitizationTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler',
    ];

    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/IntegerColumnSanitization.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $backendUser->workspace = 1;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(1));
    }

    #[Test]
    public function emptyStringIsTurnedIntoZeroForIntegerColumns(): void
    {
        $result = $this->get(DataHandler::class)->insertUpdateDB_preprocessBasedOnFieldType(
            'tt_content',
            ['tx_testdatahandler_group_int' => '']
        );
        self::assertSame(['tx_testdatahandler_group_int' => 0], $result);
    }

    #[Test]
    public function emptyStringIsKeptForNonIntegerColumns(): void
    {
        $result = $this->get(DataHandler::class)->insertUpdateDB_preprocessBasedOnFieldType(
            'tt_content',
            ['header' => '', 'tx_testdatahandler_group' => '']
        );
        self::assertSame(['header' => '', 'tx_testdatahandler_group' => ''], $result);
    }

    #[Test]
    public function creatingWorkspaceVersionKeepsAnEmptyRelationFieldAnInteger(): void
    {
        $versionRow = $this->createWorkspaceVersion(1);
        self::assertSame(0, (int)$versionRow['tx_testdatahandler_group_int']);
        self::assertSame(0, $this->getHistoryPayloadOfVersion((int)$versionRow['uid'])['tx_testdatahandler_group_int']);
    }

    #[Test]
    public function creatingWorkspaceVersionKeepsAnExistingRelation(): void
    {
        $versionRow = $this->createWorkspaceVersion(2);
        self::assertSame(1, (int)$versionRow['tx_testdatahandler_group_int']);
    }

    private function createWorkspaceVersion(int $liveId): array
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['tt_content' => [$liveId => ['header' => 'CE changed']]], []);
        $dataHandler->process_datamap();
        self::assertSame([], $dataHandler->errorLog);

        $row = $this->getConnectionPool()
            ->getConnectionForTable('tt_content')
            ->executeQuery('SELECT * FROM tt_content WHERE t3ver_oid = ? AND t3ver_wsid = 1', [$liveId])
            ->fetchAssociative();
        self::assertIsArray($row, 'No workspace version has been created');
        return $row;
    }

    private function getHistoryPayloadOfVersion(int $versionId): array
    {
        $historyData = $this->getConnectionPool()
            ->getConnectionForTable('sys_history')
            ->executeQuery(
                'SELECT history_data FROM sys_history WHERE tablename = ? AND recuid = ? ORDER BY uid ASC',
                ['tt_content', $versionId]
            )
            ->fetchOne();
        self::assertNotFalse($historyData, 'No history entry has been written');
        return json_decode($historyData, true);
    }
}
