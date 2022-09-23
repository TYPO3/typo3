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

class TypolinkViewHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var array Used by buildDefaultLanguageConfiguration() of SiteBasedTestTrait
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    public function renderDataProvider(): array
    {
        return [
            'link: default' => [
                '<f:link.typolink parameter="1">This is a testlink</f:link.typolink>',
                '<a href="/en/">This is a testlink</a>',
            ],
            'link: with add query string' => [
                '<f:link.typolink parameter="1" addQueryString="untrusted">This is a testlink</f:link.typolink>',
                '<a href="/en/?foo=bar&amp;temp=test&amp;cHash=286759dfcd3f566fa21091a0d77e9831">This is a testlink</a>',
            ],
            'link: with add query string and exclude' => [
                '<f:link.typolink parameter="1" addQueryString="untrusted" addQueryStringExclude="temp">This is a testlink</f:link.typolink>',
                '<a href="/en/?foo=bar&amp;cHash=afa4b37588ab917af3cfe2cd4464029d">This is a testlink</a>',
            ],
            't3://url link: default' => [
                '<f:link.typolink parameter="t3://url?url=https://example.org?param=1&other=dude">This is a testlink</f:link.typolink>',
                '<a href="https://example.org?param=1">This is a testlink</a>',
            ],
            't3://url link: with add query string' => [
                '<f:link.typolink parameter="t3://url?url=https://example.org?param=1&other=dude" addQueryString="untrusted">This is a testlink</f:link.typolink>',
                '<a href="https://example.org?param=1">This is a testlink</a>',
            ],
            't3://url link: with add query string and exclude' => [
                '<f:link.typolink parameter="t3://url?url=https://example.org?param=1&other=dude" addQueryString="untrusted" addQueryStringExclude="temp">This is a testlink</f:link.typolink>',
                '<a href="https://example.org?param=1">This is a testlink</a>',
            ],
            'mailto: link: default' => [
                '<f:link.typolink parameter="mailto:foo@typo3.org">This is a testlink</f:link.typolink>',
                '<a href="mailto:foo@typo3.org">This is a testlink</a>',
            ],
            'mailto: link: with add query string' => [
                '<f:link.typolink parameter="mailto:foo@typo3.org" addQueryString="untrusted">This is a testlink</f:link.typolink>',
                '<a href="mailto:foo@typo3.org">This is a testlink</a>',
            ],
            'mailto: link: with add query string and exclude' => [
                '<f:link.typolink parameter="mailto:foo@typo3.org" addQueryString="untrusted" addQueryStringExclude="temp">This is a testlink</f:link.typolink>',
                '<a href="mailto:foo@typo3.org">This is a testlink</a>',
            ],
            'http://: link: default' => [
                '<f:link.typolink parameter="http://typo3.org/foo/?foo=bar">This is a testlink</f:link.typolink>',
                '<a href="http://typo3.org/foo/?foo=bar">This is a testlink</a>',
            ],
            'http://: link: with add query string' => [
                '<f:link.typolink parameter="http://typo3.org/foo/?foo=bar" addQueryString="untrusted">This is a testlink</f:link.typolink>',
                '<a href="http://typo3.org/foo/?foo=bar">This is a testlink</a>',
            ],
            'http://: link: with add query string and exclude' => [
                '<f:link.typolink parameter="http://typo3.org/foo/?foo=bar" addQueryString="untrusted" addQueryStringExclude="temp">This is a testlink</f:link.typolink>',
                '<a href="http://typo3.org/foo/?foo=bar">This is a testlink</a>',
            ],
            'page with complex title and extended parameters' => [
                '<f:link.typolink parameter="1 - - \"a \\\"link\\\" title with \\\\\ \" &x=y">This is a testlink</f:link.typolink>',
                '<a href="/en/?x=y&amp;cHash=fcdb7fbded8dc9d683ea83aee9909d99" title="a &quot;link&quot; title with \">This is a testlink</a>',
            ],
            'full parameter usage' => [
                '<f:link.typolink parameter="1 _blank css-class \"testtitle with whitespace\" &X=y">This is a testlink</f:link.typolink>',
                '<a href="/en/?X=y&amp;cHash=b8582914879e1ee43c72a4d26e4a4d98" target="_blank" title="testtitle with whitespace" class="css-class">This is a testlink</a>',
            ],
            't3:// with extended class' => [
                '<f:link.typolink parameter="t3://url?url=https://example.org?param=1&other=dude - css-class">This is a testlink</f:link.typolink>',
                '<a href="https://example.org?param=1" class="css-class">This is a testlink</a>',
            ],
            't3:// with complex title and extended parameters' => [
                '<f:link.typolink parameter="t3://url?url=https://example.org?param=1&other=dude - - \"a \\\"link\\\" title with \\\\\ \" &x=y">This is a testlink</f:link.typolink>',
                '<a href="https://example.org?param=1" title="a &quot;link&quot; title with \">This is a testlink</a>',
            ],
            't3:// with complex title and extended parameters & correctly encoded other parameter' => [
                '<f:link.typolink parameter="t3://url?url=https://example.org?param=1%26other=dude - - \"a \\\"link\\\" title with \\\\\ \" &x=y">This is a testlink</f:link.typolink>',
                '<a href="https://example.org?param=1&amp;other=dude" title="a &quot;link&quot; title with \">This is a testlink</a>',
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
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ]
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
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest())
            ->withPageId(1)
            ->withQueryParameter('foo', 'bar')
            ->withQueryParameter('temp', 'test')
        );
        self::assertStringContainsString($expected, (string)$response->getBody());
    }

    public function renderWithAssignedParametersDataProvider(): array
    {
        return [
            'target _self' => [
                '<f:link.typolink parameter="{parameter}" parts-as="typoLinkParts">Individual {typoLinkParts.target} {typoLinkParts.class} {typoLinkParts.title}</f:link.typolink>',
                [
                    'parameter' => 'http://typo3.org/ "_self" "<CSS>" "<Title>"',
                ],
                '<a href="http://typo3.org/" target="_self" title="&lt;Title&gt;" class="&lt;CSS&gt;">Individual _self &lt;CSS&gt; &lt;Title&gt;</a>',
            ],
            'target does not point to "self", adds noreferrer relationship' => [
                '<f:link.typolink parameter="{parameter}" parts-as="typoLinkParts">Individual {typoLinkParts.target} {typoLinkParts.class} {typoLinkParts.title}</f:link.typolink>',
                [
                    'parameter' => 'http://typo3.org/ "<Target>" "<CSS>" "<Title>"',
                ],
                '<a href="http://typo3.org/" target="&lt;Target&gt;" rel="noreferrer" title="&lt;Title&gt;" class="&lt;CSS&gt;">Individual &lt;Target&gt; &lt;CSS&gt; &lt;Title&gt;</a>',
            ],
            'typoLinkAdditionalAttributesAreRendered' => [
                '<f:link.typolink parameter="{parameter}" additionalAttributes="{additionalAttributes}">Link Text</f:link.typolink>',
                [
                    'parameter' => 'http://typo3.org/ "_self" "<CSS>" "<Title>"',
                    'additionalAttributes' => [
                        'data-bs-html' => '<div data-template="template">'
                            . '<img src="logo.png" alt="&quot;&lt;ALT&gt;&quot;"></div>',
                        'data-other' => '\'\'',
                    ],
                ],
                '<a href="http://typo3.org/" target="_self"'
                    . ' data-bs-html="&lt;div data-template=&quot;template&quot;&gt;'
                    . '&lt;img src=&quot;logo.png&quot; alt=&quot;&amp;quot;&amp;lt;ALT&amp;gt;&amp;quot;&quot;&gt;&lt;/div&gt;"'
                    . ' data-other="&#039;&#039;" title="&lt;Title&gt;" class="&lt;CSS&gt;">Link Text</a>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider renderWithAssignedParametersDataProvider
     */
    public function renderWithAssignedParameters(string $template, array $assigns, string $expected): void
    {
        $context = $this->get(RenderingContextFactory::class)->create();
        $context->getTemplatePaths()->setTemplateSource($template);
        $view = new TemplateView($context);
        $view->assignMultiple($assigns);
        self::assertSame($expected, trim($view->render()));
    }
}
