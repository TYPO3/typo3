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

namespace TYPO3\CMS\Linkvalidator\Tests\Functional\Linktype;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\CMS\Linkvalidator\Linktype\RecordLinktype;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RecordLinktypeTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = [
        'linkvalidator',
        'info',
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/linkvalidator/Tests/Functional/Linktype/Fixtures/Extensions/record_linkhandler_example',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../../../core/Tests/Functional/Fixtures/be_users_admin.csv');
        $GLOBALS['BE_USER'] = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);
        $GLOBALS['EXEC_TIME'] = time();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tx_record_linkhandler_example.csv');
    }

    public static function checkLinkReturnsCorrectValueDataProvider(): array
    {
        return [
            'record does not exist' => [999, true, false, RecordLinktype::NOTEXISTING],
            'record exists' => [1, true, true, null],
            'record is hidden' => [2, true, false, RecordLinktype::HIDDEN],
            'record is hidden, reportHiddenRecords disabled' => [2, false, true, null],
            'record is hidden, reportHiddenRecords not set' => [2, null, false, RecordLinktype::HIDDEN],
            'record is deleted' => [3, true, false, RecordLinktype::DELETED],
            'record is deleted, reportHiddenRecords disabled' => [3, false, false, RecordLinktype::DELETED],
            'record starttime is in the future' => [4, true, false, RecordLinktype::HIDDEN],
            'record starttime is in the future, reportHiddenRecords disabled' => [4, false, true, null],
            'record endtime is in the past' => [5, true, false, RecordLinktype::HIDDEN],
            'record is within starttime and endtime' => [6, true, true, null],
        ];
    }

    #[DataProvider('checkLinkReturnsCorrectValueDataProvider')]
    #[Test]
    public function checkLinkReturnsCorrectValue(int $uid, ?bool $reportHiddenRecords, bool $expectedResult, ?string $expectedErrorType): void
    {
        $url = 't3://record?identifier=tx_myrecord&uid=' . $uid;
        $softRefEntry = [
            'substr' => [
                'type' => 'record',
                'recordRef' => 'tx_record_linkhandler_example:' . $uid,
                'tokenID' => '7e11f87a247121b76d698fd989119e54',
                'tokenValue' => $url,
            ],
            'row' => ['uid' => 1, 'pid' => 1, 'header_link' => $url],
            'table' => 'tt_content',
            'field' => 'header_link',
            'uid' => 1,
        ];
        $subject = $this->get(RecordLinktype::class);

        $result = $subject->checkLink($url, $softRefEntry, $this->initializeLinkAnalyzer($reportHiddenRecords));

        self::assertSame($expectedResult, $result);
        self::assertSame($expectedErrorType, $subject->getErrorParams()['errorType'] ?? null);
    }

    #[Test]
    public function getLinkStatisticsFindsBrokenRecordLinksInRteAndLinkFields(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/tt_content_with_record_links.csv');
        $tsConfig = [
            'searchFields' => ['tt_content' => ['bodytext', 'header_link']],
            'linktypes' => 'record',
            'checkhidden' => '0',
        ];
        $linkAnalyzer = $this->get(LinkAnalyzer::class);
        $linkAnalyzer->init($tsConfig['searchFields'], [1], $tsConfig);

        $linkAnalyzer->getLinkStatistics(['record']);

        $rows = $this->getConnectionPool()->getConnectionForTable('tx_linkvalidator_link')
            ->select(['record_uid', 'field', 'link_type', 'url_response'], 'tx_linkvalidator_link', [], [], ['record_uid' => 'ASC'])
            ->fetchAllAssociative();
        $rows = array_map(
            static fn(array $row): array => [
                'record_uid' => $row['record_uid'],
                'field' => $row['field'],
                'link_type' => $row['link_type'],
                'errorType' => json_decode($row['url_response'], true)['errorParams']['errorType'] ?? null,
            ],
            $rows
        );
        self::assertSame(
            [
                ['record_uid' => 1, 'field' => 'bodytext', 'link_type' => 'record', 'errorType' => RecordLinktype::DELETED],
                ['record_uid' => 2, 'field' => 'header_link', 'link_type' => 'record', 'errorType' => RecordLinktype::NOTEXISTING],
            ],
            $rows
        );
    }

    #[Test]
    public function checkLinkReportsRecordAsHiddenWhenEndtimeIsReached(): void
    {
        $url = 't3://record?identifier=tx_myrecord&uid=5';
        $softRefEntry = ['substr' => ['type' => 'record', 'recordRef' => 'tx_record_linkhandler_example:5', 'tokenValue' => $url]];
        $subject = $this->get(RecordLinktype::class);
        // Same as the endtime of record 5, which is not accessible anymore at that time
        $GLOBALS['EXEC_TIME'] = 946684800;

        self::assertFalse($subject->checkLink($url, $softRefEntry, $this->initializeLinkAnalyzer(true)));
        self::assertSame(RecordLinktype::HIDDEN, $subject->getErrorParams()['errorType']);
    }

    public static function checkLinkHandlesTableWithoutDeleteAndEnableFieldsDataProvider(): array
    {
        return [
            'record exists' => [1, true, null],
            'record does not exist' => [2, false, RecordLinktype::NOTEXISTING],
        ];
    }

    #[DataProvider('checkLinkHandlesTableWithoutDeleteAndEnableFieldsDataProvider')]
    #[Test]
    public function checkLinkHandlesTableWithoutDeleteAndEnableFields(int $uid, bool $expectedResult, ?string $expectedErrorType): void
    {
        $url = 't3://record?identifier=tx_record_linkhandler_plain&uid=' . $uid;
        $softRefEntry = ['substr' => ['type' => 'record', 'recordRef' => 'tx_record_linkhandler_plain:' . $uid, 'tokenValue' => $url]];
        $subject = $this->get(RecordLinktype::class);

        self::assertSame($expectedResult, $subject->checkLink($url, $softRefEntry, $this->initializeLinkAnalyzer(true)));
        self::assertSame($expectedErrorType, $subject->getErrorParams()['errorType'] ?? null);
    }

    #[Test]
    public function checkLinkReportsMissingTable(): void
    {
        $url = 't3://record?identifier=unknown&uid=1';
        $softRefEntry = ['substr' => ['type' => 'record', 'recordRef' => 'tx_unknown_table:1', 'tokenValue' => $url]];
        $subject = $this->get(RecordLinktype::class);

        self::assertFalse($subject->checkLink($url, $softRefEntry, $this->initializeLinkAnalyzer(true)));
        self::assertSame(RecordLinktype::TABLENOTEXISTING, $subject->getErrorParams()['errorType']);
        self::assertSame(
            'Table or link identifier \'tx_unknown_table\' of the linked record (id=1) could not be resolved.',
            $subject->getErrorMessage($subject->getErrorParams())
        );
    }

    #[Test]
    public function getErrorMessageRendersTitleAndUid(): void
    {
        $subject = $this->get(RecordLinktype::class);
        $url = 't3://record?identifier=tx_myrecord&uid=2';
        $softRefEntry = ['substr' => ['type' => 'record', 'recordRef' => 'tx_record_linkhandler_example:2', 'tokenValue' => $url]];
        $subject->checkLink($url, $softRefEntry, $this->initializeLinkAnalyzer(true));

        // The title respects the full label configuration of the table, here "label_alt_force"
        self::assertSame(
            'Record \'record 2, hidden\' (id=2) is not visible.',
            $subject->getErrorMessage($subject->getErrorParams())
        );
    }

    #[Test]
    public function fetchTypeClaimsRecordLinks(): void
    {
        $subject = $this->get(RecordLinktype::class);

        self::assertSame('record', $subject->fetchType(['type' => 'db', 'tokenValue' => 't3://record?identifier=tx_news&uid=1'], '', 'record'));
        self::assertSame('', $subject->fetchType(['type' => 'db', 'tokenValue' => 't3://page?uid=1'], '', 'record'));
    }

    private function initializeLinkAnalyzer(?bool $reportHiddenRecords): LinkAnalyzer
    {
        $tsConfig = [
            'searchFields' => ['tt_content' => ['header_link']],
            'linktypes' => 'record',
            'checkhidden' => '0',
        ];
        if ($reportHiddenRecords !== null) {
            $tsConfig['reportHiddenRecords'] = $reportHiddenRecords ? '1' : '0';
        }
        $linkAnalyzer = $this->get(LinkAnalyzer::class);
        $linkAnalyzer->init($tsConfig['searchFields'], [1], $tsConfig);
        return $linkAnalyzer;
    }
}
