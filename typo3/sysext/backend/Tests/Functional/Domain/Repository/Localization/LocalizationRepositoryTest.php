<?php
namespace TYPO3\CMS\Backend\Tests\Functional\Domain\Repository\Localization;

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

use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Tests\FunctionalTestCase;

/**
 * Test case for TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository
 */
class LocalizationRepositoryTest extends FunctionalTestCase
{
    /**
     * @var LocalizationRepository
     */
    protected $subject;

    /**
     * Sets up this test case.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->setUpBackendUserFromFixture(1);
        Bootstrap::getInstance()->initializeLanguageObject();

        $this->importCSVDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Domain/Repository/Localization/Fixtures/DefaultPagesAndContent.csv');

        $this->subject = new LocalizationRepository();
    }

    public function fetchOriginLanguageDataProvider()
    {
        return [
            'default language returns 0' => [
                1,
                0,
                0,
                [
                    'sys_language_uid' => 0
                ]
            ],
            'connected mode translated from default language' => [
                1,
                0,
                1,
                [
                    'sys_language_uid' => 0
                ]
            ],
            'connected mode translated from non default language' => [
                1,
                0,
                2,
                [
                    'sys_language_uid' => 1
                ]
            ],
            'free mode translated from default language' => [
                2,
                0,
                1,
                [
                    'sys_language_uid' => 0
                ]
            ],
            'free mode translated from non default language' => [
                2,
                0,
                2,
                [
                    'sys_language_uid' => 1
                ]
            ],
            'free mode copied from another page translated from default language' => [
                3,
                0,
                1,
                [
                    'sys_language_uid' => 0
                ]
            ],
            'free mode copied from another page translated from non default language' => [
                3,
                0,
                2,
                [
                    'sys_language_uid' => 0
                ]
            ]
        ];
    }

    /**
     * @dataProvider fetchOriginLanguageDataProvider
     * @test
     *
     * @param $pageId
     * @param $colPos
     * @param $localizedLanguage
     * @param $expectedResult
     */
    public function fetchOriginLanguage($pageId, $colPos, $localizedLanguage, $expectedResult)
    {
        $result = $this->subject->fetchOriginLanguage($pageId, $colPos, $localizedLanguage);
        $this->assertEquals($expectedResult, $result);
    }

    public function getLocalizedRecordCountDataProvider()
    {
        return [
            'default language returns 0 always' => [
                1,
                0,
                0,
                0
            ],
            'connected mode translated from default language' => [
                1,
                0,
                1,
                2
            ],
            'connected mode translated from non default language' => [
                1,
                0,
                2,
                1
            ],
            'free mode translated from default language' => [
                2,
                0,
                1,
                1
            ],
            'free mode translated from non default language' => [
                2,
                0,
                2,
                1
            ],
            'free mode copied from another page translated from default language' => [
                3,
                0,
                1,
                1
            ],
            'free mode copied from another page translated from non default language' => [
                3,
                0,
                2,
                1
            ]
        ];
    }

    /**
     * @dataProvider getLocalizedRecordCountDataProvider
     * @test
     * @param int $pageId
     * @param int $colPos
     * @param int $localizedLanguage
     * @param int $expectedResult
     */
    public function getLocalizedRecordCount($pageId, $colPos, $localizedLanguage, $expectedResult)
    {
        $result = $this->subject->getLocalizedRecordCount($pageId, $colPos, $localizedLanguage);
        $this->assertEquals($expectedResult, $result);
    }

    public function getRecordsToCopyDatabaseResultDataProvider()
    {
        return [
            'from language 0 to 1 connected mode' => [
                1,
                0,
                1,
                0,
                [
                    ['uid' => 298]
                ]
            ],
            'from language 1 to 2 connected mode' => [
                1,
                0,
                2,
                1,
                [
                    ['uid' => 300]
                ]
            ],
            'from language 0 to 1 free mode' => [
                2,
                0,
                1,
                0,
                []
            ],
            'from language 1 to 2 free mode' => [
                2,
                0,
                2,
                1,
                []
            ],
            'from language 0 to 1 free mode copied' => [
                3,
                0,
                1,
                0,
                [
                    // this is wrong in terms of usability as we have a translation
                    // already for this record, but in v7.6 there is no way to find
                    // this out, since the record has been copied from page 2
                    // and therefore t3_origuid has been overridden by the copy action
                    ['uid' => 313]
                ]
            ],
            'from language 1 to 2 free mode  mode copied' => [
                3,
                0,
                2,
                1,
                [
                    // this is wrong in terms of usability as we have a translation
                    // already for this record (from language 1), but in v7.6 there is no way to find
                    // this out, since the record has been copied from page 2
                    // and therefore t3_origuid has been overridden by the copy action
                    ['uid' => 314]
                ]
            ],
        ];
    }

    /**
     * @dataProvider getRecordsToCopyDatabaseResultDataProvider
     * @test
     * @param int $pageId
     * @param int $colPos
     * @param int $destLanguageId
     * @param int $languageId
     * @param array $expectedResult
     */
    public function getRecordsToCopyDatabaseResult($pageId, $colPos, $destLanguageId, $languageId, $expectedResult)
    {
        $result = $this->subject->getRecordsToCopyDatabaseResult($pageId, $colPos, $destLanguageId, $languageId, 'uid');
        $result = $result->fetch_all(MYSQLI_ASSOC);
        $this->assertEquals($expectedResult, $result);
    }
}
