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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ParseFuncTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected array $coreExtensionsToLoad = ['fluid_styled_content'];

    /**
     * @var array Used by buildDefaultLanguageConfiguration() of SiteBasedTestTrait
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    public static function contentIsRenderedDataProvider(): array
    {
        return [
            'parseFunc allowTags are applied' => [
                '<f:format.html parseFuncTSPath="lib.parseFunc_Custom"><p>Some <wbr> output, also <em>emphasized</em>.</p></f:format.html>',
                '<p>Some <wbr> output, also <em>emphasized</em>.</p>',
            ],
        ];
    }

    #[DataProvider('contentIsRenderedDataProvider')]
    #[Test]
    public function contentIsRendered(string $fluidTemplateSource, string $expected): void
    {
        // Test inspired by HtmlViewHelperTest
        $this->importCSVDataSet(__DIR__ . '/Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ]
        );
        $this->createTypoScriptTemplate($fluidTemplateSource);

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())->withPageId(1)
        );
        self::assertStringContainsString($expected, (string)$response->getBody());
    }

    private function createTypoScriptTemplate(string $fluidTemplateSource): void
    {
        (new ConnectionPool())->getConnectionForTable('sys_template')
            ->insert(
                'sys_template',
                [
                    'pid' => 1,
                    'root' => 1,
                    'clear' => 3,
                    'constants' => <<<EOT
@import 'EXT:fluid_styled_content/Configuration/TypoScript/constants.typoscript'
project = TYPO3
foo = BAR
EOT,
                    'config' => <<<EOT
@import 'EXT:fluid_styled_content/Configuration/TypoScript/setup.typoscript'

lib.parseFunc_Custom < lib.parseFunc_RTE
lib.parseFunc_Custom.allowTags := addToList(wbr)

page = PAGE
page.10 = FLUIDTEMPLATE
page.10 {
    template = TEXT
    template.value = $fluidTemplateSource
}
EOT
                ]
            );
    }
}
