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

namespace TYPO3\CMS\Backend\Tests\Functional\Domain\Repository\Localization;

use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

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
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUserFromFixture(1);
        Bootstrap::initializeLanguageObject();

        $this->importCSVDataSet(ORIGINAL_ROOT . 'typo3/sysext/backend/Tests/Functional/Domain/Repository/Localization/Fixtures/DefaultPagesAndContent.csv');

        $this->subject = new LocalizationRepository();
    }

    /**
     * @return array
     */
    public function fetchOriginLanguageDataProvider(): array
    {
        return [
            'default language returns empty array' => [
                1,
                0,
                []
            ],
            'connected mode translated from default language' => [
                1,
                1,
                [
                    'sys_language_uid' => 0
                ]
            ],
            'connected mode translated from non default language' => [
                1,
                2,
                [
                    'sys_language_uid' => 1
                ]
            ],
            'free mode translated from default language' => [
                2,
                1,
                [
                    'sys_language_uid' => 0
                ]
            ],
            'free mode translated from non default language' => [
                2,
                2,
                [
                    'sys_language_uid' => 1
                ]
            ],
            'free mode copied from another page translated from default language' => [
                3,
                1,
                [
                    'sys_language_uid' => 0
                ]
            ],
            'free mode copied from another page translated from non default language' => [
                3,
                2,
                [
                    'sys_language_uid' => 1
                ]
            ]
        ];
    }

    /**
     * @param int $pageId
     * @param int $localizedLanguage
     * @param array|bool $expectedResult
     * @dataProvider fetchOriginLanguageDataProvider
     * @test
     */
    public function fetchOriginLanguage(int $pageId, int $localizedLanguage, ?array $expectedResult): void
    {
        $result = $this->subject->fetchOriginLanguage($pageId, $localizedLanguage);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function getLocalizedRecordCountDataProvider(): array
    {
        return [
            'default language returns 0 always' => [
                1,
                0,
                0
            ],
            'connected mode translated from default language' => [
                1,
                1,
                2
            ],
            'connected mode translated from non default language' => [
                1,
                2,
                1
            ],
            'free mode translated from default language' => [
                2,
                1,
                1
            ],
            'free mode translated from non default language' => [
                2,
                2,
                1
            ],
            'free mode copied from another page translated from default language' => [
                3,
                1,
                1
            ],
            'free mode copied from another page translated from non default language' => [
                3,
                2,
                1
            ]
        ];
    }

    /**
     * @param int $pageId
     * @param int $localizedLanguage
     * @param int $expectedResult
     * @dataProvider getLocalizedRecordCountDataProvider
     * @test
     */
    public function getLocalizedRecordCount(int $pageId, int $localizedLanguage, int $expectedResult): void
    {
        $result = $this->subject->getLocalizedRecordCount($pageId, $localizedLanguage);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function getRecordsToCopyDatabaseResultDataProvider(): array
    {
        return [
            'from language 0 to 1 connected mode' => [
                1,
                1,
                0,
                [
                    ['uid' => 298]
                ]
            ],
            'from language 1 to 2 connected mode' => [
                1,
                2,
                1,
                [
                    ['uid' => 300]
                ]
            ],
            'from language 0 to 1 free mode' => [
                2,
                1,
                0,
                []
            ],
            'from language 1 to 2 free mode' => [
                2,
                2,
                1,
                []
            ],
            'from language 0 to 1 free mode copied' => [
                3,
                1,
                0,
                []
            ],
            'from language 1 to 2 free mode  mode copied' => [
                3,
                2,
                1,
                []
            ],
        ];
    }

    /**
     * @param int $pageId
     * @param int $destLanguageId
     * @param int $languageId
     * @param array $expectedResult
     * @dataProvider getRecordsToCopyDatabaseResultDataProvider
     * @test
     */
    public function getRecordsToCopyDatabaseResult(int $pageId, int $destLanguageId, int $languageId, array $expectedResult): void
    {
        $result = $this->subject->getRecordsToCopyDatabaseResult($pageId, $destLanguageId, $languageId, 'uid');
        $result = $result->fetchAll();
        self::assertEquals($expectedResult, $result);
    }
}
