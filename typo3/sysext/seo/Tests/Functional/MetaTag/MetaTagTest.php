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

namespace TYPO3\CMS\Seo\Tests\Functional\MetaTag;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\AbstractTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Pins the interaction between meta tags generated from the page record by EXT:seo
 * and meta tags defined via "page.meta" TypoScript.
 *
 * Meta tags of the page record are added before TypoScript is evaluated, so a value
 * from the page record wins, unless TypoScript uses "replace = 1".
 */
final class MetaTagTest extends AbstractTestCase
{
    protected array $coreExtensionsToLoad = [
        'workspaces',
        'seo',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/Scenarios/pages_with_seo_meta.csv');

        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
    }

    public static function ensureMetaDataAreCorrectDataProvider(): array
    {
        return [
            'page record wins over TypoScript for description' => [
                1,
                [
                    'page.meta.description = Description from TypoScript',
                ],
                ['<meta name="description" content="Description from page record">'],
                ['<meta name="description" content="Description from TypoScript">'],
            ],
            'TypoScript with replace wins over page record for description' => [
                1,
                [
                    'page.meta.description = Description from TypoScript',
                    'page.meta.description.replace = 1',
                ],
                ['<meta name="description" content="Description from TypoScript">'],
                ['<meta name="description" content="Description from page record">'],
            ],
            'page record wins over TypoScript for og:title' => [
                1,
                [
                    'page.meta.og:title = OG title from TypoScript',
                ],
                ['<meta property="og:title" content="OG title from page record">'],
                ['<meta property="og:title" content="OG title from TypoScript">'],
            ],
            'TypoScript with replace wins over page record for og:title' => [
                1,
                [
                    'page.meta.og:title = OG title from TypoScript',
                    'page.meta.og:title.replace = 1',
                ],
                ['<meta property="og:title" content="OG title from TypoScript">'],
                ['<meta property="og:title" content="OG title from page record">'],
            ],
            'page record wins over TypoScript for twitter:card' => [
                1,
                [
                    'page.meta.twitter:card = summary_large_image',
                ],
                ['<meta name="twitter:card" content="summary">'],
                ['<meta name="twitter:card" content="summary_large_image">'],
            ],
            'TypoScript with replace wins over page record for twitter:card' => [
                1,
                [
                    'page.meta.twitter:card = summary_large_image',
                    'page.meta.twitter:card.replace = 1',
                ],
                ['<meta name="twitter:card" content="summary_large_image">'],
                ['<meta name="twitter:card" content="summary">'],
            ],
            'TypoScript with replace but empty value keeps the page record value' => [
                1,
                [
                    'page.meta.description =',
                    'page.meta.description.replace = 1',
                ],
                ['<meta name="description" content="Description from page record">'],
                [],
            ],
            'TypoScript is used if the page record field is empty' => [
                2,
                [
                    'page.meta.description = Description from TypoScript',
                ],
                ['<meta name="description" content="Description from TypoScript">'],
                [],
            ],
            'TypoScript stdWrap is used if the page record field is empty' => [
                2,
                [
                    'page.meta.og:title.data = levelfield:-1, og_title, slide',
                ],
                ['<meta property="og:title" content="OG title from page record">'],
                [],
            ],
        ];
    }

    #[DataProvider('ensureMetaDataAreCorrectDataProvider')]
    #[Test]
    public function ensureMetaDataAreCorrect(int $pageId, array $typoScriptSetup, array $expectedMetaTags, array $notExpectedMetaTags): void
    {
        $this->setUpFrontendRootPage(1, [], ['config' => implode(LF, array_merge(['page = PAGE'], $typoScriptSetup))]);

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => $pageId,
            ])
        );
        $body = (string)$response->getBody();

        foreach ($expectedMetaTags as $expectedMetaTag) {
            self::assertStringContainsString($expectedMetaTag, $body);
        }
        foreach ($notExpectedMetaTags as $notExpectedMetaTag) {
            self::assertStringNotContainsString($notExpectedMetaTag, $body);
        }
    }
}
