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
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\Exception\InvalidTemplateResourceException;

final class PageViewContentObjectTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    private const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en-US'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr-FR'],
    ];
    private const ROOT_PAGE_ID = 1;
    private const SPECIAL_PAGE_ID = 3;

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

    public static function renderWorksWithPlainRenderingInMultipleLanguagesDataProvider(): array
    {
        return [
            'standard layout on root page' => [
                self::ROOT_PAGE_ID,
                0,
                [
                    'You are on page Fluid Root Page',
                    'This is a standard page with no content.',
                    'page-layout-identifier-Standard',
                ],
            ],
            'standard layout on root page, FR' => [
                self::ROOT_PAGE_ID,
                1,
                [
                    'Vous êtes à la page Fluid Root Page FR',
                ],
            ],
            'special layout on root page' => [
                self::SPECIAL_PAGE_ID,
                0,
                [
                    'This is a special page with no content.',
                    'page-layout-identifier-special_layout',
                ],
            ],
        ];
    }

    #[Test]
    #[DataProvider('renderWorksWithPlainRenderingInMultipleLanguagesDataProvider')]
    public function renderWorksWithPlainRenderingInMultipleLanguages(int $pageUid, int $languageId, array $contentMatches): void
    {
        $this->setUpFrontendRootPage(
            $pageUid,
            [
                'EXT:frontend/Tests/Functional/Fixtures/Extensions/test_fluidpagerendering/Configuration/TypoScript/plain.typoscript',
            ]
        );
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId($pageUid)->withLanguageId($languageId));
        $body = (string)$response->getBody();
        foreach ($contentMatches as $match) {
            self::assertStringContainsString($match, $body);
        }
    }

    #[Test]
    public function invalidPathThrowsException(): void
    {
        $this->setUpFrontendRootPage(
            self::ROOT_PAGE_ID,
            [
                'EXT:frontend/Tests/Functional/Fixtures/Extensions/test_fluidpagerendering/Configuration/TypoScript/invalidPath.typoscript',
            ]
        );
        $this->expectException(InvalidTemplateResourceException::class);
        $this->expectExceptionMessage(sprintf('PAGEVIEW TypoScript object: Failed to resolve the expected template file "Pages/Standard.html" for layout "Standard". See also: %s. The following paths were checked: EXT:test_fluidpagerendering/Resources/Private/Templates/Pages/Pages/Standard.html', (new Typo3Information())->getDocsLink('t3tsref:cobj-pageview')));
        $this->executeFrontendSubRequest((new InternalRequest())->withPageId(self::ROOT_PAGE_ID));
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
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1711748615);
        $this->expectExceptionMessage(sprintf('Cannot use reserved name "%s" as variable name in PAGEVIEW.', $variableName));
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
