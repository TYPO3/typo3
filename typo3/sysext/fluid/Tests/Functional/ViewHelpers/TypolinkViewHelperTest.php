<?php
declare(strict_types=1);
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
        bool $addQueryString,
        string $addQueryStringMethod,
        string $addQueryStringExclude,
        string $expected,
        string $template
    ) {
        $view = new StandaloneView();
        $view->setTemplatePathAndFilename('typo3/sysext/fluid/Tests/Functional/ViewHelpers/Fixtures/' . $template . '.html');
        $view->assignMultiple([
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
                'addQueryString' => false,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => '<a href="index.php?id=1">This is a testlink</a> <a href="index.php?id=1">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'link: with add query string' => [
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => '<a href="index.php?id=1&amp;foo=bar&amp;temp=test">This is a testlink</a> <a href="index.php?id=1">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'link: with add query string and exclude' => [
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => 'temp',
                'expected' => '<a href="index.php?id=1&amp;foo=bar">This is a testlink</a> <a href="index.php?id=1">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'link: with add query string and method POST' => [
                'addQueryString' => true,
                'addQueryStringMethod' => 'POST',
                'addQueryStringExclude' => 'temp',
                'expected' => '<a href="index.php?id=1">This is a testlink</a> <a href="index.php?id=1">This is a testlink</a>',
                'template' => 'link_typolink_viewhelper',
            ],
            'uri: default' => [
                'addQueryString' => false,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => 'index.php?id=1 index.php?id=1',
                'template' => 'uri_typolink_viewhelper',
            ],
            'uri: with add query string' => [
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => '',
                'expected' => 'index.php?id=1&amp;foo=bar&amp;temp=test index.php?id=1',
                'template' => 'uri_typolink_viewhelper',
            ],
            'uri: with add query string and exclude' => [
                'addQueryString' => true,
                'addQueryStringMethod' => 'GET',
                'addQueryStringExclude' => 'temp',
                'expected' => 'index.php?id=1&amp;foo=bar index.php?id=1',
                'template' => 'uri_typolink_viewhelper',
            ],
            'uri: with add query string and method POST' => [
                'addQueryString' => true,
                'addQueryStringMethod' => 'POST',
                'addQueryStringExclude' => 'temp',
                'expected' => 'index.php?id=1 index.php?id=1',
                'template' => 'uri_typolink_viewhelper',
            ],
        ];
    }
}
