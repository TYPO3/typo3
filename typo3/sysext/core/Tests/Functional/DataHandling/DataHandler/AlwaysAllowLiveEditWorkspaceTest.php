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
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AlwaysAllowLiveEditWorkspaceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        // Set after TCA migration, which removes this combination
        $GLOBALS['TCA']['tt_content']['ctrl']['versioningWS_alwaysAllowLiveEdit'] = true;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->importCSVDataSet(dirname(__DIR__, 2) . '/Fixtures/be_users_admin.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/AlwaysAllowLiveEditWorkspace.csv');
        $backendUser = $this->setUpBackendUser(1);
        $backendUser->setWorkspace(1);
    }

    #[Test]
    public function newRecordOfWorkspaceAwareTableIsCreatedInWorkspace(): void
    {
        $this->processDataMap(['tt_content' => ['NEW1' => ['pid' => 1, 'header' => 'New content', 'CType' => 'text']]]);

        self::assertSame([], $this->fetchContentRows('New content', 0), 'Record has been created in live workspace');
        self::assertCount(1, $this->fetchContentRows('New content', 1));
    }

    #[Test]
    public function modifyingLiveRecordOfWorkspaceAwareTableCreatesWorkspaceVersion(): void
    {
        $this->processDataMap(['tt_content' => [1 => ['header' => 'Modified content']]]);

        self::assertSame([], $this->fetchContentRows('Modified content', 0), 'Live record has been modified directly');
        self::assertCount(1, $this->fetchContentRows('Live content', 0));
        self::assertCount(1, $this->fetchContentRows('Modified content', 1));
    }

    private function processDataMap(array $dataMap): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start($dataMap, []);
        $dataHandler->process_datamap();
        self::assertSame([], $dataHandler->errorLog);
    }

    private function fetchContentRows(string $header, int $workspaceId): array
    {
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        return $queryBuilder
            ->select('uid', 't3ver_wsid', 't3ver_oid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('header', $queryBuilder->createNamedParameter($header)),
                $queryBuilder->expr()->eq('t3ver_wsid', $queryBuilder->createNamedParameter($workspaceId, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
