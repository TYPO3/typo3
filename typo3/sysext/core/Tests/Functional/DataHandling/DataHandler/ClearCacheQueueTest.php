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
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ClearCacheQueueTest extends FunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'pages' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/ClearCacheQueue/PagesWithContent.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    public static function invalidQueueEntryDataProvider(): array
    {
        return [
            'table without TCA' => ['tx_table_without_tca', 1],
            'record uid zero' => ['tt_content', 0],
        ];
    }

    #[Test]
    #[DataProvider('invalidQueueEntryDataProvider')]
    public function invalidQueueEntryDoesNotPreventCacheFlushOfOtherRecords(string $table, int $uid): void
    {
        $pageCache = $this->getPageCacheWithEntryOfPageTwo();

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->registerRecordIdForPageCacheClearing($table, $uid);
        $dataHandler->start(['tt_content' => [1 => ['header' => 'Changed content']]], []);
        $dataHandler->process_datamap();

        self::assertFalse($pageCache->has('page-two'));
    }

    #[Test]
    #[DataProvider('invalidQueueEntryDataProvider')]
    public function invalidQueueEntryDoesNotPreventCacheFlushOfSubsequentDataHandlerRun(string $table, int $uid): void
    {
        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->registerRecordIdForPageCacheClearing($table, $uid);
        $dataHandler->start(['tt_content' => [1 => ['header' => 'Changed content']]], []);
        $dataHandler->process_datamap();

        $pageCache = $this->getPageCacheWithEntryOfPageTwo();

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['tt_content' => [2 => ['header' => 'Changed content']]], []);
        $dataHandler->process_datamap();

        self::assertFalse($pageCache->has('page-two'));
    }

    private function getPageCacheWithEntryOfPageTwo(): FrontendInterface
    {
        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        $pageCache->set('page-two', ['dummy'], ['pageId_2']);
        self::assertTrue($pageCache->has('page-two'));
        return $pageCache;
    }
}
