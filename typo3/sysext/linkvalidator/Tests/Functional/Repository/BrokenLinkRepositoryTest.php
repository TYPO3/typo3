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

namespace TYPO3\CMS\Linkvalidator\Tests\Functional\Repository;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;
use TYPO3\CMS\Linkvalidator\Repository\BrokenLinkRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BrokenLinkRepositoryTest extends FunctionalTestCase
{
    protected $coreExtensionsToLoad = [
        'linkvalidator',
        'seo',
    ];

    /**
     * @var BrokenLinkRepository
     */
    protected $brokenLinksRepository;

    protected $beusers = [
        'admin' => [
            'fixture' => __DIR__ . '/Fixtures/be_users.csv',
            'uid' => 1,
            'groupFixture' => '',
        ],
        'no group' => [
            'fixture' => __DIR__ . '/Fixtures/be_users.csv',
            'uid' => 2,
            'groupFixture' => '',
        ],
        // write access to pages, tt_content
        'group 1' => [
            'fixture' => __DIR__ . '/Fixtures/be_users.csv',
            'uid' => 3,
            'groupFixture' => __DIR__ . '/Fixtures/be_groups.csv',
        ],
        // write access to pages, tt_content, exclude field pages.header_link
        'group 2' => [
            'fixture' => __DIR__ . '/Fixtures/be_users.csv',
            'uid' => 4,
            'groupFixture' => __DIR__ . '/Fixtures/be_groups.csv',
        ],
        // write access to pages, tt_content (restricted to default language)
        'group 3' => [
            'fixture' => __DIR__ . '/Fixtures/be_users.csv',
            'uid' => 5,
            'groupFixture' => __DIR__ . '/Fixtures/be_groups.csv',
        ],
        // group 6: access to all, but restricted via explicit allow to CType=texmedia and text
        'group 6' => [
            'fixture' => __DIR__ . '/Fixtures/be_users.csv',
            'uid' => 6,
            'groupFixture' => __DIR__ . '/Fixtures/be_groups.csv',
        ],

    ];

    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['TYPO3_CONF_VARS']['BE']['explicitADmode'] = 'explicitAllow';
        $this->brokenLinksRepository = new BrokenLinkRepository();
    }

    public function getLinkCountsForPagesAndLinktypesReturnsCorrectCountForUserDataProvider(): ?\Generator
    {
        yield 'Admin user should see all broken links' =>
        [
            // backendUser: 1=admin
            $this->beusers['admin'],
            // input file for DB
            __DIR__ . '/Fixtures/input.csv',
            //pids
            [1],
            // expected result:
            [
                'db' => 1,
                'file' => 1,
                'external' => 2,
                'total' => 4,
            ],
        ];
        yield 'User with no group should see none' =>
        [
            // backend user
            $this->beusers['no group'],
            // input file for DB
            __DIR__ . '/Fixtures/input.csv',
            //pids
            [1],
            // expected result:
            [
                'total' => 0,
            ],
        ];
        yield 'User with permission to pages but not to specific tables should see none' =>
        [
            // backend user
            $this->beusers['no group'],
            // input file for DB
            __DIR__ . '/Fixtures/input_permissions_user_2.csv',
            //pids
            [1],
            // expected result:
            [
                'total' => 0,
            ],
        ];
        yield 'User with permission to pages and to specific tables, but no exclude fields should see 3 of 4 broken links' =>
        [
            // backend user
            $this->beusers['group 1'],
            // input file for DB
            __DIR__ . '/Fixtures/input_permissions_user_3.csv',
            //pids
            [1],
            // expected result:
            [
                'db' => 1,
                'file' => 1,
                'external' => 1,
                'total' => 3,
            ],
        ];
        yield 'User with permission to pages, specific tables and exclude fields should see all broken links' =>
        [
            // backend user
            $this->beusers['group 2'],
            // input file for DB
            __DIR__ . '/Fixtures/input_permissions_user_4.csv',
            //pids
            [1],
            // expected result:
            [
                'db' => 1,
                'file' => 1,
                'external' => 2,
                'total' => 4,
            ],
        ];
        yield 'User has write permission only for Ctype textmedia and text, should see only broken links from textmedia records' =>
        [
            // backend user
            $this->beusers['group 6'],
            // input file for DB
            __DIR__ . '/Fixtures/input_permissions_user_6_explicit_allow.csv',
            //pids
            [1],
            // expected result:
            [
                'external' => 1,
                'total' => 1,
            ],
        ];

        yield 'User has write permission only for default language and should see only 1 of 2 broken links' =>
        [
            // backend user
            $this->beusers['group 3'],
            // input file for DB
            __DIR__ . '/Fixtures/input_permissions_user_5.csv',
            //pids
            [1],
            // expected result:
            [
                'external' => 1,
                'total' => 1,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getLinkCountsForPagesAndLinktypesReturnsCorrectCountForUserDataProvider
     */
    public function getLinkCountsForPagesAndLinktypesReturnsCorrectCountForUser(
        array $beuser,
        string $inputFile,
        array $pidList,
        array $expectedOutput
    ): void {
        $tsConfig = [
            'searchFields.' => [
                'pages' => 'media,url,canonical_link',
                'tt_content' => 'bodytext,header_link,records',
            ],
            'linktypes' => 'db,file,external',
            'checkhidden' => '0',
        ];
        $linkTypes = explode(',', $tsConfig['linktypes']);

        $searchFields = $tsConfig['searchFields.'];
        foreach ($searchFields as $table => $fields) {
            $searchFields[$table] = explode(',', $fields);
        }

        $this->setupBackendUserAndGroup($beuser['uid'], $beuser['fixture'], $beuser['groupFixture']);

        $this->importCSVDataSet($inputFile);

        $linkAnalyzer = $this->getContainer()->get(LinkAnalyzer::class);
        $linkAnalyzer->init($searchFields, $pidList, $tsConfig);
        $linkAnalyzer->getLinkStatistics($linkTypes);
        $result = $this->brokenLinksRepository->getNumberOfBrokenLinksForRecordsOnPages(
            $pidList,
            $searchFields
        );

        self::assertEquals($expectedOutput, $result);
    }

    public function getAllBrokenLinksForPagesReturnsCorrectCountForUserDataProvider(): ?\Generator
    {
        yield 'Admin user should see all broken links' =>
        [
            // backendUser: 1=admin
            $this->beusers['admin'],
            // input file for DB
            __DIR__ . '/Fixtures/input.csv',
            //pids
            [1],
            // count
            4,
        ];

        yield 'User with no group should see none' =>
        [
            // backend user
            $this->beusers['no group'],
            // input file for DB
            __DIR__ . '/Fixtures/input.csv',
            //pids
            [1],
            // count
            0,
        ];
        yield 'User with permission to pages but not to specific tables should see none' =>
        [
            // backend user
            $this->beusers['no group'],
            // input file for DB
            __DIR__ . '/Fixtures/input_permissions_user_2.csv',
            //pids
            [1],
            // count
            0,
        ];
        yield 'User with permission to pages and to specific tables, but no exclude fields should see 3 of 4 broken links' =>
        [
            // backend user
            $this->beusers['group 1'],
            // input file for DB
            __DIR__ . '/Fixtures/input_permissions_user_3.csv',
            //pids
            [1],
            // count
            3,
        ];
        yield 'User with permission to pages, specific tables and exclude fields should see all broken links' =>
        [
            // backend user
            $this->beusers['group 2'],
            // input file for DB
            __DIR__ . '/Fixtures/input_permissions_user_4.csv',
            //pids
            [1],
            // count
            4,
        ];
        yield 'User has write permission only for Ctype textmedia and text, should see only broken links from textmedia records' =>
        [
            // backend user
            $this->beusers['group 6'],
            // input file for DB
            __DIR__ . '/Fixtures/input_permissions_user_6_explicit_allow.csv',
            //pids
            [1],
            // count
            1,
        ];

        yield 'User has write permission only for default language and should see only 1 of 2 broken links' =>
        [
            // backend user
            $this->beusers['group 3'],
            // input file for DB
            __DIR__ . '/Fixtures/input_permissions_user_5.csv',
            //pids
            [1],
            // count
            1,
        ];
    }

    /**
     * @test
     * @dataProvider getAllBrokenLinksForPagesReturnsCorrectCountForUserDataProvider
     */
    public function getAllBrokenLinksForPagesReturnsCorrectCountForUser(
        array $beuser,
        string $inputFile,
        array $pidList,
        int $expectedCount
    ): void {
        $tsConfig = [
            'searchFields.' => [
                'pages' => 'media,url,canonical_link',
                'tt_content' => 'bodytext,header_link,records',
            ],
            'linktypes' => 'db,file,external',
            'checkhidden' => '0',
        ];
        $linkTypes = explode(',', $tsConfig['linktypes']);

        $searchFields = $tsConfig['searchFields.'];
        foreach ($searchFields as $table => $fields) {
            $searchFields[$table] = explode(',', $fields);
        }

        $this->setupBackendUserAndGroup($beuser['uid'], $beuser['fixture'], $beuser['groupFixture']);

        $this->importCSVDataSet($inputFile);

        $linkAnalyzer = $this->getContainer()->get(LinkAnalyzer::class);
        $linkAnalyzer->init($searchFields, $pidList, $tsConfig);
        $linkAnalyzer->getLinkStatistics($linkTypes);

        $results = $this->brokenLinksRepository->getAllBrokenLinksForPages(
            $pidList,
            $linkTypes,
            $searchFields
        );

        self::assertCount($expectedCount, $results);
    }

    public function getAllBrokenLinksForPagesReturnsCorrectValuesForUserDataProvider(): ?\Generator
    {
        yield 'Admin user should see all broken links' =>
        [
            // backendUser: 1=admin
            $this->beusers['admin'],
            // input file for DB
            __DIR__ . '/Fixtures/input.csv',
            //pids
            [1],
            // expected result:
            [
                [
                   'record_uid' => 1,
                   'record_pid' => 1,
                   'language' => 0,
                   'headline' => 'link',
                   'field' => 'bodytext',
                   'table_name' => 'tt_content',
                   'element_type' => 'textmedia',
                   'link_title' => 'link',
                   'url' => 'http://localhost/iAmInvalid',
                   'link_type' => 'external',
                   'needs_recheck' => 0,
                ],
                [
                    'record_uid' => 2,
                    'record_pid' => 1,
                    'language' => 0,
                    'headline' => '[No title]',
                    'field' => 'header_link',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => null,
                    'url' => 'http://localhost/iAmInvalid',
                    'link_type' => 'external',
                    'needs_recheck' => 0,
                ],
                [
                    'record_uid' => 3,
                    'record_pid' => 1,
                    'language' => 0,
                    'headline' => 'broken link',
                    'field' => 'bodytext',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => 'broken link',
                    'url' => '85',
                    'link_type' => 'db',
                    'needs_recheck' => 0,
                ],
                [
                    'record_uid' => 5,
                    'record_pid' => 1,
                    'language' => 0,
                    'headline' => 'broken link',
                    'field' => 'bodytext',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => 'broken link',
                    'url' => 'file:88',
                    'link_type' => 'file',
                    'needs_recheck' => 0,
                ],
            ],
        ];

        yield 'User with no group should see none' =>
        [
            // backend user
            $this->beusers['no group'],
            // input file for DB
            __DIR__ . '/Fixtures/input.csv',
            //pids
            [1],
            // expected result:
            [],
        ];
        yield 'User with permission to pages but not to specific tables should see none' =>
        [
            // backend user
            $this->beusers['no group'],
            // input file for DB
            __DIR__ . '/Fixtures/input_permissions_user_2.csv',
            //pids
            [1],
            // expected result:
            [],
        ];
        yield 'User with permission to pages and to specific tables, but no exclude fields should see 3 of 4 broken links' =>
        [
            // backend user
            $this->beusers['group 1'],
            // input file for DB
            __DIR__ . '/Fixtures/input_permissions_user_3.csv',
            //pids
            [1],
            // expected result:
            [
                [
                    'record_uid' => 1,
                    'record_pid' => 1,
                    'language' => 0,
                    'headline' => 'link',
                    'field' => 'bodytext',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => 'link',
                    'url' => 'http://localhost/iAmInvalid',
                    'link_type' => 'external',
                    'needs_recheck' => 0,
                ],
                [
                    'record_uid' => 3,
                    'record_pid' => 1,
                    'language' => 0,
                    'headline' => 'broken link',
                    'field' => 'bodytext',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => 'broken link',
                    'url' => '85',
                    'link_type' => 'db',
                    'needs_recheck' => 0,
                ],
                [
                    'record_uid' => 5,
                    'record_pid' => 1,
                    'language' => 0,
                    'headline' => 'broken link',
                    'field' => 'bodytext',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => 'broken link',
                    'url' => 'file:88',
                    'link_type' => 'file',
                    'needs_recheck' => 0,
                ],
            ],
        ];
        yield 'User with permission to pages, specific tables and exclude fields should see all broken links' =>
        [
            // backend user
            $this->beusers['group 2'],
            // input file for DB
            __DIR__ . '/Fixtures/input_permissions_user_4.csv',
            //pids
            [1],
            // expected result:
            [
                [
                    'record_uid' => 1,
                    'record_pid' => 1,
                    'language' => 0,
                    'headline' => 'link',
                    'field' => 'bodytext',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => 'link',
                    'url' => 'http://localhost/iAmInvalid',
                    'link_type' => 'external',
                    'needs_recheck' => 0,
                ],
                [
                    'record_uid' => 2,
                    'record_pid' => 1,
                    'language' => 0,
                    'headline' => '[No title]',
                    'field' => 'header_link',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => null,
                    'url' => 'http://localhost/iAmInvalid',
                    'link_type' => 'external',
                    'needs_recheck' => 0,
                ],
                [
                    'record_uid' => 3,
                    'record_pid' => 1,
                    'language' => 0,
                    'headline' => 'broken link',
                    'field' => 'bodytext',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => 'broken link',
                    'url' => '85',
                    'link_type' => 'db',
                    'needs_recheck' => 0,
                ],
                [
                    'record_uid' => 5,
                    'record_pid' => 1,
                    'language' => 0,
                    'headline' => 'broken link',
                    'field' => 'bodytext',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => 'broken link',
                    'url' => 'file:88',
                    'link_type' => 'file',
                    'needs_recheck' => 0,
                ],
            ],
        ];
        yield 'User has write permission only for Ctype textmedia and text, should see only broken links from textmedia records' =>
        [
            // backend user
            $this->beusers['group 6'],
            // input file for DB
            __DIR__ . '/Fixtures/input_permissions_user_6_explicit_allow.csv',
            //pids
            [1],
            // expected result:
            [
                [
                    'record_uid' => 1,
                    'record_pid' => 1,
                    'language' => 0,
                    'headline' => 'link',
                    'field' => 'bodytext',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => 'link',
                    'url' => 'http://localhost/iAmInvalid',
                    'link_type' => 'external',
                    'needs_recheck' => 0,
                ],
            ],
        ];

        yield 'User has write permission only for default language and should see only 1 of 2 broken links' =>
        [
            // backend user
            $this->beusers['group 3'],
            // input file for DB
            __DIR__ . '/Fixtures/input_permissions_user_5.csv',
            //pids
            [1],
            // expected result:
            [
                [
                    'record_uid' => 1,
                    'record_pid' => 1,
                    'language' => 0,
                    'headline' => 'link',
                    'field' => 'bodytext',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => 'link',
                    'url' => 'http://localhost/iAmInvalid',
                    'link_type' => 'external',
                    'needs_recheck' => 0,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getAllBrokenLinksForPagesReturnsCorrectValuesForUserDataProvider
     */
    public function getAllBrokenLinksForPagesReturnsCorrectValuesForUser(
        array $beuser,
        string $inputFile,
        array $pidList,
        array $expectedResult
    ): void {
        $tsConfig = [
            'searchFields.' => [
                'pages' => 'media,url,canonical_link',
                'tt_content' => 'bodytext,header_link,records',
            ],
            'linktypes' => 'db,file,external',
            'checkhidden' => '0',
        ];
        $linkTypes = explode(',', $tsConfig['linktypes']);

        $searchFields = $tsConfig['searchFields.'];
        foreach ($searchFields as $table => $fields) {
            $searchFields[$table] = explode(',', $fields);
        }

        $this->setupBackendUserAndGroup($beuser['uid'], $beuser['fixture'], $beuser['groupFixture']);

        $this->importCSVDataSet($inputFile);

        $linkAnalyzer = $this->getContainer()->get(LinkAnalyzer::class);
        $linkAnalyzer->init($searchFields, $pidList, $tsConfig);
        $linkAnalyzer->getLinkStatistics($linkTypes);

        $results = $this->brokenLinksRepository->getAllBrokenLinksForPages(
            $pidList,
            $linkTypes,
            $searchFields
        );

        foreach ($results as &$result) {
            unset($result['url_response']);
            unset($result['uid']);
            unset($result['last_check']);
        }
        self::assertEquals($expectedResult, $results);
    }

    public function getAllBrokenLinksForPagesRespectsGivenLanguagesDataProvider(): ?\Generator
    {
        yield 'All languages should be returend' =>
        [
            // backendUser: 1=admin
            $this->beusers['admin'],
            // input file for DB
            __DIR__ . '/Fixtures/input_languages.csv',
            //pids
            [1, 2, 3],
            //languages
            [],
            // expected result:
            [
                [
                    'record_uid' => 1,
                    'record_pid' => 1,
                    'language' => 0,
                    'headline' => 'link',
                    'field' => 'bodytext',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => 'link',
                    'url' => 'http://localhost/iAmInvalid',
                    'link_type' => 'external',
                ],
                [
                    'record_uid' => 2,
                    'record_pid' => 1,
                    'language' => 1,
                    'headline' => 'link',
                    'field' => 'bodytext',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => 'link',
                    'url' => 'http://localhost/iAmInvalid',
                    'link_type' => 'external',
                ],
                [
                    'record_uid' => 3,
                    'record_pid' => 1,
                    'language' => 2,
                    'headline' => 'link',
                    'field' => 'bodytext',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => 'link',
                    'url' => 'http://localhost/iAmInvalid',
                    'link_type' => 'external',
                ],
            ],
        ];

        yield 'Only defined languages should be returend' =>
        [
            // backendUser: 1=admin
            $this->beusers['admin'],
            // input file for DB
            __DIR__ . '/Fixtures/input_languages.csv',
            //pids
            [1, 2, 3],
            //languages
            [0, 2],
            // expected result:
            [
                [
                    'record_uid' => 1,
                    'record_pid' => 1,
                    'language' => 0,
                    'headline' => 'link',
                    'field' => 'bodytext',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => 'link',
                    'url' => 'http://localhost/iAmInvalid',
                    'link_type' => 'external',
                ],
                [
                    'record_uid' => 3,
                    'record_pid' => 1,
                    'language' => 2,
                    'headline' => 'link',
                    'field' => 'bodytext',
                    'table_name' => 'tt_content',
                    'element_type' => 'textmedia',
                    'link_title' => 'link',
                    'url' => 'http://localhost/iAmInvalid',
                    'link_type' => 'external',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getAllBrokenLinksForPagesRespectsGivenLanguagesDataProvider
     */
    public function getAllBrokenLinksForPagesRespectsGivenLanguages(
        array $beuser,
        string $inputFile,
        array $pidList,
        array $languages,
        array $expectedResult
    ): void {
        $tsConfig = [
            'searchFields.' => [
                'tt_content' => 'bodytext',
            ],
            'linktypes' => 'external',
            'checkhidden' => '0',
        ];
        $linkTypes = explode(',', $tsConfig['linktypes']);

        $searchFields = $tsConfig['searchFields.'];
        foreach ($searchFields as $table => $fields) {
            $searchFields[$table] = explode(',', $fields);
        }

        $this->setupBackendUserAndGroup($beuser['uid'], $beuser['fixture'], $beuser['groupFixture']);
        $this->importCSVDataSet($inputFile);

        $linkAnalyzer = $this->getContainer()->get(LinkAnalyzer::class);
        $linkAnalyzer->init($searchFields, $pidList, $tsConfig);
        $linkAnalyzer->getLinkStatistics($linkTypes);

        $results = $this->brokenLinksRepository->getAllBrokenLinksForPages(
            $pidList,
            $linkTypes,
            $searchFields,
            $languages
        );

        foreach ($results as &$result) {
            unset($result['url_response'], $result['uid'], $result['last_check'], $result['needs_recheck']);
        }

        self::assertEquals($expectedResult, $results);
    }

    protected function setupBackendUserAndGroup(int $uid, string $fixtureFile, string $groupFixtureFile): void
    {
        if ($groupFixtureFile) {
            $this->importCSVDataSet($groupFixtureFile);
        }
        $this->backendUserFixture = $fixtureFile;
        $this->setUpBackendUserFromFixture($uid);
        Bootstrap::initializeLanguageObject();
    }

    protected function setUpBackendUserFromFixture($userUid): BackendUserAuthentication
    {
        $this->importCSVDataSet($this->backendUserFixture);
        return $this->setUpBackendUser($userUid);
    }
}
