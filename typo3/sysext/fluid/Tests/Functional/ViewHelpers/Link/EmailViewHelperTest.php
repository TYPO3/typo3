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
        $view->setTemplateSource('<f:link.email email="foo@example.com">send mail</f:link.email>');
        self::assertEquals('<a href="mailto:foo@example.com">send mail</a>', $view->render());
    }

    /**
     * @test
     */
    public function renderCreatesProperMarkupInBackendWithEmptyChild(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $view = new StandaloneView();
        $view->setTemplateSource('<f:link.email email="foo@example.com" />');
        self::assertEquals('<a href="mailto:foo@example.com">foo@example.com</a>', $view->render());
    }

    public function renderEncodesEmailInFrontendDataProvider(): array
    {
        return [
            'Plain email' => [
                '<f:link.email email="some@email.tld" />',
                0,
                '<a href="mailto:some@email.tld">some@email.tld</a>',
            ],
            'Plain email with spam protection' => [
                '<f:link.email email="some@email.tld" />',
                1,
                '<a href="#" data-mailto-token="nbjmup+tpnfAfnbjm/ume" data-mailto-vector="1">some(at)email.tld</a>',
            ],
            'Plain email with ascii spam protection' => [
                '<f:link.email email="some@email.tld" />',
                'ascii',
                '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;&#115;&#111;&#109;&#101;&#64;&#101;&#109;&#97;&#105;&#108;&#46;&#116;&#108;&#100;">some(at)email.tld</a>',
            ],
            'Susceptible email' => [
                '<f:link.email email="\"><script>alert(\'email\')</script>" />',
                0,
                '<a href="mailto:&quot;&gt;&lt;script&gt;alert(\'email\')&lt;/script&gt;">&quot;&gt;&lt;script&gt;alert(\'email\')&lt;/script&gt;</a>',
            ],
            'Susceptible email with spam protection' => [
                '<f:link.email email="\"><script>alert(\'email\')</script>" />',
                1,
                '<a href="#" data-mailto-token="nbjmup+&quot;&gt;&lt;tdsjqu&gt;bmfsu(\'fnbjm\')&lt;0tdsjqu&gt;" data-mailto-vector="1">&quot;&gt;&lt;script&gt;alert(\'email\')&lt;/script&gt;</a>',
            ],
            'Susceptible email with ascii spam protection' => [
                '<f:link.email email="\"><script>alert(\'email\')</script>" />',
                'ascii',
                '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;&#34;&#62;&#60;&#115;&#99;&#114;&#105;&#112;&#116;&#62;&#97;&#108;&#101;&#114;&#116;&#40;&#39;&#101;&#109;&#97;&#105;&#108;&#39;&#41;&#60;&#47;&#115;&#99;&#114;&#105;&#112;&#116;&#62;">&quot;&gt;&lt;script&gt;alert(\'email\')&lt;/script&gt;</a>',
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
