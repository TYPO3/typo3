<?php
namespace TYPO3\CMS\Frontend\Tests\Functional\ContentObject;

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

use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Testcase for TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
 */
class ContentObjectRendererTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    /**
     * @var ContentObjectRenderer
     */
    protected $subject;

    protected function setUp()
    {
        parent::setUp();

        $typoScriptFrontendController = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            null,
            1,
            0
        );
        $typoScriptFrontendController->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $typoScriptFrontendController->tmpl = GeneralUtility::makeInstance(TemplateService::class);
        $GLOBALS['TSFE'] = $typoScriptFrontendController;

        $this->subject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
    }

    /**
     * Data provider for the getQuery test
     *
     * @return array multi-dimensional array with the second level like this:
     * @see getQuery
     */
    public function getQueryDataProvider(): array
    {
        $data = [
            'testing empty conf' => [
                'tt_content',
                [],
                [
                    'SELECT' => '*'
                ]
            ],
            'testing #17284: adding uid/pid for workspaces' => [
                'tt_content',
                [
                    'selectFields' => 'header,bodytext'
                ],
                [
                    'SELECT' => 'header,bodytext, `tt_content`.`uid` AS `uid`, `tt_content`.`pid` AS `pid`, `tt_content`.`t3ver_state` AS `t3ver_state`'
                ]
            ],
            'testing #17284: no need to add' => [
                'tt_content',
                [
                    'selectFields' => 'tt_content.*'
                ],
                [
                    'SELECT' => 'tt_content.*'
                ]
            ],
            'testing #17284: no need to add #2' => [
                'tt_content',
                [
                    'selectFields' => '*'
                ],
                [
                    'SELECT' => '*'
                ]
            ],
            'testing #29783: joined tables, prefix tablename' => [
                'tt_content',
                [
                    'selectFields' => 'tt_content.header,be_users.username',
                    'join' => 'be_users ON tt_content.cruser_id = be_users.uid'
                ],
                [
                    'SELECT' => 'tt_content.header,be_users.username, `tt_content`.`uid` AS `uid`, `tt_content`.`pid` AS `pid`, `tt_content`.`t3ver_state` AS `t3ver_state`'
                ]
            ],
            'testing #34152: single count(*), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'count(*)'
                ],
                [
                    'SELECT' => 'count(*)'
                ]
            ],
            'testing #34152: single max(crdate), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'max(crdate)'
                ],
                [
                    'SELECT' => 'max(crdate)'
                ]
            ],
            'testing #34152: single min(crdate), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'min(crdate)'
                ],
                [
                    'SELECT' => 'min(crdate)'
                ]
            ],
            'testing #34152: single sum(is_siteroot), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'sum(is_siteroot)'
                ],
                [
                    'SELECT' => 'sum(is_siteroot)'
                ]
            ],
            'testing #34152: single avg(crdate), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'avg(crdate)'
                ],
                [
                    'SELECT' => 'avg(crdate)'
                ]
            ]
        ];

        return $data;
    }

    /**
     * Check if sanitizeSelectPart works as expected
     *
     * @dataProvider getQueryDataProvider
     * @test
     * @param string $table
     * @param array $conf
     * @param array $expected
     */
    public function getQuery(string $table, array $conf, array $expected)
    {
        $GLOBALS['TCA'] = [
            'pages' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'hidden'
                    ]
                ]
            ],
            'tt_content' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'hidden'
                    ],
                    'versioningWS' => true
                ]
            ],
        ];

        $result = $this->subject->getQuery($table, $conf, true);
        foreach ($expected as $field => $value) {
            $this->assertEquals($value, $result[$field]);
        }
    }

    /**
     * @test
     */
    public function getQueryCallsGetTreeListWithNegativeValuesIfRecursiveIsSet()
    {
        $this->subject = $this->getAccessibleMock(ContentObjectRenderer::class, ['getTreeList']);
        $this->subject->start([], 'tt_content');

        $conf = [
            'recursive' => '15',
            'pidInList' => '16, -35'
        ];

        $this->subject->expects($this->at(0))
            ->method('getTreeList')
            ->with(-16, 15)
            ->will($this->returnValue('15,16'));
        $this->subject->expects($this->at(1))
            ->method('getTreeList')
            ->with(-35, 15)
            ->will($this->returnValue('15,35'));

        $this->subject->getQuery('tt_content', $conf, true);
    }

    /**
     * @test
     */
    public function getQueryCallsGetTreeListWithCurrentPageIfThisIsSet()
    {
        $GLOBALS['TSFE']->id = 27;

        $this->subject = $this->getAccessibleMock(ContentObjectRenderer::class, ['getTreeList']);
        $this->subject->start([], 'tt_content');

        $conf = [
            'pidInList' => 'this',
            'recursive' => '4'
        ];

        $this->subject->expects($this->once())
            ->method('getTreeList')
            ->with(-27)
            ->will($this->returnValue('27'));

        $this->subject->getQuery('tt_content', $conf, true);
    }

    /**
     * @return array
     */
    public function getWhereReturnCorrectQueryDataProvider()
    {
        return [
            [
                [
                    'tt_content' => [
                        'ctrl' => [
                        ],
                        'columns' => [
                        ]
                    ],
                ],
                'tt_content',
                [
                    'uidInList' => '42',
                    'pidInList' => 43,
                    'where' => 'tt_content.cruser_id=5',
                    'groupBy' => 'tt_content.title',
                    'orderBy' => 'tt_content.sorting',
                ],
                'WHERE (`tt_content`.`uid` IN (42)) AND (`tt_content`.`pid` IN (43)) AND (tt_content.cruser_id=5) GROUP BY `tt_content`.`title` ORDER BY `tt_content`.`sorting`',
            ],
            [
                [
                    'tt_content' => [
                        'ctrl' => [
                            'delete' => 'deleted',
                            'enablecolumns' => [
                                'disabled' => 'hidden',
                                'starttime' => 'startdate',
                                'endtime' => 'enddate',
                            ],
                            'languageField' => 'sys_language_uid',
                            'transOrigPointerField' => 'l18n_parent',
                        ],
                        'columns' => [
                        ]
                    ],
                ],
                'tt_content',
                [
                    'uidInList' => 42,
                    'pidInList' => 43,
                    'where' => 'tt_content.cruser_id=5',
                    'groupBy' => 'tt_content.title',
                    'orderBy' => 'tt_content.sorting',
                ],
                'WHERE (`tt_content`.`uid` IN (42)) AND (`tt_content`.`pid` IN (43)) AND (tt_content.cruser_id=5) AND (`tt_content`.`sys_language_uid` = 13) AND ((`tt_content`.`deleted` = 0) AND (`tt_content`.`hidden` = 0) AND (`tt_content`.`startdate` <= 4242) AND ((`tt_content`.`enddate` = 0) OR (`tt_content`.`enddate` > 4242))) GROUP BY `tt_content`.`title` ORDER BY `tt_content`.`sorting`',
            ],
            [
                [
                    'tt_content' => [
                        'ctrl' => [
                            'languageField' => 'sys_language_uid',
                            'transOrigPointerField' => 'l18n_parent',
                        ],
                        'columns' => [
                        ]
                    ],
                ],
                'tt_content',
                [
                    'uidInList' => 42,
                    'pidInList' => 43,
                    'where' => 'tt_content.cruser_id=5',
                    'languageField' => 0,
                ],
                'WHERE (`tt_content`.`uid` IN (42)) AND (`tt_content`.`pid` IN (43)) AND (tt_content.cruser_id=5)',
            ],
        ];
    }

    /**
     * @test
     * @param array $tca
     * @param string $table
     * @param array $configuration
     * @param string $expectedResult
     * @dataProvider getWhereReturnCorrectQueryDataProvider
     */
    public function getWhereReturnCorrectQuery(array $tca, string $table, array $configuration, string $expectedResult)
    {
        $GLOBALS['TCA'] = $tca;
        $GLOBALS['SIM_ACCESS_TIME'] = '4242';
        $GLOBALS['TSFE']->sys_language_content = 13;
        /** @var \PHPUnit_Framework_MockObject_MockObject|ContentObjectRenderer $contentObjectRenderer */
        $contentObjectRenderer = $this->getMockBuilder(ContentObjectRenderer::class)
            ->setMethods(['checkPidArray'])
            ->getMock();
        $contentObjectRenderer->expects($this->any())
            ->method('checkPidArray')
            ->willReturn(explode(',', $configuration['pidInList']));

        // Embed the enable fields string into the expected result as the database
        // connection is still unconfigured when the data provider is being run.
        $expectedResult = sprintf($expectedResult, $GLOBALS['TSFE']->sys_page->enableFields($table));

        $this->assertSame($expectedResult, $contentObjectRenderer->getWhere($table, $configuration));
    }

    /**
     * @return array
     */
    public function typolinkReturnsCorrectLinksForPagesDataProvider()
    {
        return [
            'Link to page' => [
                'My page',
                [
                    'parameter' => 42,
                ],
                [
                    'uid' => 42,
                    'title' => 'Page title',
                ],
                '<a href="index.php?id=42">My page</a>',
            ],
            'Link to page without link text' => [
                '',
                [
                    'parameter' => 42,
                ],
                [
                    'uid' => 42,
                    'title' => 'Page title',
                ],
                '<a href="index.php?id=42">Page title</a>',
            ],
            'Link to page with attributes' => [
                'My page',
                [
                    'parameter' => '42',
                    'ATagParams' => 'class="page-class"',
                    'target' => '_self',
                    'title' => 'Link to internal page',
                ],
                [
                    'uid' => 42,
                    'title' => 'Page title',
                ],
                '<a href="index.php?id=42" title="Link to internal page" target="_self" class="page-class">My page</a>',
            ],
            'Link to page with attributes in parameter' => [
                'My page',
                [
                    'parameter' => '42 _self page-class "Link to internal page"',
                ],
                [
                    'uid' => 42,
                    'title' => 'Page title',
                ],
                '<a href="index.php?id=42" title="Link to internal page" target="_self" class="page-class">My page</a>',
            ],
            'Link to page with bold tag in title' => [
                '',
                [
                    'parameter' => 42,
                ],
                [
                    'uid' => 42,
                    'title' => 'Page <b>title</b>',
                ],
                '<a href="index.php?id=42">Page <b>title</b></a>',
            ],
            'Link to page with script tag in title' => [
                '',
                [
                    'parameter' => 42,
                ],
                [
                    'uid' => 42,
                    'title' => '<script>alert(123)</script>Page title',
                ],
                '<a href="index.php?id=42">&lt;script&gt;alert(123)&lt;/script&gt;Page title</a>',
            ],
        ];
    }

    /**
     * @test
     * @param string $linkText
     * @param array $configuration
     * @param array $pageArray
     * @param string $expectedResult
     * @dataProvider typolinkReturnsCorrectLinksForPagesDataProvider
     */
    public function typolinkReturnsCorrectLinksForPages($linkText, $configuration, $pageArray, $expectedResult)
    {
        $pageRepositoryMockObject = $this->getMockBuilder(PageRepository::class)
            ->setMethods(['getPage'])
            ->getMock();
        $pageRepositoryMockObject->expects($this->any())->method('getPage')->willReturn($pageArray);

        $typoScriptFrontendController = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            null,
            1,
            0
        );
        $typoScriptFrontendController->config = [
            'config' => [],
            'mainScript' => 'index.php',
        ];
        $typoScriptFrontendController->sys_page = $pageRepositoryMockObject;
        $typoScriptFrontendController->tmpl = GeneralUtility::makeInstance(TemplateService::class);
        $typoScriptFrontendController->tmpl->setup = [
            'lib.' => [
                'parseFunc.' => $this->getLibParseFunc(),
            ],
        ];
        $GLOBALS['TSFE'] = $typoScriptFrontendController;

        $subject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $this->assertEquals($expectedResult, $subject->typoLink($linkText, $configuration));
    }

    /**
     * @return array
     */
    protected function getLibParseTarget()
    {
        return [
            'override' => '',
            'override.' => [
                'if.' => [
                    'isTrue.' => [
                        'data' => 'TSFE:dtdAllowsFrames',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getLibParseFunc()
    {
        return [
            'makelinks' => '1',
            'makelinks.' => [
                'http.' => [
                    'keep' => '{$styles.content.links.keep}',
                    'extTarget' => '',
                    'extTarget.' => $this->getLibParseTarget(),
                    'mailto.' => [
                        'keep' => 'path',
                    ],
                ],
            ],
            'tags' => [
                'link' => 'TEXT',
                'link.' => [
                    'current' => '1',
                    'typolink.' => [
                        'parameter.' => [
                            'data' => 'parameters : allParams',
                        ],
                        'extTarget.' => $this->getLibParseTarget(),
                        'target.' => $this->getLibParseTarget(),
                    ],
                    'parseFunc.' => [
                        'constants' => '1',
                    ],
                ],
            ],

            'allowTags' => 'a, abbr, acronym, address, article, aside, b, bdo, big, blockquote, br, caption, center, cite, code, col, colgroup, dd, del, dfn, dl, div, dt, em, font, footer, header, h1, h2, h3, h4, h5, h6, hr, i, img, ins, kbd, label, li, link, meta, nav, ol, p, pre, q, samp, sdfield, section, small, span, strike, strong, style, sub, sup, table, thead, tbody, tfoot, td, th, tr, title, tt, u, ul, var',
            'denyTags' => '*',
            'sword' => '<span class="csc-sword">|</span>',
            'constants' => '1',
            'nonTypoTagStdWrap.' => [
                'HTMLparser' => '1',
                'HTMLparser.' => [
                    'keepNonMatchedTags' => '1',
                    'htmlSpecialChars' => '2',
                ],
            ],
        ];
    }
}
