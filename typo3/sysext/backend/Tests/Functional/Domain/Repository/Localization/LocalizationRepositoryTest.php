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

/**
 * Test case for TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository
 */
class LocalizationRepositoryTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
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
        Bootstrap::initializeLanguageObject();

        $this->importCSVDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Domain/Repository/Localization/Fixtures/DefaultPagesAndContent.csv');

        $this->subject = new LocalizationRepository();
    }

    public function fetchOriginLanguageDataProvider()
    {
        return [
            'default language returns false' => [
                1,
                0,
                0,
                false
            ],
            'connected mode translated from default language' => [
                1,
                0,
                1,
                false
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
                false
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
                false
            ],
            'free mode copied from another page translated from non default language' => [
                3,
                0,
                2,
                [
                    'sys_language_uid' => 1
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
                []
            ],
            'from language 1 to 2 free mode  mode copied' => [
                3,
                0,
                2,
                1,
                []
            ],
        ];
    }

    /**
     * @dataProvider getRecordsToCopyDatabaseResultDataProvider
     * @test
     */
    public function getRecordsToCopyDatabaseResult($pageId, $colPos, $destLanguageId, $languageId, $expectedResult)
    {
        $result = $this->subject->getRecordsToCopyDatabaseResult($pageId, $colPos, $destLanguageId, $languageId, 'uid');
        $result = $result->fetchAll();
        $this->assertEquals($expectedResult, $result);
    }
}
