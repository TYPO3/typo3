<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Fluid\Tests\Functional\ViewHelpers;

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

use TYPO3\CMS\Fluid\View\StandaloneView;

class TypolinkViewHelperTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    private const TEMPLATE_BASE_PATH = 'typo3/sysext/fluid/Tests/Functional/ViewHelpers/Fixtures/';

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/fluid_test'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['fluid'];

    protected function setUp()
    {
        parent::setUp();

        $this->importDataSet('typo3/sysext/fluid/Tests/Functional/ViewHelpers/Fixtures/pages.xml');

        $_GET = [
            'foo' => 'bar',
            'temp' => 'test',
        ];
    }

    /**
     * @param mixed $parameter
     * @param bool $addQueryString
     * @param string $addQueryStringMethod
     * @param string $addQueryStringExclude
     * @param string $expected
     * @param string $template
     *
     * @test
     * @dataProvider renderCreatesCorrectLinkProvider
     */
    public function renderCreatesCorrectLink(
        $parameter,
        bool $addQueryString,
        string $addQueryStringMethod,
        string $addQueryStringExclude,
        string $expected,
        string $template
    ) {
        $view = new StandaloneView();
        $view->setTemplatePathAndFilename('typo3/sysext/fluid/Tests/Functional/ViewHelpers/Fixtures/' . $template . '.html');
        $view->assignMultiple([
            'parameter' => $parameter,
            'uid' => 1,
            'addQueryString' => $addQueryString,
            'addQueryStringMethod' => $addQueryStringMethod,
            'addQueryStringExclude' => $addQueryStringExclude,
        ]);
        $this->assertEquals($expected, trim(preg_replace('/\s+/', ' ', $view->render())));
    }

    /**
     * @return array
     */
    public function renderCreatesCorrectLinkProvider(): array
    {
        return [
            'link: default' => [
                'parameter' => 1,
                'addQueryString' => false,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => '<a href="index.php?id=1">This is a testlink</a> <a href="index.php?id=1">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'link: with add query string' => [
                'parameter' => 1,
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => '<a href="index.php?id=1&amp;foo=bar&amp;temp=test">This is a testlink</a> <a href="index.php?id=1">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'link: with add query string and exclude' => [
                'parameter' => 1,
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => 'temp',
                'expected' => '<a href="index.php?id=1&amp;foo=bar">This is a testlink</a> <a href="index.php?id=1">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'link: with add query string and method POST' => [
                'parameter' => 1,
                'addQueryString' => true,
                'addQueryStringMethod' => 'POST',
                'addQueryStringExclude' => 'temp',
                'expected' => '<a href="index.php?id=1">This is a testlink</a> <a href="index.php?id=1">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'uri: default' => [
                'parameter' => 1,
                'addQueryString' => false,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => 'index.php?id=1 index.php?id=1',
                'template' => 'uri_typolink_viewhelper',
            ],
            'uri: with add query string' => [
                'parameter' => 1,
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => 'index.php?id=1&amp;foo=bar&amp;temp=test index.php?id=1',
                'template' => 'uri_typolink_viewhelper',
            ],
            'uri: with add query string and exclude' => [
                'parameter' => 1,
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => 'temp',
                'expected' => 'index.php?id=1&amp;foo=bar index.php?id=1',
                'template' => 'uri_typolink_viewhelper',
            ],
            'uri: with add query string and method POST' => [
                'parameter' => 1,
                'addQueryString' => true,
                'addQueryStringMethod' => 'POST',
                'addQueryStringExclude' => 'temp',
                'expected' => 'index.php?id=1 index.php?id=1',
                'template' => 'uri_typolink_viewhelper',
            ],
            't3://url link: default' => [
                'parameter' => 't3://url?url=https://example.org?param=1&other=dude',
                'addQueryString' => false,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => '<a href="https://example.org?param=1">This is a testlink</a> <a href="https://example.org?param=1">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            't3://url link: with add query string' => [
                'parameter' => 't3://url?url=https://example.org?param=1&other=dude',
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => '<a href="https://example.org?param=1">This is a testlink</a> <a href="https://example.org?param=1">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            't3://url link: with add query string and exclude' => [
                'parameter' => 't3://url?url=https://example.org?param=1&other=dude',
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => 'temp',
                'expected' => '<a href="https://example.org?param=1">This is a testlink</a> <a href="https://example.org?param=1">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            't3://url uri: default' => [
                'parameter' => 't3://url?url=https://example.org?param=1&other=dude',
                'addQueryString' => false,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => 'https://example.org?param=1 https://example.org?param=1',
                'template' => 'uri_typolink_viewhelper',
            ],
            't3://url uri: with add query string' => [
                'parameter' => 't3://url?url=https://example.org?param=1&other=dude',
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => 'https://example.org?param=1 https://example.org?param=1',
                'template' => 'uri_typolink_viewhelper',
            ],
            't3://url uri: with add query string and exclude' => [
                'parameter' => 't3://url?url=https://example.org?param=1&other=dude',
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => 'temp',
                'expected' => 'https://example.org?param=1 https://example.org?param=1',
                'template' => 'uri_typolink_viewhelper',
            ],
            'mailto: link: default' => [
                'parameter' => 'mailto:foo@typo3.org',
                'addQueryString' => false,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => '<a href="mailto:foo@typo3.org">This is a testlink</a> <a href="mailto:foo@typo3.org">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'mailto: link: with add query string' => [
                'parameter' => 'mailto:foo@typo3.org',
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => '<a href="mailto:foo@typo3.org">This is a testlink</a> <a href="mailto:foo@typo3.org">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'mailto: link: with add query string and exclude' => [
                'parameter' => 'mailto:foo@typo3.org',
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => 'temp',
                'expected' => '<a href="mailto:foo@typo3.org">This is a testlink</a> <a href="mailto:foo@typo3.org">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'mailto: uri: default' => [
                'parameter' => 'mailto:foo@typo3.org',
                'addQueryString' => false,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => 'mailto:foo@typo3.org mailto:foo@typo3.org',
                'template' => 'uri_typolink_viewhelper',
            ],
            'mailto: uri: with add query string' => [
                'parameter' => 'mailto:foo@typo3.org',
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => 'mailto:foo@typo3.org mailto:foo@typo3.org',
                'template' => 'uri_typolink_viewhelper',
            ],
            'mailto: uri: with add query string and exclude' => [
                'parameter' => 'mailto:foo@typo3.org',
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => 'temp',
                'expected' => 'mailto:foo@typo3.org mailto:foo@typo3.org',
                'template' => 'uri_typolink_viewhelper',
            ],
            'http://: link: default' => [
                'parameter' => 'http://typo3.org/foo/?foo=bar',
                'addQueryString' => false,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => '<a href="http://typo3.org/foo/?foo=bar">This is a testlink</a> <a href="http://typo3.org/foo/?foo=bar">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'http://: link: with add query string' => [
                'parameter' => 'http://typo3.org/foo/?foo=bar',
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => '<a href="http://typo3.org/foo/?foo=bar">This is a testlink</a> <a href="http://typo3.org/foo/?foo=bar">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'http://: link: with add query string and exclude' => [
                'parameter' => 'http://typo3.org/foo/?foo=bar',
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => 'temp',
                'expected' => '<a href="http://typo3.org/foo/?foo=bar">This is a testlink</a> <a href="http://typo3.org/foo/?foo=bar">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'http://: uri: default' => [
                'parameter' => 'http://typo3.org/foo/?foo=bar',
                'addQueryString' => false,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => 'http://typo3.org/foo/?foo=bar http://typo3.org/foo/?foo=bar',
                'template' => 'uri_typolink_viewhelper',
            ],
            'http://: uri: with add query string' => [
                'parameter' => 'http://typo3.org/foo/?foo=bar',
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => 'http://typo3.org/foo/?foo=bar http://typo3.org/foo/?foo=bar',
                'template' => 'uri_typolink_viewhelper',
            ],
            'http://: uri: with add query string and exclude' => [
                'parameter' => 'http://typo3.org/foo/?foo=bar',
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => 'temp',
                'expected' => 'http://typo3.org/foo/?foo=bar http://typo3.org/foo/?foo=bar',
                'template' => 'uri_typolink_viewhelper',
            ],
        ];
    }

    public function typoLinkAdditionalAttributesAreRenderedDataProvider(): array
    {
        return [
            [
                [
                    'parameter' => 'http://typo3.org/ "_self" "<CSS>" "<Title>"',
                    'additionalAttributes' => [
                        'data-html' => '<div data-template="template">'
                            . '<img src="logo.png" alt="&quot;&lt;ALT&gt;&quot;"></div>',
                        'data-other' => '\'\'',
                    ],
                ],
                '<a href="http://typo3.org/" title="&lt;Title&gt;" target="_self"'
                . ' class="&lt;CSS&gt;" data-html="&lt;div data-template=&quot;template&quot;&gt;'
                . '&lt;img src=&quot;logo.png&quot; alt=&quot;&amp;quot;&amp;lt;ALT&amp;gt;&amp;quot;&quot;&gt;&lt;/div&gt;"'
                . ' data-other="\'\'">Link Text</a>'
            ]
        ];
    }

    /**
     * @param array $configuration
     * @param string $expectation
     *
     * @test
     * @dataProvider typoLinkAdditionalAttributesAreRenderedDataProvider
     */
    public function typoLinkAdditionalAttributesAreRendered(array $configuration, string $expectation): void
    {
        $view = new StandaloneView();
        $view->setTemplatePathAndFilename(
            self::TEMPLATE_BASE_PATH . 'link_typolink_additionalAttributes.html'
        );
        $view->assignMultiple($configuration);
        self::assertSame($expectation, trim($view->render()));
    }
}
