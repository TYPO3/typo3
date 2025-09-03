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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\DataHandling\Event\BeforeRemoveNonCopyableFieldsEvent;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Tests related to DataHandler copyRecord functionality
 */
final class CopyRecordTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/CopyRecord/Pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function pageRecordIsCopiedTest(): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->enableLogging = false;

        $cmd['pages'][2]['copy'] = 1;
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        $this->assertCSVDataSet(__DIR__ . '/DataSet/CopyRecord/PageRecordIsCopiedResult.csv');
    }

    #[Test]
    public function copyOfMultipleRecordsAppliesUpdateValuesToAllCopies(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/CopyRecord/ContentLanguages.csv');
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->enableLogging = false;

        $cmd['tt_content'][10]['copy'] = ['action' => 'paste', 'target' => 2, 'update' => ['colPos' => 3]];
        $cmd['tt_content'][11]['copy'] = ['action' => 'paste', 'target' => 2, 'update' => ['colPos' => 3]];
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        foreach ([10, 11] as $sourceUid) {
            $copyUid = $dataHandler->copyMappingArray_merged['tt_content'][$sourceUid] ?? null;
            self::assertIsInt($copyUid);
            $copy = BackendUtility::getRecord('tt_content', $copyUid);
            self::assertSame(3, (int)$copy['colPos'], 'Copy of tt_content:' . $sourceUid);
        }
    }

    #[Test]
    public function copyIsDeniedIfDoktypeOfTargetPageDoesNotAllowTable(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/CopyRecord/DoktypeRestriction.csv');
        $this->restrictSysFolderRecordTypes();
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->enableLogging = false;

        $cmd['tt_content'][110]['copy'] = 101;
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        self::assertArrayNotHasKey(110, $dataHandler->copyMappingArray_merged['tt_content'] ?? []);
    }

    public static function copyIsAllowedIfOnlyDoktypeOfSourcePageDoesNotAllowTableDataProvider(): array
    {
        return [
            'page with restrictive doktype onto standard page' => ['pages', 101, 100, 100],
            'content from page with restrictive doktype onto standard page' => ['tt_content', 120, 100, 100],
        ];
    }

    #[Test]
    #[DataProvider('copyIsAllowedIfOnlyDoktypeOfSourcePageDoesNotAllowTableDataProvider')]
    public function copyIsAllowedIfOnlyDoktypeOfSourcePageDoesNotAllowTable(string $table, int $uid, int $destination, int $expectedPid): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/CopyRecord/DoktypeRestriction.csv');
        $this->restrictSysFolderRecordTypes();
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->enableLogging = false;

        $cmd[$table][$uid]['copy'] = $destination;
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        $copyUid = $dataHandler->copyMappingArray_merged[$table][$uid] ?? null;
        self::assertIsInt($copyUid);
        self::assertSame($expectedPid, (int)BackendUtility::getRecord($table, $copyUid)['pid']);
    }

    #[Test]
    public function beforeRemoveNonCopyableFieldsEventIsCalledAndNonCopyableFieldsAreRespected(): void
    {
        $beforeRemoveNonCopyableFieldsEvent = null;
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'before-remove-non-copyable-fields-event',
            static function (BeforeRemoveNonCopyableFieldsEvent $event) use (&$beforeRemoveNonCopyableFieldsEvent) {
                $beforeRemoveNonCopyableFieldsEvent = $event;
                $nonCopyableFields = $event->getNonCopyableFields();
                $nonCopyableFields[] = 'description';
                $event->setNonCopyableFields($nonCopyableFields);
            }
        );
        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(BeforeRemoveNonCopyableFieldsEvent::class, 'before-remove-non-copyable-fields-event');

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->enableLogging = false;

        $cmd['pages'][2]['copy'] = 1;
        $dataHandler->start([], $cmd);
        $dataHandler->process_cmdmap();

        self::assertInstanceOf(BeforeRemoveNonCopyableFieldsEvent::class, $beforeRemoveNonCopyableFieldsEvent);
        $this->assertCSVDataSet(__DIR__ . '/DataSet/CopyRecord/PageRecordIsCopiedWithoutDescriptionFieldResult.csv');
    }

    public static function copiedPageIsSortedByDestinationWithSortByColumnInTcaDataProvider(): array
    {
        return [
            'after another page' => [
                -2,
                ['Dummy 1-2', 'Dummy 1-4 (copy 1)', 'Dummy 1-3', 'Dummy 1-4'],
            ],
            'after the last page' => [
                -4,
                ['Dummy 1-2', 'Dummy 1-3', 'Dummy 1-4', 'Dummy 1-4 (copy 1)'],
            ],
            'after itself' => [
                -3,
                ['Dummy 1-2', 'Dummy 1-3', 'Dummy 1-3 (copy 1)', 'Dummy 1-4'],
                3,
            ],
            'into a page' => [
                1,
                ['Dummy 1-4 (copy 1)', 'Dummy 1-2', 'Dummy 1-3', 'Dummy 1-4'],
            ],
        ];
    }

    #[Test]
    #[DataProvider('copiedPageIsSortedByDestinationWithSortByColumnInTcaDataProvider')]
    public function copiedPageIsSortedByDestinationWithSortByColumnInTca(int $destination, array $expectedTitles, int $sourceUid = 4): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/CopyRecord/PageSiblings.csv');
        $sortBy = $GLOBALS['TCA']['pages']['ctrl']['sortby'];
        $GLOBALS['TCA']['pages']['columns'][$sortBy]['config']['type'] = 'passthrough';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['pages' => [$sourceUid => ['copy' => $destination]]]);
        $dataHandler->process_cmdmap();

        self::assertSame([], $dataHandler->errorLog);
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $titles = $queryBuilder->select('title')->from('pages')
            ->where($queryBuilder->expr()->eq('pid', 1))
            ->orderBy($sortBy)
            ->executeQuery()
            ->fetchFirstColumn();
        self::assertSame($expectedTitles, $titles);
    }

    private function restrictSysFolderRecordTypes(): void
    {
        $GLOBALS['TCA']['pages']['types']['1']['allowedRecordTypes'] = ['*'];
        $GLOBALS['TCA']['pages']['types']['254']['allowedRecordTypes'] = ['sys_category'];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
    }
}
