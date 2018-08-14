<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\Unit\Page;

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

use Prophecy\Argument;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageGenerator;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class PageGeneratorTest extends UnitTestCase
{
    /**
     * Tear down
     */
    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @return array
     */
    public function generateMetaTagHtmlGeneratesCorrectTagsDataProvider()
    {
        return [
            'simple meta' => [
                [
                    'author' => 'Markus Klein',
                ],
                '',
                [
                    'type' => 'name',
                    'name' => 'author',
                    'content' => 'Markus Klein'
                ]
            ],
            'httpEquivalent meta' => [
                [
                    'X-UA-Compatible' => 'IE=edge,chrome=1',
                    'X-UA-Compatible.' => ['httpEquivalent' => 1]
                ],
                'IE=edge,chrome=1',
                [
                    'type' => 'http-equiv',
                    'name' => 'X-UA-Compatible',
                    'content' => 'IE=edge,chrome=1'
                ]
            ],
            'httpEquivalent meta xhtml new notation' => [
                [
                    'X-UA-Compatible' => 'IE=edge,chrome=1',
                    'X-UA-Compatible.' => ['attribute' => 'http-equiv']
                ],
                'IE=edge,chrome=1',
                [
                    'type' => 'http-equiv',
                    'name' => 'X-UA-Compatible',
                    'content' => 'IE=edge,chrome=1'
                ]
            ],
            'refresh meta' => [
                [
                    'refresh' => '10',
                ],
                '',
                [
                    'type' => 'http-equiv',
                    'name' => 'refresh',
                    'content' => '10'
                ]
            ],
            'refresh meta new notation' => [
                [
                    'refresh' => '10',
                    'refresh.' => ['attribute' => 'http-equiv']
                ],
                '10',
                [
                    'type' => 'http-equiv',
                    'name' => 'refresh',
                    'content' => '10'
                ]
            ],
            'meta with dot' => [
                [
                    'DC.author' => 'Markus Klein',
                ],
                '',
                [
                        'type' => 'name',
                        'name' => 'DC.author',
                        'content' => 'Markus Klein'
                ]
            ],
            'meta with colon' => [
                [
                    'OG:title' => 'Magic Tests',
                ],
                '',
                [
                    'type' => 'name',
                    'name' => 'OG:title',
                    'content' => 'Magic Tests'
                ]
            ],
            'different attribute name' => [
                [
                    'og:site_title' => 'My TYPO3 site',
                    'og:site_title.' => ['attribute' => 'property'],
                ],
                'My TYPO3 site',
                [
                    'type' => 'property',
                    'name' => 'og:site_title',
                    'content' => 'My TYPO3 site'
                ]
            ],
            'meta with 0 value' => [
                [
                    'custom:key' => '0',
                ],
                '',
                [
                    'type' => 'name',
                    'name' => 'custom:key',
                    'content' => '0'
                ]
            ],
        ];
    }

    /**
     * @test
     */
    public function generateMetaTagExpectExceptionOnBogusTags()
    {
        $stdWrapResult = '10';

        $typoScript = [
            'refresh' => '10',
            'refresh.' => ['attribute' => 'http-equiv-new']
        ];

        $expectedTags = [
            'type' => 'http-equiv-new',
            'name' => 'refresh',
            'content' => '10'
        ];

        $cObj = $this->prophesize(ContentObjectRenderer::class);
        $cObj->cObjGet(Argument::cetera())->shouldBeCalled();
        $cObj->stdWrap(Argument::cetera())->willReturn($stdWrapResult);
        $tmpl = $this->prophesize(TemplateService::class);
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $tsfe->generatePageTitle()->willReturn('');
        $tsfe->INTincScript_loadJSCode()->shouldBeCalled();
        $tsfe->cObj = $cObj->reveal();
        $tsfe->tmpl = $tmpl->reveal();
        $tsfe->page = [
            'title' => ''
        ];
        $tsfe->pSetup = [
            'meta.' => $typoScript
        ];
        $GLOBALS['TSFE'] = $tsfe->reveal();

        $pageRendererProphecy = $this->prophesize(PageRenderer::class);
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRendererProphecy->reveal());

        PageGenerator::renderContentWithHeader('');

        $pageRendererProphecy->setMetaTag($expectedTags['type'], $expectedTags['name'], $expectedTags['content'])->willThrow(\InvalidArgumentException::class);
    }

    /**
     * @test
     * @dataProvider generateMetaTagHtmlGeneratesCorrectTagsDataProvider
     *
     * @param array $typoScript
     * @param string $stdWrapResult
     * @param array $expectedTags
     */
    public function generateMetaTagHtmlGeneratesCorrectTags(array $typoScript, string $stdWrapResult, array $expectedTags)
    {
        $cObj = $this->prophesize(ContentObjectRenderer::class);
        $cObj->cObjGet(Argument::cetera())->shouldBeCalled();
        $cObj->stdWrap(Argument::cetera())->willReturn($stdWrapResult);
        $tmpl = $this->prophesize(TemplateService::class);
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $tsfe->generatePageTitle()->willReturn('');
        $tsfe->INTincScript_loadJSCode()->shouldBeCalled();
        $tsfe->cObj = $cObj->reveal();
        $tsfe->tmpl = $tmpl->reveal();
        $tsfe->config = [
            'config' => [],
        ];
        $tsfe->page = [
            'title' => ''
        ];
        $tsfe->pSetup = [
            'meta.' => $typoScript
        ];
        $GLOBALS['TSFE'] = $tsfe->reveal();

        $pageRendererProphecy = $this->prophesize(PageRenderer::class);
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRendererProphecy->reveal());

        PageGenerator::renderContentWithHeader('');

        $pageRendererProphecy->setMetaTag($expectedTags['type'], $expectedTags['name'], $expectedTags['content'], [], false)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function generateMetaTagHtmlGenerateNoTagWithEmptyContent()
    {
        $stdWrapResult = '';

        $typoScript = [
            'custom:key' => '',
        ];

        $cObj = $this->prophesize(ContentObjectRenderer::class);
        $cObj->cObjGet(Argument::cetera())->shouldBeCalled();
        $cObj->stdWrap(Argument::cetera())->willReturn($stdWrapResult);
        $tmpl = $this->prophesize(TemplateService::class);
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $tsfe->generatePageTitle()->willReturn('');
        $tsfe->INTincScript_loadJSCode()->shouldBeCalled();
        $tsfe->cObj = $cObj->reveal();
        $tsfe->tmpl = $tmpl->reveal();
        $tsfe->config = [
            'config' => [],
        ];
        $tsfe->page = [
            'title' => ''
        ];
        $tsfe->pSetup = [
            'meta.' => $typoScript
        ];
        $GLOBALS['TSFE'] = $tsfe->reveal();

        $pageRendererProphecy = $this->prophesize(PageRenderer::class);
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRendererProphecy->reveal());

        PageGenerator::renderContentWithHeader('');

        $pageRendererProphecy->setMetaTag(null, null, null)->shouldNotBeCalled();
    }

    public function generateMultipleMetaTagsDataProvider()
    {
        return [
            'multi value attribute name' => [
                [
                    'og:locale:alternate.' => [
                        'attribute' => 'property',
                        'value' => [
                            10 => 'nl_NL',
                            20 => 'de_DE',
                        ]
                    ],
                ],
                '',
                [
                    [
                        'type' => 'property',
                        'name' => 'og:locale:alternate',
                        'content' => 'nl_NL'
                    ],
                    [
                        'type' => 'property',
                        'name' => 'og:locale:alternate',
                        'content' => 'de_DE'
                    ]
                ]
            ],
            'multi value attribute name (empty values are skipped)' => [
                [
                    'og:locale:alternate.' => [
                        'attribute' => 'property',
                        'value' => [
                            10 => 'nl_NL',
                            20 => '',
                            30 => 'de_DE',
                        ]
                    ],
                ],
                '',
                [
                    [
                        'type' => 'property',
                        'name' => 'og:locale:alternate',
                        'content' => 'nl_NL'
                    ],
                    [
                        'type' => 'property',
                        'name' => 'og:locale:alternate',
                        'content' => 'de_DE'
                    ]
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider generateMultipleMetaTagsDataProvider
     *
     * @param array $typoScript
     * @param string $stdWrapResult
     * @param array $expectedTags
     */
    public function generateMultipleMetaTags(array $typoScript, string $stdWrapResult, array $expectedTags)
    {
        $cObj = $this->prophesize(ContentObjectRenderer::class);
        $cObj->cObjGet(Argument::cetera())->shouldBeCalled();
        $cObj->stdWrap(Argument::cetera())->willReturn($stdWrapResult);
        $tmpl = $this->prophesize(TemplateService::class);
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $tsfe->generatePageTitle()->willReturn('');
        $tsfe->INTincScript_loadJSCode()->shouldBeCalled();
        $tsfe->cObj = $cObj->reveal();
        $tsfe->tmpl = $tmpl->reveal();
        $tsfe->config = [
            'config' => [],
        ];
        $tsfe->page = [
            'title' => ''
        ];
        $tsfe->pSetup = [
            'meta.' => $typoScript
        ];
        $GLOBALS['TSFE'] = $tsfe->reveal();

        $pageRendererProphecy = $this->prophesize(PageRenderer::class);
        GeneralUtility::setSingletonInstance(PageRenderer::class, $pageRendererProphecy->reveal());

        PageGenerator::renderContentWithHeader('');

        $pageRendererProphecy->setMetaTag($expectedTags[0]['type'], $expectedTags[0]['name'], $expectedTags[0]['content'], [], false)->shouldHaveBeenCalled();
        $pageRendererProphecy->setMetaTag($expectedTags[1]['type'], $expectedTags[1]['name'], $expectedTags[1]['content'], [], false)->shouldHaveBeenCalled();
    }
}
