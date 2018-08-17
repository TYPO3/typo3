<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\Unit\Controller;

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

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TypoScriptFrontendControllerTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\TYPO3\TestingFramework\Core\AccessibleObjectInterface|TypoScriptFrontendController
     */
    protected $subject;

    protected function setUp()
    {
        GeneralUtility::flushInternalRuntimeCaches();
        $this->subject = $this->getAccessibleMock(TypoScriptFrontendController::class, ['dummy'], [], '', false);
        $this->subject->_set('context', new Context());
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '170928423746123078941623042360abceb12341234231';

        $pageRepository = $this->getMockBuilder(PageRepository::class)->getMock();
        $this->subject->sys_page = $pageRepository;

        $pageRenderer = $this->getMockBuilder(PageRenderer::class)->getMock();
        $this->subject->_set('pageRenderer', $pageRenderer);
    }

    /**
     * Tests concerning rendering content
     */

    /**
     * @test
     */
    public function headerAndFooterMarkersAreReplacedDuringIntProcessing()
    {
        $GLOBALS['TSFE'] = $this->setupTsfeMockForHeaderFooterReplacementCheck();
        $GLOBALS['TSFE']->INTincScript();
        $this->assertContains('headerData', $GLOBALS['TSFE']->content);
        $this->assertContains('footerData', $GLOBALS['TSFE']->content);
    }

    /**
     * This is the callback that mimics a USER_INT extension
     */
    public function INTincScript_processCallback()
    {
        $GLOBALS['TSFE']->additionalHeaderData[] = 'headerData';
        $GLOBALS['TSFE']->additionalFooterData[] = 'footerData';
    }

    /**
     * Setup a \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController object only for testing the header and footer
     * replacement during USER_INT rendering
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|TypoScriptFrontendController
     */
    protected function setupTsfeMockForHeaderFooterReplacementCheck()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TypoScriptFrontendController $tsfe */
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setMethods([
                'INTincScript_process',
                'INTincScript_loadJSCode',
                'setAbsRefPrefix',
                'regeneratePageTitle'
            ])->disableOriginalConstructor()
            ->getMock();
        $tsfe->expects($this->exactly(2))->method('INTincScript_process')->will($this->returnCallback([$this, 'INTincScript_processCallback']));
        $tsfe->content = file_get_contents(__DIR__ . '/Fixtures/renderedPage.html');
        $config = [
            'INTincScript_ext' => [
                'divKey' => '679b52796e75d474ccbbed486b6837ab',
            ],
            'INTincScript' => [
                'INT_SCRIPT.679b52796e75d474ccbbed486b6837ab' => [],
            ]
        ];
        $tsfe->config = $config;

        return $tsfe;
    }

    /**
     * Tests concerning sL
     */

    /**
     * @test
     */
    public function localizationReturnsUnchangedStringIfNotLocallangLabel()
    {
        $string = $this->getUniqueId();
        $this->subject->page = [];
        $this->subject->settingLanguage();
        $this->assertEquals($string, $this->subject->sL($string));
    }

    /**
     * Tests concerning getSysDomainCache
     */

    /**
     * @return array
     */
    public function getSysDomainCacheDataProvider()
    {
        return [
            'typo3.org' => [
                'typo3.org',
            ],
            'foo.bar' => [
                'foo.bar',
            ],
            'example.com' => [
                'example.com',
            ],
        ];
    }

    /**
     * @return array
     */
    public function baseUrlWrapHandlesDifferentUrlsDataProvider()
    {
        return [
            'without base url' => [
                '',
                'fileadmin/user_uploads/image.jpg',
                'fileadmin/user_uploads/image.jpg'
            ],
            'with base url' => [
                'http://www.google.com/',
                'fileadmin/user_uploads/image.jpg',
                'http://www.google.com/fileadmin/user_uploads/image.jpg'
            ],
            'without base url but with url prepended with a forward slash' => [
                '',
                '/fileadmin/user_uploads/image.jpg',
                '/fileadmin/user_uploads/image.jpg',
            ],
            'with base url but with url prepended with a forward slash' => [
                'http://www.google.com/',
                '/fileadmin/user_uploads/image.jpg',
                '/fileadmin/user_uploads/image.jpg',
            ],
        ];
    }

    /**
     * @dataProvider baseUrlWrapHandlesDifferentUrlsDataProvider
     * @test
     * @param string $baseUrl
     * @param string $url
     * @param string $expected
     */
    public function baseUrlWrapHandlesDifferentUrls($baseUrl, $url, $expected)
    {
        $this->subject->baseUrl = $baseUrl;
        $this->assertSame($expected, $this->subject->baseUrlWrap($url));
    }

    /**
     * @return array
     */
    public function initializeSearchWordDataInTsfeBuildsCorrectRegexDataProvider()
    {
        return [
            'one simple search word' => [
                ['test'],
                false,
                'test',
            ],
            'one simple search word with standalone words' => [
                ['test'],
                true,
                '[[:space:]]test[[:space:]]',
            ],
            'two simple search words' => [
                ['test', 'test2'],
                false,
                'test|test2',
            ],
            'two simple search words with standalone words' => [
                ['test', 'test2'],
                true,
                '[[:space:]]test[[:space:]]|[[:space:]]test2[[:space:]]',
            ],
            'word with regex chars' => [
                ['A \\ word with / a bunch of [] regex () chars .*'],
                false,
                'A \\\\ word with \\/ a bunch of \\[\\] regex \\(\\) chars \\.\\*',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider initializeSearchWordDataInTsfeBuildsCorrectRegexDataProvider
     *
     * @param array $searchWordGetParameters The values that should be loaded in the sword_list GET parameter.
     * @param bool $enableStandaloneSearchWords If TRUE the sword_standAlone option will be enabled.
     * @param string $expectedRegex The expected regex after processing the search words.
     */
    public function initializeSearchWordDataInTsfeBuildsCorrectRegex(array $searchWordGetParameters, $enableStandaloneSearchWords, $expectedRegex)
    {
        $_GET['sword_list'] = $searchWordGetParameters;

        $this->subject->page = [];
        if ($enableStandaloneSearchWords) {
            $this->subject->config = ['config' => ['sword_standAlone' => 1]];
        }

        $this->subject->preparePageContentGeneration();
        $this->assertEquals($this->subject->sWordRegEx, $expectedRegex);
    }

    /**
     * @test
     * @dataProvider splitLinkVarsDataProvider
     *
     * @param $string
     * @param $expected
     */
    public function splitLinkVarsStringSplitsStringByComma($string, $expected)
    {
        $this->assertEquals($expected, $this->subject->_callRef('splitLinkVarsString', $string));
    }

    /**
     * @return array
     */
    public function splitLinkVarsDataProvider()
    {
        return [
            [
                'L',
                ['L']
            ],
            [
                'L,a',
                [
                    'L',
                    'a'
                ]
            ],
            [
                'L, a',
                [
                    'L',
                    'a'
                ]
            ],
            [
                'L , a',
                [
                    'L',
                    'a'
                ]
            ],
            [
                ' L, a ',
                [
                    'L',
                    'a'
                ]
            ],
            [
                'L(1)',
                [
                    'L(1)'
                ]
            ],
            [
                'L(1),a',
                [
                    'L(1)',
                    'a'
                ]
            ],
            [
                'L(1) ,  a',
                [
                    'L(1)',
                    'a'
                ]
            ],
            [
                'a,L(1)',
                [
                    'a',
                    'L(1)'
                ]
            ],
            [
                'L(1),a(2-3)',
                [
                    'L(1)',
                    'a(2-3)'
                ]
            ],
            [
                'L(1),a((2-3))',
                [
                    'L(1)',
                    'a((2-3))'
                ]
            ],
            [
                'L(1),a(a{2,4})',
                [
                    'L(1)',
                    'a(a{2,4})'
                ]
            ],
            [
                'L(1),a(/a{2,4}\,()/)',
                [
                    'L(1)',
                    'a(/a{2,4}\,()/)'
                ]
            ],
            [
                'L,a , b(c) , dd(/g{1,2}/), eee(, ()f) , 2',
                [
                    'L',
                    'a',
                    'b(c)',
                    'dd(/g{1,2}/)',
                    'eee(, ()f)',
                    '2'
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider calculateLinkVarsDataProvider
     * @param string $linkVars
     * @param array $getVars
     * @param string $expected
     */
    public function calculateLinkVarsConsidersCorrectVariables(string $linkVars, array $getVars, string $expected)
    {
        $_GET = $getVars;
        $this->subject->config['config']['linkVars'] = $linkVars;
        $this->subject->calculateLinkVars();
        $this->assertEquals($expected, $this->subject->linkVars);
    }

    public function calculateLinkVarsDataProvider(): array
    {
        return [
            'simple variable' => [
                'L',
                [
                    'L' => 1
                ],
                '&L=1'
            ],
            'missing variable' => [
                'L',
                [
                ],
                ''
            ],
            'restricted variables' => [
                'L(1-3),bar(3),foo(array),blub(array)',
                [
                    'L' => 1,
                    'bar' => 2,
                    'foo' => [ 1, 2, 'f' => [ 4, 5 ] ],
                    'blub' => 123
                ],
                '&L=1&foo[0]=1&foo[1]=2&foo[f][0]=4&foo[f][1]=5'
            ],
            'nested variables' => [
                'bar|foo(1-2)',
                [
                    'bar' => [
                        'foo' => 1,
                        'unused' => 'never'
                    ]
                ],
                '&bar[foo]=1'
            ],
        ];
    }
}
