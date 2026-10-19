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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * FormEngine does not submit a language field with a single choice. On a page outside any site, DataHandler has no
 * site to take a default language from, so the access check must see the value the record is stored with.
 */
final class NewRecordOutsideSiteTest extends FunctionalTestCase
{
    #[Test]
    public function editorCanCreateRecordWithoutLanguageFieldOnPageOutsideSite(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/NewRecordOutsideSite.csv');

        [$errors, $record] = $this->createContentElementAsUser(2);

        self::assertSame([], $errors);
        self::assertSame(0, (int)$record['sys_language_uid']);
    }

    #[Test]
    public function configuredDefaultLanguageIsKept(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/NewRecordOutsideSite.csv');

        [$errors, $record] = $this->createContentElementAsUser(3);

        self::assertSame([], $errors);
        self::assertSame(-1, (int)$record['sys_language_uid']);
    }

    #[Test]
    public function editorWithoutAccessToDefaultLanguageIsStillRejected(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/NewRecordOutsideSite.csv');

        [$errors, $record] = $this->createContentElementAsUser(4);

        self::assertCount(1, $errors);
        self::assertStringContainsString('Language was not allowed', $errors[0]);
        self::assertNull($record);
    }

    /**
     * @return array{0: array, 1: array|null}
     */
    private function createContentElementAsUser(int $userId): array
    {
        $backendUser = $this->setUpBackendUser($userId);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['tt_content' => ['NEW1' => ['pid' => 1, 'header' => 'Without language field']]], [], $backendUser);
        $dataHandler->process_datamap();

        $uid = (int)($dataHandler->substNEWwithIDs['NEW1'] ?? 0);
        $record = $uid > 0
            ? $this->get(ConnectionPool::class)->getConnectionForTable('tt_content')->select(['*'], 'tt_content', ['uid' => $uid])->fetchAssociative()
            : false;
        return [$dataHandler->errorLog, $record ?: null];
    }
}
