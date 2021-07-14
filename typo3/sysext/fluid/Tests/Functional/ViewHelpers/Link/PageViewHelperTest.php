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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Link;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PageViewHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    public function renderDataProvider(): array
    {
        return [
            'renderProvidesATagForValidLinkTarget' => [
                '<f:link.page>index.php</f:link.page>',
                '<a href="/">index.php</a>',
            ],
            'renderWillProvideEmptyATagForNonValidLinkTarget' => [
                '<f:link.page></f:link.page>',
                '<a href="/"></a>',
            ],
            'link to root page' => [
                '<f:link.page pageUid="1">linkMe</f:link.page>',
                '<a href="/">linkMe</a>'
            ],
            'link to page sub page' => [
                '<f:link.page pageUid="3">linkMe</f:link.page>',
                '<a href="/dummy-1-2/dummy-1-2-3">linkMe</a>'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderDataProvider
     */
    public function render(string $template, string $expected): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
        );
        (new ConnectionPool())->getConnectionForTable('sys_template')->insert('sys_template', [
            'pid' => 1,
            'root' => 1,
            'clear' => 1,
            'config' => <<<EOT
page = PAGE
page.10 = FLUIDTEMPLATE
page.10 {
    template = TEXT
    template.value = $template
}
EOT
        ]);
        $response = $this->executeFrontendSubRequest((new InternalRequest())->withPageId(1));
        self::assertStringContainsString($expected, (string)$response->getBody());
    }
}
