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
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Fluid\Fluid\View\TemplateView;

class EmailViewHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [];

    /**
     * @test
     */
    public function renderCreatesProperMarkupInBackend(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:link.email email="foo@example.com">send mail</f:link.email>');
        self::assertEquals('<a href="mailto:foo@example.com">send mail</a>', (new TemplateView($context))->render());
    }

    /**
     * @test
     */
    public function renderCreatesProperMarkupInBackendWithEmptyChild(): void
    {
        $context = $this->getContainer()->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource('<f:link.email email="foo@example.com" />');
        self::assertEquals('<a href="mailto:foo@example.com">foo@example.com</a>', (new TemplateView($context))->render());
    }

    public function renderEncodesEmailInFrontendDataProvider(): array
    {
        return [
            'Plain email' => [
                '<f:link.email email="some@email.tld" />',
                ['config.spamProtectEmailAddresses = 0'],
                '<a href="mailto:some@email.tld">some@email.tld</a>',
            ],
            'Plain email with spam protection' => [
                '<f:link.email email="some@email.tld" />',
                ['config.spamProtectEmailAddresses = 1'],
                '<a href="#" data-mailto-token="nbjmup+tpnfAfnbjm/ume" data-mailto-vector="1">some(at)email.tld</a>',
            ],
            'Plain email with spam protection and markup substitution' => [
                '<f:link.email email="some@email.tld" />',
                [
                    'config.spamProtectEmailAddresses = 1',
                    'config.spamProtectEmailAddresses_atSubst = <span class="at"></span>',
                    'config.spamProtectEmailAddresses_lastDotSubst = <span class="dot"></span>',
                ],
                '<a href="#" data-mailto-token="nbjmup+tpnfAfnbjm/ume" data-mailto-vector="1">some<span class="at"></span>email<span class="dot"></span>tld</a>',
            ],
            'Susceptible email' => [
                '<f:link.email email="\"><script>alert(\'email\')</script>" />',
                ['config.spamProtectEmailAddresses = 0'],
                '<a href="mailto:&quot;&gt;&lt;script&gt;alert(&#039;email&#039;)&lt;/script&gt;">&quot;&gt;&lt;script&gt;alert(&#039;email&#039;)&lt;/script&gt;</a>',
            ],
            'Susceptible email with spam protection' => [
                '<f:link.email email="\"><script>alert(\'email\')</script>" />',
                ['config.spamProtectEmailAddresses = 1'],
                '<a href="#" data-mailto-token="nbjmup+&quot;&gt;&lt;tdsjqu&gt;bmfsu(&#039;fnbjm&#039;)&lt;0tdsjqu&gt;" data-mailto-vector="1">&quot;&gt;&lt;script&gt;alert(&#039;email&#039;)&lt;/script&gt;</a>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderEncodesEmailInFrontendDataProvider
     */
    public function renderEncodesEmailInFrontend(string $template, array $typoScript, string $expected): void
    {
        $this->setUpBackendUserFromFixture(1);
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
        );
        (new ConnectionPool())->getConnectionForTable('sys_template')->insert('sys_template', [
            'pid' => 1,
            'root' => 1,
            'clear' => 1,
            'config' => implode("\n", $typoScript) . "\n" . <<<EOT
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
