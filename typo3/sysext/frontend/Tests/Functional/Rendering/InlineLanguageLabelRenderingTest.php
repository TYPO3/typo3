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

namespace TYPO3\CMS\Frontend\Tests\Functional\Rendering;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class InlineLanguageLabelRenderingTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en'],
        'DA' => ['id' => 1, 'title' => 'Dansk', 'locale' => 'da_DK.UTF8', 'iso' => 'da'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCsvDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DA', '/da/', ['EN']),
            ],
        );
        $this->setUpFrontendRootPage(
            1,
            ['EXT:frontend/Tests/Functional/Rendering/Fixtures/InlineLanguageLabelRenderingTest.typoscript']
        );
    }

    public static function inlineLanguageLabelsDataProvider(): \Generator
    {
        yield 'default language uses the source labels' => [
            'https://website.local/en/',
            'English label',
        ];
        yield 'locale with country code falls back to the language label file' => [
            'https://website.local/da/',
            'Dansk etiket',
        ];
    }

    #[DataProvider('inlineLanguageLabelsDataProvider')]
    #[Test]
    public function inlineLanguageLabelsAreResolvedForSiteLanguage(string $uri, string $expectedLabel): void
    {
        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));

        self::assertStringContainsString(
            json_encode(['inline.label' => $expectedLabel], JSON_THROW_ON_ERROR),
            (string)$response->getBody()
        );
    }
}
