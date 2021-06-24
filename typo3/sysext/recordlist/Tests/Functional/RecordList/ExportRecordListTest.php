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

namespace TYPO3\CMS\Recordlist\Tests\Functional\RecordList;

use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;
use TYPO3\CMS\Recordlist\RecordList\ExportRecordList;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class ExportRecordListTest extends FunctionalTestCase
{
    private ?BackendUserAuthentication $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->setUpBackendUserFromFixture(1);
        $GLOBALS['LANG'] = $this->getContainer()->get(LanguageServiceFactory::class)->createFromUserPreferences($this->user);
    }

    /**
     * @test
     */
    public function exportReturnsAListOfAllBackendUsers(): void
    {
        $recordList = new DatabaseRecordList();
        $recordList->start(0, 'be_users', 0);
        $recordList->setFields['be_users'] = [
            'username',
            'realName',
            'email',
            'admin',
            'crdate'
        ];
        $subject = new ExportRecordList($recordList, new TranslationConfigurationProvider());
        $headerRow = $subject->getHeaderRow($recordList->setFields['be_users']);
        $contentRows = $subject->getRecords('be_users', 0, $recordList->setFields['be_users'], $this->user);
        $result = array_merge([$headerRow], $contentRows);
        self::assertEquals([
            [
                'username' => 'username',
                'email' => 'email',
                'realName' => 'realName',
                'admin' => 'admin',
                'crdate' => 'crdate'
            ],
            [
                'username' => 'admin',
                'email' => '',
                'realName' => '',
                'admin' => 'Yes',
                'crdate' => '22-04-13 14:55'
            ],
        ], $this->prepareRecordsForDbCompatAssertions($result));
    }

    /**
     * @test
     */
    public function exportReturnsAListOfSubpages(): void
    {
        $this->importDataSet('EXT:recordlist/Tests/Functional/RecordList/Fixtures/pages.xml');
        $recordList = new DatabaseRecordList();
        $recordList->start(1, 'pages', 0);
        $recordList->setFields['pages'] = [
            'uid',
            'pid',
            'title',
            'sys_language_uid',
        ];
        $subject = new ExportRecordList($recordList, new TranslationConfigurationProvider());
        $headerRow = $subject->getHeaderRow($recordList->setFields['pages']);
        $contentRows = $subject->getRecords('pages', 1, $recordList->setFields['pages'], $this->user);
        $result = array_merge([$headerRow], $contentRows);
        self::assertEquals([
            [
                'uid' => 'uid',
                'pid' => 'pid',
                'title' => 'title',
                'sys_language_uid' => 'sys_language_uid'
            ],
            [
                'uid' => '2',
                'pid' => '1',
                'title' => 'Dummy 1-2',
                'sys_language_uid' => '0'
            ],
            [
                'uid' => '902',
                'pid' => '1',
                'title' => 'Attrappe 1-2',
                'sys_language_uid' => '1'
            ],
            [
                'uid' => '3',
                'pid' => '1',
                'title' => 'Dummy 1-3',
                'sys_language_uid' => '0'
            ],
            [
                'uid' => '903',
                'pid' => '1',
                'title' => 'Attrappe 1-3',
                'sys_language_uid' => '1'
            ],
            [
                'uid' => '4',
                'pid' => '1',
                'title' => 'Dummy 1-4',
                'sys_language_uid' => '0'
            ]
        ], $this->prepareRecordsForDbCompatAssertions($result));

        // Fetch the records again but now ensure translations are omitted
        $headerRow = $subject->getHeaderRow($recordList->setFields['pages']);
        $contentRows = $subject->getRecords('pages', 1, $recordList->setFields['pages'], $this->user, true);
        $result = array_merge([$headerRow], $contentRows);
        self::assertEquals([
           [
               'uid' => 'uid',
               'pid' => 'pid',
               'title' => 'title',
               'sys_language_uid' => 'sys_language_uid'
           ],
           [
               'uid' => '2',
               'pid' => '1',
               'title' => 'Dummy 1-2',
               'sys_language_uid' => '0'
           ],
           [
               'uid' => '3',
               'pid' => '1',
               'title' => 'Dummy 1-3',
               'sys_language_uid' => '0'
           ],
           [
               'uid' => '4',
               'pid' => '1',
               'title' => 'Dummy 1-4',
               'sys_language_uid' => '0'
           ]
       ], $this->prepareRecordsForDbCompatAssertions($result));
    }

    /**
     * @test
     */
    public function exportReturnsRawValues(): void
    {
        $recordList = new DatabaseRecordList();
        $recordList->start(0, 'be_users', 0);
        $recordList->setFields['be_users'] = [
            'username',
            'realName',
            'email',
            'admin',
            'crdate'
        ];
        $subject = new ExportRecordList($recordList, new TranslationConfigurationProvider());
        $headerRow = $subject->getHeaderRow($recordList->setFields['be_users']);
        $contentRows = $subject->getRecords('be_users', 0, $recordList->setFields['be_users'], $this->user, false, true);
        $result = array_merge([$headerRow], $contentRows);
        self::assertEquals([
            [
                'username' => 'username',
                'email' => 'email',
                'realName' => 'realName',
                'admin' => 'admin',
                'crdate' => 'crdate'
            ],
            [
                'username' => 'admin',
                'email' => '',
                'realName' => '',
                'admin' => '1',
                'crdate' => '1366642540'
            ],
        ], $this->prepareRecordsForDbCompatAssertions($result));
    }

    /**
     * postgres is returning int fields as pure integers, others use strings.
     * In order to have our tests reliable, we cast everything to string.
     *
     * @param array $records
     * @return array
     */
    protected function prepareRecordsForDbCompatAssertions(array $records): array
    {
        foreach ($records as &$record) {
            $record = array_map('strval', $record);
        }
        return $records;
    }
}
