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

namespace TYPO3\CMS\Backend\Tests\Functional\RecordList;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Backend\RecordList\DownloadRecordList;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DownloadRecordListTest extends FunctionalTestCase
{
    private BackendUserAuthentication $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->user = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->user);
    }

    #[Test]
    public function downloadReturnsAListOfAllBackendUsers(): void
    {
        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest(new ServerRequest());
        $recordList->start(0, 'be_users', 0);
        $recordList->setFields['be_users'] = [
            'username',
            'realName',
            'email',
            'admin',
            'crdate',
        ];
        $subject = new DownloadRecordList($recordList, $this->get(TranslationConfigurationProvider::class), $this->get(TcaSchemaFactory::class));
        $headerRow = $subject->getHeaderRow($recordList->setFields['be_users']);
        $contentRows = $subject->getRecords('be_users', $recordList->setFields['be_users'], $this->user);
        $result = array_merge([$headerRow], $contentRows);
        self::assertEquals([
            [
                'username' => 'username',
                'email' => 'email',
                'realName' => 'realName',
                'admin' => 'admin',
                'crdate' => 'crdate',
            ],
            [
                'username' => 'admin',
                'email' => '',
                'realName' => '',
                'admin' => 'Yes',
                'crdate' => '2013-04-22 14:55',
            ],
        ], $this->prepareRecordsForDbCompatAssertions($result));
    }

    #[Test]
    public function downloadReturnsAListOfSubpages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages_download_record_list.csv');
        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest(new ServerRequest());
        $recordList->start(1, 'pages', 0);
        $recordList->setFields['pages'] = [
            'uid',
            'pid',
            'title',
            'sys_language_uid',
        ];
        $subject = new DownloadRecordList($recordList, $this->get(TranslationConfigurationProvider::class), $this->get(TcaSchemaFactory::class));
        $headerRow = $subject->getHeaderRow($recordList->setFields['pages']);
        // Get records with raw values
        $contentRows = $subject->getRecords('pages', $recordList->setFields['pages'], $this->user, false, true);
        $result = array_merge([$headerRow], $contentRows);
        self::assertEquals([
            [
                'uid' => 'uid',
                'pid' => 'pid',
                'title' => 'title',
                'sys_language_uid' => 'sys_language_uid',
            ],
            [
                'uid' => '2',
                'pid' => '1',
                'title' => 'Dummy 1-2',
                'sys_language_uid' => '0',
            ],
            [
                'uid' => '902',
                'pid' => '1',
                'title' => 'Attrappe 1-2',
                'sys_language_uid' => '1',
            ],
            [
                'uid' => '3',
                'pid' => '1',
                'title' => 'Dummy 1-3',
                'sys_language_uid' => '0',
            ],
            [
                'uid' => '903',
                'pid' => '1',
                'title' => 'Attrappe 1-3',
                'sys_language_uid' => '1',
            ],
            [
                'uid' => '4',
                'pid' => '1',
                'title' => 'Dummy 1-4',
                'sys_language_uid' => '0',
            ],
        ], $this->prepareRecordsForDbCompatAssertions($result));

        // Fetch the records again but now ensure translations are omitted
        $headerRow = $subject->getHeaderRow($recordList->setFields['pages']);
        $contentRows = $subject->getRecords('pages', $recordList->setFields['pages'], $this->user, true);
        $result = array_merge([$headerRow], $contentRows);
        self::assertEquals([
            [
                'uid' => 'uid',
                'pid' => 'pid',
                'title' => 'title',
                'sys_language_uid' => 'sys_language_uid',
            ],
            [
                'uid' => '2',
                'pid' => '1',
                'title' => 'Dummy 1-2',
                'sys_language_uid' => 'Default',
            ],
            [
                'uid' => '3',
                'pid' => '1',
                'title' => 'Dummy 1-3',
                'sys_language_uid' => 'Default',
            ],
            [
                'uid' => '4',
                'pid' => '1',
                'title' => 'Dummy 1-4',
                'sys_language_uid' => 'Default',
            ],
        ], $this->prepareRecordsForDbCompatAssertions($result));
    }

    #[Test]
    public function downloadReturnsRawValues(): void
    {
        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest(new ServerRequest());
        $recordList->start(0, 'be_users', 0);
        $recordList->setFields['be_users'] = [
            'username',
            'realName',
            'email',
            'admin',
            'crdate',
        ];
        $subject = new DownloadRecordList($recordList, $this->get(TranslationConfigurationProvider::class), $this->get(TcaSchemaFactory::class));
        $headerRow = $subject->getHeaderRow($recordList->setFields['be_users']);
        $contentRows = $subject->getRecords('be_users', $recordList->setFields['be_users'], $this->user, false, true);
        $result = array_merge([$headerRow], $contentRows);
        self::assertEquals([
            [
                'username' => 'username',
                'email' => 'email',
                'realName' => 'realName',
                'admin' => 'admin',
                'crdate' => 'crdate',
            ],
            [
                'username' => 'admin',
                'email' => '',
                'realName' => '',
                'admin' => '1',
                'crdate' => '1366642540',
            ],
        ], $this->prepareRecordsForDbCompatAssertions($result));
    }

    #[Test]
    public function downloadWithPresetReturnsRequestedData(): void
    {
        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest(new ServerRequest());
        $recordList->start(0, 'be_users', 0);
        $recordList->setFields['be_users'] = [
            'username',
            'realName',
            'email',
            'admin',
            'crdate',
        ];
        $recordList->modTSconfig['downloadPresets.']['be_users.']['10.'] = [
            'identifier' => '10',
            'label' => 'Preset 1',
            'columns' => 'username, email',
        ];
        $columnsToRender = $recordList->getColumnsToRender('be_users', false, '10');
        $subject = new DownloadRecordList($recordList, $this->get(TranslationConfigurationProvider::class), $this->get(TcaSchemaFactory::class));
        $headerRow = $subject->getHeaderRow($columnsToRender);
        $contentRows = $subject->getRecords('be_users', $columnsToRender, $this->user, false, true);
        $result = array_merge([$headerRow], $contentRows);
        self::assertEquals([
            [
                'username' => 'username',
                'email' => 'email',
            ],
            [
                'username' => 'admin',
                'email' => '',
            ],
        ], $this->prepareRecordsForDbCompatAssertions($result));
    }

    #[Test]
    public function downloadWithMissingPresetReturnsFallbackData(): void
    {
        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest(new ServerRequest());
        $recordList->start(0, 'be_users', 0);
        $recordList->setFields['be_users'] = [
            'username',
            'realName',
            'email',
            'admin',
            'crdate',
        ];
        $recordList->modTSconfig['downloadPresets.']['be_users.']['10.'] = [
            'label' => 'Preset 1',
            'columns' => 'username, email',
        ];
        $columnsToRender = $recordList->getColumnsToRender('be_users', false, 'Preset INVALID');
        $subject = new DownloadRecordList($recordList, $this->get(TranslationConfigurationProvider::class), $this->get(TcaSchemaFactory::class));
        $headerRow = $subject->getHeaderRow($columnsToRender);
        $contentRows = $subject->getRecords('be_users', $columnsToRender, $this->user, false, true);
        $result = array_merge([$headerRow], $contentRows);
        self::assertEquals([
            [
                'username' => 'username',
                'email' => 'email',
                'realName' => 'realName',
                'admin' => 'admin',
                'crdate' => 'crdate',
            ],
            [
                'username' => 'admin',
                'email' => '',
                'realName' => '',
                'admin' => '1',
                'crdate' => '1366642540',
            ],
        ], $this->prepareRecordsForDbCompatAssertions($result));
    }

    #[Test]
    public function downloadWithInvalidPresetReturnsFallbackData(): void
    {
        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest(new ServerRequest());
        $recordList->start(0, 'be_users', 0);
        $recordList->setFields['be_users'] = [
            'username',
            'realName',
            'email',
            'admin',
            'crdate',
        ];

        $recordList->modTSconfig['downloadPresets.']['be_users.']['10.'] = [
            'label' => 'Preset 1',
            'columns' => ['username, email'], // Array, but STRING is required
        ];
        $columnsToRender = $recordList->getColumnsToRender('be_users', false, 'Preset INVALID');
        $subject = new DownloadRecordList($recordList, $this->get(TranslationConfigurationProvider::class), $this->get(TcaSchemaFactory::class));
        $headerRow = $subject->getHeaderRow($columnsToRender);
        $contentRows = $subject->getRecords('be_users', $columnsToRender, $this->user, false, true);
        $result = array_merge([$headerRow], $contentRows);
        self::assertEquals([
            [
                'username' => 'username',
                'email' => 'email',
                'realName' => 'realName',
                'admin' => 'admin',
                'crdate' => 'crdate',
            ],
            [
                'username' => 'admin',
                'email' => '',
                'realName' => '',
                'admin' => '1',
                'crdate' => '1366642540',
            ],
        ], $this->prepareRecordsForDbCompatAssertions($result));

        $recordList->modTSconfig['downloadPresets.']['be_users.']['10.'] = [
            'title' => 'Preset 1', // wrong key, should be "label"
            'columns' => 'username, email',
        ];
        $columnsToRender = $recordList->getColumnsToRender('be_users', false, 'Preset 1');
        $subject = new DownloadRecordList($recordList, $this->get(TranslationConfigurationProvider::class), $this->get(TcaSchemaFactory::class));
        $headerRow = $subject->getHeaderRow($columnsToRender);
        $contentRows = $subject->getRecords('be_users', $columnsToRender, $this->user, false, true);
        $result = array_merge([$headerRow], $contentRows);
        self::assertEquals([
            [
                'username' => 'username',
                'email' => 'email',
                'realName' => 'realName',
                'admin' => 'admin',
                'crdate' => 'crdate',
            ],
            [
                'username' => 'admin',
                'email' => '',
                'realName' => '',
                'admin' => '1',
                'crdate' => '1366642540',
            ],
        ], $this->prepareRecordsForDbCompatAssertions($result));

        $recordList->modTSconfig['downloadPresets.']['be_users.']['10.'] = [
            'label' => 'Preset 1',
            'fields' => 'username, email', // Wrong key, should be "columns"
        ];
        $columnsToRender = $recordList->getColumnsToRender('be_users', false, 'Preset 1');
        $subject = new DownloadRecordList($recordList, $this->get(TranslationConfigurationProvider::class), $this->get(TcaSchemaFactory::class));
        $headerRow = $subject->getHeaderRow($columnsToRender);
        $contentRows = $subject->getRecords('be_users', $columnsToRender, $this->user, false, true);
        $result = array_merge([$headerRow], $contentRows);
        self::assertEquals([
            [
                'username' => 'username',
                'email' => 'email',
                'realName' => 'realName',
                'admin' => 'admin',
                'crdate' => 'crdate',
            ],
            [
                'username' => 'admin',
                'email' => '',
                'realName' => '',
                'admin' => '1',
                'crdate' => '1366642540',
            ],
        ], $this->prepareRecordsForDbCompatAssertions($result));
    }

    #[Test]
    public function downloadWithMissingPresetColumnNamesReturnsValidData(): void
    {
        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest(new ServerRequest());
        $recordList->start(0, 'be_users', 0);
        $recordList->setFields['be_users'] = [
            'username',
            'realName',
            'email',
            'admin',
            'crdate',
        ];
        $recordList->modTSconfig['downloadPresets.']['be_users.']['10.'] = [
            'label' => 'Preset 1',
            'columns' => 'username, email, some, invalid',
        ];
        $columnsToRender = $recordList->getColumnsToRender('be_users', false, md5('Preset 1' . 'usernameemailsomeinvalid'));
        $subject = new DownloadRecordList($recordList, $this->get(TranslationConfigurationProvider::class), $this->get(TcaSchemaFactory::class));
        $headerRow = $subject->getHeaderRow($columnsToRender);
        $contentRows = $subject->getRecords('be_users', $columnsToRender, $this->user, false, true);
        $result = array_merge([$headerRow], $contentRows);
        self::assertEquals([
            [
                'username' => 'username',
                'email' => 'email',
            ],
            [
                'username' => 'admin',
                'email' => '',
            ],
        ], $this->prepareRecordsForDbCompatAssertions($result));
    }

    #[Test]
    public function downloadWithPresetUsingNonAllowedColumnNamesReturnsOnlyAllowedData(): void
    {
        $recordList = $this->get(DatabaseRecordList::class);
        $recordList->setRequest(new ServerRequest());
        $recordList->start(0, 'be_users', 0);
        $recordList->setFields['be_users'] = [
            'username',
            'email',
        ];
        $recordList->modTSconfig['downloadPresets.']['be_users.']['10.'] = [
            'label' => 'Preset 1',
            'columns' => 'username, email, uid',
        ];
        // "uid" is not in list of setFields columns, and should thus not be returned.
        $columnsToRender = $recordList->getColumnsToRender('be_users', false, 'Preset 1');
        $subject = new DownloadRecordList($recordList, $this->get(TranslationConfigurationProvider::class), $this->get(TcaSchemaFactory::class));
        $headerRow = $subject->getHeaderRow($columnsToRender);
        $contentRows = $subject->getRecords('be_users', $columnsToRender, $this->user, false, true);
        $result = array_merge([$headerRow], $contentRows);
        self::assertEquals([
            [
                'username' => 'username',
                'email' => 'email',
            ],
            [
                'username' => 'admin',
                'email' => '',
            ],
        ], $this->prepareRecordsForDbCompatAssertions($result));
    }

    /**
     * postgres is returning int fields as pure integers, others use strings.
     * In order to have our tests reliable, we cast everything to string.
     */
    protected function prepareRecordsForDbCompatAssertions(array $records): array
    {
        foreach ($records as &$record) {
            $record = array_map(strval(...), $record);
        }
        return $records;
    }
}
