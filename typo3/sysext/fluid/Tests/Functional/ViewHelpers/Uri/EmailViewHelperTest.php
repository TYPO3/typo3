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

namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers\Uri;

use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class EmailViewHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @test
     */
    public function renderCreatesProperMarkupInBackend(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $view = new StandaloneView();
        $view->setTemplateSource('<f:uri.email email="foo@example.com" />');
        self::assertEquals('mailto:foo@example.com', $view->render());
    }

    public function renderEncodesEmailInFrontendDataProvider(): array
    {
        return [
            'Plain email' => [
                '<f:uri.email email="some@email.tld" />',
                0,
                'mailto:some@email.tld',
            ],
            'Plain email with spam protection' => [
                '<f:uri.email email="some@email.tld" />',
                1,
                'javascript:linkTo_UnCryptMailto(%27nbjmup%2BtpnfAfnbjm%5C%2Fume%27);',
            ],
            'Susceptible email' => [
                '<f:uri.email email="\"><script>alert(\'email\')</script>" />',
                0,
                'mailto:&quot;&gt;&lt;script&gt;alert(&#039;email&#039;)&lt;/script&gt;',
            ],
            'Susceptible email with spam protection' => [
                '<f:uri.email email="\"><script>alert(\'email\')</script>" />',
                1,
                'javascript:linkTo_UnCryptMailto(%27nbjmup%2B%5Cu0022%5Cu003E%5Cu003Ctdsjqu%5Cu003Ebmfsu%28%5Cu0027fnbjm%5Cu0027%29%5Cu003C0tdsjqu%5Cu003E%27);',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderEncodesEmailInFrontendDataProvider
     */
    public function renderEncodesEmailInFrontend(string $template, $spamProtectEmailAddresses, string $expected): void
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
config.spamProtectEmailAddresses = $spamProtectEmailAddresses
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
