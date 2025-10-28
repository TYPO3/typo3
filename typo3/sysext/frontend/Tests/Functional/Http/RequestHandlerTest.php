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

namespace TYPO3\CMS\Frontend\Tests\Functional\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RequestHandlerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    public function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'RequestHandlerTest',
            $this->buildSiteConfiguration(1, '/'),
        );
    }

    public static function generateHtmlTagUsingTypoScriptDataProvider(): array
    {
        return [
            'empty TypoScript config.' => [
                [
                    'page = PAGE',
                ], // 'config {}' section of TypoScript, one row per line
                '<html lang="en-US">',
            ],
            'disable all attributes' => [
                [
                    'page = PAGE',
                    'config.htmlTag_setParams = none',
                ],
                '<html>',
            ],
            'htmlTag_setParams string' => [
                [
                    'page = PAGE',
                    'config.htmlTag_setParams = foo="bar"',
                ],
                '<html foo="bar">',
            ],
            'attributes property trumps htmlTag_setParams' => [
                [
                    'page = PAGE',
                    'config.htmlTag_setParams = none',
                    'config.htmlTag.attributes.foo = bar',
                ],
                '<html lang="en-US" foo="bar">',
            ],
            'attributes property with mixed values' => [
                [
                    'page = PAGE',
                    'config.htmlTag.attributes.foo = bar',
                    'config.htmlTag.attributes.no-js = true',
                    'config.htmlTag.attributes.additional-enabled = 0',
                ],
                '<html lang="en-US" foo="bar" no-js="true" additional-enabled="0">',
            ],
            'attributes using stdWrap' => [
                [
                    'page = PAGE',
                    'config.htmlTag.attributes.someStdWrap = someDefault',
                    'config.htmlTag.attributes.someStdWrap.override = someOverride',
                    'config.htmlTag.attributes.noDefault.override = noDefault',
                ],
                '<html lang="en-US" someStdWrap="someOverride" noDefault="noDefault">',
            ],
        ];
    }

    #[DataProvider('generateHtmlTagUsingTypoScriptDataProvider')]
    #[Test]
    public function generateHtmlTagUsingTypoScript(array $typoScriptSetupConfig, string $expectedResult): void
    {
        $this->setUpFrontendRootPage(1, [], ['config' => implode("\n", $typoScriptSetupConfig)]);
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://website.local/'))->withPageId(1));
        self::assertStringContainsString($expectedResult, (string)$response->getBody());
    }

    #[Test]
    public function generateMetaTagUsingTypoScriptThrowsOnInvalidTag(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1496402460);
        $this->setUpFrontendRootPage(1, [], ['config' => implode("\n", ['page = PAGE', 'page.meta.refresh = 10', 'page.meta.refresh.attribute=this-is-invalid'])]);
        $this->executeFrontendSubRequest((new InternalRequest('https://website.local/'))->withPageId(1));
    }

    public static function generateMetaTagUsingTypoScriptDataProvider(): array
    {
        return [
            'simple author' => [
                [
                    'page = PAGE',
                    'page.meta.author = Nobody expects the spanish inquisition!',
                ],
                ['<meta name="author" content="Nobody expects the spanish inquisition!">'],
            ],
            'meta http-equiv' => [
                [
                    'page = PAGE',
                    'page.meta.X-UA-Compatible = IE=edge,chrome=1',
                    'page.meta.X-UA-Compatible.httpEquivalent = 1',
                ],
                ['<meta http-equiv="x-ua-compatible" content="IE=edge,chrome=1">'],
            ],
            'meta http-equiv new notation' => [
                [
                    'page = PAGE',
                    'page.meta.X-UA-Compatible = IE=edge,chrome=1',
                    'page.meta.X-UA-Compatible.attribute = http-equiv',
                ],
                ['<meta http-equiv="x-ua-compatible" content="IE=edge,chrome=1">'],
            ],
            'meta refresh' => [
                [
                    'page = PAGE',
                    'page.meta.refresh = 10',
                ],
                ['<meta http-equiv="refresh" content="10">'],
            ],
            'meta refresh new notation' => [
                [
                    'page = PAGE',
                    'page.meta.refresh = 10',
                    'page.meta.refresh.attribute = http-equiv',
                ],
                ['<meta http-equiv="refresh" content="10">'],
            ],
            'meta with quoted dot' => [
                [
                    'page = PAGE',
                    'page.meta.DC\.author = Nobody expects the spanish inquisition!',
                ],
                ['<meta name="dc.author" content="Nobody expects the spanish inquisition!">'],
            ],
            'meta with colon' => [
                [
                    'page = PAGE',
                    'page.meta.OG:title = My title',
                ],
                ['<meta name="og:title" content="My title">'],
            ],
            'different attribute name' => [
                [
                    'page = PAGE',
                    'page.meta.OG:site_title = My site title',
                    'page.meta.OG:site_title.attribute = property',
                ],
                ['<meta property="og:site_title" content="My site title">'],
            ],
            'zero value' => [
                [
                    'page = PAGE',
                    'page.meta.custom:key = 0',
                ],
                ['<meta name="custom:key" content="0">'],
            ],
            'multi value attribute name' => [
                [
                    'page = PAGE',
                    'page.meta.og:locale:alternate.attribute = property',
                    'page.meta.og:locale:alternate.value.10 = nl_NL',
                    'page.meta.og:locale:alternate.value.20 = de_DE',
                ],
                [
                    '<meta property="og:locale:alternate" content="nl_NL">',
                    '<meta property="og:locale:alternate" content="de_DE">',
                ],
            ],
            'multi value attribute name skips empty value' => [
                [
                    'page = PAGE',
                    'page.meta.og:locale:alternate.attribute = property',
                    'page.meta.og:locale:alternate.value.10 = nl_NL',
                    'page.meta.og:locale:alternate.value.20 = ',
                    'page.meta.og:locale:alternate.value.30 = de_DE',
                ],
                [
                    '<meta property="og:locale:alternate" content="nl_NL">',
                    '<meta property="og:locale:alternate" content="de_DE">',
                ],
            ],
        ];
    }

    #[DataProvider('generateMetaTagUsingTypoScriptDataProvider')]
    #[Test]
    public function generateMetaTagUsingTypoScript(array $typoScriptSetup, array $expectedResults): void
    {
        $this->setUpFrontendRootPage(1, [], ['config' => implode("\n", $typoScriptSetup)]);
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://website.local/'))->withPageId(1));
        $body = (string)$response->getBody();
        foreach ($expectedResults as $expectedResult) {
            self::assertStringContainsString($expectedResult, $body);
        }
    }

    #[Test]
    public function generateMetaTagUsingTypoScriptGeneratesNoTagWithEmptyContent(): void
    {
        $this->setUpFrontendRootPage(1, [], ['config' => implode("\n", ['page = PAGE', 'page.meta.refresh = '])]);
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://website.local/'))->withPageId(1));
        self::assertStringNotContainsString('refresh', (string)$response->getBody());
    }
}
