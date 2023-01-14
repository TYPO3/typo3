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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\Slug;

use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Tests\Functional\DataHandling\AbstractDataHandlerActionTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SlugHelperUniqueWithLanguageTest extends AbstractDataHandlerActionTestCase
{
    protected $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler_slug',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(__DIR__ . '/DataSet/TestSlugUniqueWithLanguages.csv');
        $this->setUpFrontendSite(1);
        $this->setUpFrontendRootPage(1, ['typo3/sysext/core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript']);
    }

    public function buildSlugForUniqueRespectsLanguageDataProvider(): array
    {
        return [
            'sameLanguageSameSlug' =>  [
                'expectedSlug' => 'unique-slug-1',
                'recordData' => [
                    'uid' => 2,
                    'pid' => 1,
                    'sys_language_uid' => 0,
                    'title' => 'Some title',
                    'slug' => 'unique-slug',
                ],
            ],
            'sameLanguageDifferentSlug' =>  [
                'expectedSlug' => 'other-slug',
                'recordData' => [
                    'uid' => 2,
                    'pid' => 1,
                    'sys_language_uid' => 0,
                    'title' => 'Some title',
                    'slug' => 'other-slug',
                ],
            ],
            'otherLanguageSameSlug' =>  [
                'expectedSlug' => 'unique-slug',
                'recordData' => [
                    'uid' => 2,
                    'pid' => 1,
                    'sys_language_uid' => 1,
                    'title' => 'Some title',
                    'slug' => 'unique-slug',
                ],
            ],
            'allLanguagesSameSlug' =>  [
                'expectedSlug' => 'unique-slug-1',
                'recordData' => [
                    'uid' => 2,
                    'pid' => 1,
                    'sys_language_uid' => -1,
                    'title' => 'Some title',
                    'slug' => 'unique-slug',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider buildSlugForUniqueRespectsLanguageDataProvider
     */
    public function buildSlugForUniqueRespectsLanguage(string $expectedSlug, array $recordData): void
    {
        $subject = GeneralUtility::makeInstance(
            SlugHelper::class,
            'tx_testdatahandler_slug',
            'slug',
            [
                'generatorOptions' => [
                    'fields' => ['title'],
                    'fieldSeparator' => '/',
                    'prefixParentPageSlug' => false,
                    'replacements' => [
                        '/' => '-',
                    ],
                ],
                'fallbackCharacter' => '-',
                'eval' => 'unique',
                'default' => '',
            ]
        );

        $state = RecordStateFactory::forName('tx_testdatahandler_slug')->fromArray($recordData);
        $resultSlug = $subject->buildSlugForUniqueInTable($recordData['slug'], $state);
        self::assertSame($expectedSlug, $resultSlug);
    }
}
