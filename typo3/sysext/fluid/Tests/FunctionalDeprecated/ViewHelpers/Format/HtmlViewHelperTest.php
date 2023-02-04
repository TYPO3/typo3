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

namespace TYPO3\CMS\Fluid\Tests\FunctionalDeprecated\ViewHelpers\Format;

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
            'explicitly empty parseFunc path' => [
                '<f:format.html parseFuncTSPath="">TYPO3 is a cool CMS</f:format.html>',
                'TYPO3 is a cool CMS',
            ],
            'non-existing parseFunc path' => [
                '<f:format.html parseFuncTSPath="null.this.does.not.exist">TYPO3 is a cool CMS</f:format.html>',
                'TYPO3 is a cool CMS',
            ],
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
