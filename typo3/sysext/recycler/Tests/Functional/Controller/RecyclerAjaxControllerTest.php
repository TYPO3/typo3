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

namespace TYPO3\CMS\Recycler\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Recycler\Controller\RecyclerAjaxController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * @phpstan-type DatabaseState array{regular: list<int>, softDeleted: list<int>}
 */
final class RecyclerAjaxControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'recycler',
    ];

    private RecyclerAjaxController $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(dirname(__DIR__) . '/Fixtures/Database/be_groups.csv');
        $this->importCSVDataSet(dirname(__DIR__) . '/Fixtures/Database/be_users.csv');
        $this->importCSVDataSet(dirname(__DIR__) . '/Fixtures/Database/pages.csv');

        $this->subject = $this->get(RecyclerAjaxController::class);
    }

    #[Test]
    public function dispatchWithDeleteRecordsActionDoesNothingIfUserLacksDeletionPermissions(): void
    {
        $this->setUpBackendUserAndLanguageService(2);

        $request = new ServerRequest('https://typo3-testing.local', 'POST');
        $request = $request->withParsedBody(['action' => 'deleteRecords']);

        $expected = [
            'success' => false,
            'message' => LocalizationUtility::translate('flashmessage.delete.unauthorized', 'recycler'),
        ];

        $actual = $this->subject->dispatch($request);

        self::assertInstanceOf(JsonResponse::class, $actual);
        self::assertJsonStringEqualsJsonString(
            \json_encode($expected, JSON_THROW_ON_ERROR),
            (string)$actual->getBody(),
        );
    }

    /**
     * @return \Generator<string, array{int}>
     */
    public static function dispatchWithDeleteRecordsActionDoesNothingIfNoRecordsAreProvidedInRequestDataProvider(): \Generator
    {
        yield 'admin' => [1];
        yield 'permitted editor with TSconfig' => [3];
    }

    #[DataProvider('dispatchWithDeleteRecordsActionDoesNothingIfNoRecordsAreProvidedInRequestDataProvider')]
    #[Test]
    public function dispatchWithDeleteRecordsActionDoesNothingIfNoRecordsAreProvidedInRequest(int $backendUser): void
    {
        $this->setUpBackendUserAndLanguageService($backendUser);

        $request = new ServerRequest('https://typo3-testing.local', 'POST');
        $request = $request->withParsedBody(['action' => 'deleteRecords']);

        $expected = [
            'success' => false,
            'message' => LocalizationUtility::translate('flashmessage.delete.norecordsselected', 'recycler'),
        ];

        $actual = $this->subject->dispatch($request);

        self::assertInstanceOf(JsonResponse::class, $actual);
        self::assertJsonStringEqualsJsonString(
            \json_encode($expected, JSON_THROW_ON_ERROR),
            (string)$actual->getBody(),
        );
    }

    public static function dispatchWithDeleteRecordsActionDeletesGivenRecordsWherePermissionsAreGivenDataProvider(): \Generator
    {
        yield 'admin' => [
            'backendUser' => 1,
            'records' => ['pages:4', 'pages:6', 'pages:7'],
            // @todo response is misleading, actually 4, 5 (subpage of 4), 6, 6 were delete (= 4 records)
            'expectedResponse' => ['success' => true, 'message' => '3 records were deleted.'],
            'expectedDatabaseState' => [
                'pages' => ['regular' => [1, 2], 'softDeleted' => [3]],
            ],
        ];

        yield 'editor with at least one record without permissions' => [
            'backendUser' => 3,
            'records' => [
                'pages:3',
                'pages:4',
                'pages:5', // subpage of 4 => already deleted when 4 is deleted
                'pages:6', // outside of configured mount points => no permission to delete
                'pages:7', // perms_everybody = 0 => no permission to delete
            ],
            'expectedResponse' => null,
            // @todo recycler does not return reliable counts
            // ['success' => false, 'message' => 'Could not delete 3 records.'],
            'expectedDatabaseState' => [
                'pages' => ['regular' => [1, 2], 'softDeleted' => [6, 7]],
            ],
        ];

        yield 'editor with permissions on all records' => [
            'backendUser' => 3,
            'records' => ['pages:3', 'pages:4'],
            // @todo response is misleading, actually 3, 4, 5 (subpage of 4) were delete (= 3 records)
            'expectedResponse' => ['success' => true, 'message' => '2 records were deleted.'],
            'expectedDatabaseState' => [
                'pages' => ['regular' => [1, 2], 'softDeleted' => [6, 7]],
            ],
        ];
    }

    /**
     * @param list<string> $records
     * @param array{success: bool, message: string} $expectedResponse
     * @param array<string, DatabaseState> $expectedDatabaseState
     */
    #[DataProvider('dispatchWithDeleteRecordsActionDeletesGivenRecordsWherePermissionsAreGivenDataProvider')]
    #[Test]
    public function dispatchWithDeleteRecordsActionDeletesGivenRecordsWherePermissionsAreGiven(
        int $backendUser,
        array $records,
        ?array $expectedResponse,
        array $expectedDatabaseState = [],
    ): void {
        $this->setUpBackendUserAndLanguageService($backendUser);

        $request = new ServerRequest('https://typo3-testing.local', 'POST');
        $request = $request->withParsedBody([
            'action' => 'deleteRecords',
            'records' => $records,
        ]);

        $actual = $this->subject->dispatch($request);

        self::assertInstanceOf(JsonResponse::class, $actual);
        if ($expectedResponse !== null) {
            self::assertJsonStringEqualsJsonString(
                \json_encode($expectedResponse, JSON_THROW_ON_ERROR),
                (string)$actual->getBody(),
            );
        }
        foreach ($expectedDatabaseState as $tableName => $expectedDatabaseStateForTable) {
            $actualDatabaseState = $this->fetchDatabaseState($tableName);
            self::assertSame($expectedDatabaseStateForTable, $actualDatabaseState);
        }
    }

    /**
     * @return DatabaseState
     */
    private function fetchDatabaseState(string $tableName): array
    {
        $result = ['regular' => []];

        $softDeleteFieldName = $GLOBALS['TCA'][$tableName]['ctrl']['delete'] ?? null;

        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll();

        $selectFields = ['uid'];
        if ($softDeleteFieldName) {
            $selectFields[] = $softDeleteFieldName;
            $result['softDeleted'] = [];
        }

        $rows = $queryBuilder
            ->select(...$selectFields)
            ->from($tableName)
            ->executeQuery()
            ->fetchAllAssociative();

        foreach ($rows as $row) {
            $id = $row['uid'];
            $key = empty($row[$softDeleteFieldName]) ? 'regular' : 'softDeleted';
            $result[$key][] = $id;
        }
        return $result;
    }

    private function setUpBackendUserAndLanguageService(int $userId): void
    {
        $backendUser = $this->setUpBackendUser($userId);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }
}
