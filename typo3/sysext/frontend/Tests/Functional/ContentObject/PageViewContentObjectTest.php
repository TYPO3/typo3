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

namespace TYPO3\CMS\Frontend\Tests\Functional\ContentObject;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageViewContentObjectTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en-US'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr-FR'],
    ];
    protected const ROOT_PAGE_ID = 1;

    protected array $testExtensionsToLoad = [
        'typo3/sysext/frontend/Tests/Functional/Fixtures/Extensions/test_fluidpagerendering',
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/FluidPage/pages.csv');
        $this->writeSiteConfiguration(
            'pageview_template',
            $this->buildSiteConfiguration(self::ROOT_PAGE_ID, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', '/fr'),
            ],
        );
    }

    #[Test]
    public function renderWorksWithPlainRenderingInMultipleLanguages(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:frontend/Tests/Functional/Fixtures/Extensions/test_fluidpagerendering/Configuration/TypoScript/plain.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('You are on page Fluid Root Page', (string)$response->getBody());
        self::assertStringContainsString('This is a standard page with no content.', (string)$response->getBody());
        self::assertStringContainsString('page-layout-identifier-Standard', (string)$response->getBody());
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID)->withLanguageId(1));
        self::assertStringContainsString('Vous êtes à la page Fluid Root Page FR', (string)$response->getBody());
    }

    public static function reservedVariableNameDataProvider(): array
    {
        return [
            ['variableName' => 'site'],
            ['variableName' => 'language'],
            ['variableName' => 'page'],
        ];
    }

    #[DataProvider('reservedVariableNameDataProvider')]
    #[Test]
    public function assignReservedVariableNameInTypoScriptThrowsInvalidArgumentException(string $variableName): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:frontend/Tests/Functional/Fixtures/Extensions/test_fluidpagerendering/Configuration/TypoScript/plain.typoscript',
                sprintf(
                    'EXT:frontend/Tests/Functional/Fixtures/Extensions/test_fluidpagerendering/Configuration/TypoScript/reserved_variablename_%s.typoscript',
                    $variableName
                ),
            ]
        );
        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionCode(1711748615);
        self::expectExceptionMessage(sprintf('Cannot use reserved name "%s" as variable name in PAGEVIEW.', $variableName));
        $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
    }

    #[Test]
    public function pathsCanBeSpecifiedWithoutTrailingSlash(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:frontend/Tests/Functional/Fixtures/Extensions/test_fluidpagerendering/Configuration/TypoScript/withoutTrailingSlash.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
        self::assertStringContainsString('You are on page Fluid Root Page', (string)$response->getBody());
        self::assertStringContainsString('This is content from the test partial.', (string)$response->getBody());
    }
}
