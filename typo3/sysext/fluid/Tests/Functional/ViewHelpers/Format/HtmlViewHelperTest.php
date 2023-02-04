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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Format;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class HtmlViewHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var array Used by buildDefaultLanguageConfiguration() of SiteBasedTestTrait
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    public function contentIsRenderedDataProvider(): array
    {
        return [
            'format.html: process lib.parseFunc_RTE by default' => [
                '<f:format.html>###PROJECT### is a cool CMS</f:format.html>',
                'TYPO3 is a cool CMS',
            ],
            'format.html: process inline notation with undefined variable returns empty string' => [
                '{undefinedVariable -> f:format.html()}',
                '',
            ],
            'format.html: process specific TS path' => [
                '<f:format.html parseFuncTSPath="lib.foo">###FOO### is BAR</f:format.html>',
                'BAR is BAR',
            ],
            // table attribute is hard to test. It was only used as parent for CONTENT and RECORD cObj.
            // Further the table will be used in FILES cObj as fallback, if a table was not given in references array.
        ];
    }

    /**
     * @test
     * @dataProvider contentIsRenderedDataProvider
     */
    public function contentIsRendered(string $fluidTemplateSource, string $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
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
                    'clear' => 1,
                    'config' => <<<EOT
constants.PROJECT = TYPO3
constants.FOO = BAR
lib.parseFunc_RTE {
    htmlSanitize = 1
    constants = 1
}
lib.foo {
    htmlSanitize = 1
    constants = 1
}
lib.inventor {
    htmlSanitize = 1
    plainTextStdWrap.noTrimWrap = || |
    plainTextStdWrap.dataWrap = |{CURRENT:1}
}
lib.record {
    htmlSanitize = 1
    plainTextStdWrap.noTrimWrap = || |
    plainTextStdWrap.dataWrap = |{FIELD:title}
}
lib.news {
    htmlSanitize = 1
    constants = 1
    plainTextStdWrap.noTrimWrap = || |
    plainTextStdWrap.dataWrap = |{CURRENT:1}
}
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
