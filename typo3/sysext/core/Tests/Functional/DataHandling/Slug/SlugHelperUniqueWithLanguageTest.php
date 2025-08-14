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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\DataHandling\Model\RecordStateFactory;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SlugHelperUniqueWithLanguageTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_datahandler_slug',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/DataSet/TestSlugUniqueWithLanguages.csv');
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/be_users_admin.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ],
        );
    }

    public static function buildSlugForUniqueRespectsLanguageDataProvider(): array
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

    #[DataProvider('buildSlugForUniqueRespectsLanguageDataProvider')]
    #[Test]
    public function buildSlugForUniqueRespectsLanguage(string $expectedSlug, array $recordData): void
    {
        $subject = new SlugHelper(
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
