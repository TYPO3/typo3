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

use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TypolinkViewHelperTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var array
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected $configurationToUseInTestInstance = [
        'FE' => [
            'encryptionKey' => '12345'
        ]
    ];

    /**
     * @var array
     */
    protected $testExtensionsToLoad = ['typo3/sysext/fluid/Tests/Functional/Fixtures/Extensions/fluid_test'];

    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['fluid'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->importDataSet('typo3/sysext/fluid/Tests/Functional/ViewHelpers/Fixtures/pages.xml');
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ],
            [
                $this->buildErrorHandlingConfiguration('Fluid', [404])
            ]
        );
        $_GET = [
            'foo' => 'bar',
            'temp' => 'test',
        ];
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/';
        GeneralUtility::flushInternalRuntimeCaches();
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
        self::assertEquals($expected, trim(preg_replace('/\s+/', ' ', $view->render())));
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
                'expected' => '<a href="/en/">This is a testlink</a> <a href="/en/">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'link: with add query string' => [
                'parameter' => 1,
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => '<a href="/en/?foo=bar&amp;temp=test&amp;cHash=286759dfcd3f566fa21091a0d77e9831">This is a testlink</a> <a href="/en/">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'link: with add query string and exclude' => [
                'parameter' => 1,
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => 'temp',
                'expected' => '<a href="/en/?foo=bar&amp;cHash=afa4b37588ab917af3cfe2cd4464029d">This is a testlink</a> <a href="/en/">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'uri: default' => [
                'parameter' => 1,
                'addQueryString' => false,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => '/en/ /en/',
                'template' => 'uri_typolink_viewhelper',
            ],
            'uri: with add query string' => [
                'parameter' => 1,
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => '/en/?foo=bar&amp;temp=test&amp;cHash=286759dfcd3f566fa21091a0d77e9831 /en/',
                'template' => 'uri_typolink_viewhelper',
            ],
            'uri: with add query string and exclude' => [
                'parameter' => 1,
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => 'temp',
                'expected' => '/en/?foo=bar&amp;cHash=afa4b37588ab917af3cfe2cd4464029d /en/',
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

            'page with complex title and extended parameters' => [
                'parameter' => '1 - - "a \\"link\\" title with \\\\" &x=y',
                'addQueryString' => false,
                'addQueryStringMethod' => '',
                'addQueryStringExclude' => '',
                'expected' => '<a href="/en/?x=y&amp;cHash=fcdb7fbded8dc9d683ea83aee9909d99" title="a &quot;link&quot; title with \">This is a testlink</a> <a href="/en/?x=y&amp;cHash=fcdb7fbded8dc9d683ea83aee9909d99" title="a &quot;link&quot; title with \">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'full parameter usage' => [
                '1 _blank css-class "testtitle with whitespace" &X=y',
                'addQueryString' => false,
                'addQueryStringMethod' => '',
                'addQueryStringExclude' => '',
                'expected' => '<a href="/en/?X=y&amp;cHash=b8582914879e1ee43c72a4d26e4a4d98" title="testtitle with whitespace" target="_blank" class="css-class">This is a testlink</a> <a href="/en/?X=y&amp;cHash=b8582914879e1ee43c72a4d26e4a4d98" title="testtitle with whitespace" target="_blank" class="css-class">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            't3:// with extended class' => [
                't3://url?url=https://example.org?param=1&other=dude - css-class',
                'addQueryString' => false,
                'addQueryStringMethod' => '',
                'addQueryStringExclude' => '',
                'expected' => '<a href="https://example.org?param=1" class="css-class">This is a testlink</a> <a href="https://example.org?param=1" class="css-class">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            't3:// with complex title and extended parameters' => [
                't3://url?url=https://example.org?param=1&other=dude - - "a \\"link\\" title with \\\\" &x=y',
                'addQueryString' => false,
                'addQueryStringMethod' => '',
                'addQueryStringExclude' => '',
                'expected' => '<a href="https://example.org?param=1" title="a &quot;link&quot; title with \">This is a testlink</a> <a href="https://example.org?param=1" title="a &quot;link&quot; title with \">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            't3:// with complex title and extended parameters & correctly encoded other parameter' => [
                't3://url?url=https://example.org?param=1%26other=dude - - "a \\"link\\" title with \\\\" &x=y',
                'addQueryString' => false,
                'addQueryStringMethod' => '',
                'addQueryStringExclude' => '',
                'expected' => '<a href="https://example.org?param=1&amp;other=dude" title="a &quot;link&quot; title with \">This is a testlink</a> <a href="https://example.org?param=1&amp;other=dude" title="a &quot;link&quot; title with \">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
        ];
    }
}
