<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Menu;

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
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject;

/**
 * Test case
 *
 */
class AbstractMenuContentObjectTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject
     */
    protected $subject = null;

    /**
     * Set up this testcase
     */
    protected function setUp()
    {
        $proxyClassName = $this->buildAccessibleProxy(\TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject::class);
        $this->subject = $this->getMockForAbstractClass($proxyClassName);
        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TSFE'] = $this->getMock(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class, [], [$GLOBALS['TYPO3_CONF_VARS'], 1, 1]);
        $GLOBALS['TSFE']->cObj = new \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer();
        $GLOBALS['TSFE']->page = [];
    }

    ////////////////////////////////
    // Tests concerning sectionIndex
    ////////////////////////////////
    /**
     * Prepares a test for the method sectionIndex
     *
     * @return void
     */
    protected function prepareSectionIndexTest()
    {
        $this->subject->sys_page = $this->getMock(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        $this->subject->parent_cObj = $this->getMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class);
    }

    /**
     * @test
     */
    public function sectionIndexReturnsEmptyArrayIfTheRequestedPageCouldNotBeFetched()
    {
        $this->prepareSectionIndexTest();
        $this->subject->sys_page->expects($this->once())->method('getPage')->will($this->returnValue(null));
        $result = $this->subject->_call('sectionIndex', 'field');
        $this->assertEquals($result, []);
    }

    /**
     * @test
     */
    public function sectionIndexUsesTheInternalIdIfNoPageIdWasGiven()
    {
        $this->prepareSectionIndexTest();
        $this->subject->id = 10;
        $this->subject->sys_page->expects($this->once())->method('getPage')->will($this->returnValue(null))->with(10);
        $result = $this->subject->_call('sectionIndex', 'field');
        $this->assertEquals($result, []);
    }

    /**
     * @test
     * @expectedException UnexpectedValueException
     */
    public function sectionIndexThrowsAnExceptionIfTheInternalQueryFails()
    {
        $this->prepareSectionIndexTest();
        $this->subject->sys_page->expects($this->once())->method('getPage')->will($this->returnValue([]));
        $this->subject->parent_cObj->expects($this->once())->method('exec_getQuery')->will($this->returnValue(0));
        $this->subject->_call('sectionIndex', 'field');
    }

    /**
     * @test
     */
    public function sectionIndexReturnsOverlaidRowBasedOnTheLanguageOfTheGivenPage()
    {
        $this->prepareSectionIndexTest();
        $this->subject->mconf['sectionIndex.']['type'] = 'all';
        $GLOBALS['TSFE']->sys_language_contentOL = 1;
        $this->subject->sys_page->expects($this->once())->method('getPage')->will($this->returnValue(['_PAGES_OVERLAY_LANGUAGE' => 1]));
        $this->subject->parent_cObj->expects($this->once())->method('exec_getQuery')->will($this->returnValue(1));
        $GLOBALS['TYPO3_DB']->expects($this->exactly(2))->method('sql_fetch_assoc')->will($this->onConsecutiveCalls($this->returnValue(['uid' => 0, 'header' => 'NOT_OVERLAID']), $this->returnValue(false)));
        $this->subject->sys_page->expects($this->once())->method('getRecordOverlay')->will($this->returnValue(['uid' => 0, 'header' => 'OVERLAID']));
        $result = $this->subject->_call('sectionIndex', 'field');
        $this->assertEquals($result[0]['title'], 'OVERLAID');
    }

    /**
     * @return array
     */
    public function sectionIndexFiltersDataProvider()
    {
        return [
            'unfiltered fields' => [
                1,
                [
                    'sectionIndex' => 1,
                    'header' => 'foo',
                    'header_layout' => 1
                ]
            ],
            'with unset section index' => [
                0,
                [
                    'sectionIndex' => 0,
                    'header' => 'foo',
                    'header_layout' => 1
                ]
            ],
            'with unset header' => [
                0,
                [
                    'sectionIndex' => 1,
                    'header' => '',
                    'header_layout' => 1
                ]
            ],
            'with header layout 100' => [
                0,
                [
                    'sectionIndex' => 1,
                    'header' => 'foo',
                    'header_layout' => 100
                ]
            ]
        ];
    }

    /**
     * @test
     * @dataProvider sectionIndexFiltersDataProvider
     * @param int $expectedAmount
     * @param array $dataRow
     */
    public function sectionIndexFilters($expectedAmount, array $dataRow)
    {
        $this->prepareSectionIndexTest();
        $this->subject->mconf['sectionIndex.']['type'] = 'header';
        $this->subject->sys_page->expects($this->once())->method('getPage')->will($this->returnValue([]));
        $this->subject->parent_cObj->expects($this->once())->method('exec_getQuery')->will($this->returnValue(1));
        $GLOBALS['TYPO3_DB']->expects($this->exactly(2))->method('sql_fetch_assoc')->will($this->onConsecutiveCalls($this->returnValue($dataRow), $this->returnValue(false)));
        $result = $this->subject->_call('sectionIndex', 'field');
        $this->assertCount($expectedAmount, $result);
    }

    /**
     * @return array
     */
    public function sectionIndexQueriesWithDifferentColPosDataProvider()
    {
        return [
            'no configuration' => [
                [],
                'colPos=0'
            ],
            'with useColPos 2' => [
                ['useColPos' => 2],
                'colPos=2'
            ],
            'with useColPos -1' => [
                ['useColPos' => -1],
                ''
            ],
            'with stdWrap useColPos' => [
                [
                    'useColPos.' => [
                        'wrap' => '2|'
                    ]
                ],
                'colPos=2'
            ]
        ];
    }

    /**
     * @test
     * @dataProvider sectionIndexQueriesWithDifferentColPosDataProvider
     * @param array $configuration
     * @param string $whereClausePrefix
     */
    public function sectionIndexQueriesWithDifferentColPos($configuration, $whereClausePrefix)
    {
        $this->prepareSectionIndexTest();
        $this->subject->sys_page->expects($this->once())->method('getPage')->will($this->returnValue([]));
        $this->subject->mconf['sectionIndex.'] = $configuration;
        $queryConfiguration = [
            'pidInList' => 12,
            'orderBy' => 'field',
            'languageField' => 'sys_language_uid',
            'where' => $whereClausePrefix
        ];
        $this->subject->parent_cObj->expects($this->once())->method('exec_getQuery')->with('tt_content', $queryConfiguration)->will($this->returnValue(1));
        $this->subject->_call('sectionIndex', 'field', 12);
    }

    ////////////////////////////////////
    // Tests concerning menu item states
    ////////////////////////////////////
    /**
     * @return array
     */
    public function ifsubHasToCheckExcludeUidListDataProvider()
    {
        return [
            'none excluded' => [
                [12, 34, 56],
                '1, 23, 456',
                true
            ],
            'one excluded' => [
                [1, 234, 567],
                '1, 23, 456',
                true
            ],
            'three excluded' => [
                [1, 23, 456],
                '1, 23, 456',
                false
            ],
            'empty excludeList' => [
                [1, 123, 45],
                '',
                true
            ],
            'empty menu' => [
                [],
                '1, 23, 456',
                false
            ],
        ];
    }

    /**
     * @test
     * @dataProvider ifsubHasToCheckExcludeUidListDataProvider
     * @param array $menuItems
     * @param string $excludeUidList
     * @param bool $expectedResult
     */
    public function ifsubHasToCheckExcludeUidList($menuItems, $excludeUidList, $expectedResult)
    {
        $menu = [];
        foreach ($menuItems as $page) {
            $menu[] = ['uid' => $page];
        }
        $runtimeCacheMock = $this->getMockBuilder(VariableFrontend::class)->setMethods(['get', 'set'])->disableOriginalConstructor()->getMock();
        $runtimeCacheMock->expects($this->once())->method('get')->with($this->anything())->willReturn(false);
        $runtimeCacheMock->expects($this->once())->method('set')->with($this->anything(), ['result' => $expectedResult]);
        $this->subject = $this->getMockBuilder(AbstractMenuContentObject::class)->setMethods(['getRuntimeCache'])->getMockForAbstractClass();
        $this->subject->expects($this->once())->method('getRuntimeCache')->willReturn($runtimeCacheMock);
        $this->prepareSectionIndexTest();
        $this->subject->parent_cObj = $this->getMock(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class, []);

        $this->subject->sys_page->expects($this->once())->method('getMenu')->will($this->returnValue($menu));
        $this->subject->menuArr = [
            0 => ['uid' => 1]
        ];
        $this->subject->conf['excludeUidList'] = $excludeUidList;

        $this->assertEquals($expectedResult, $this->subject->isItemState('IFSUB', 0));
    }

    /**
     * @return array
     */
    public function menuTypoLinkCreatesExpectedTypoLinkConfiurationDataProvider()
    {
        return [
            'standard parameter without access protected setting' => [
                [
                    'parameter' => 1,
                    'linkAccessRestrictedPages' => false,
                    'useCacheHash' => true
                ],
                [
                    'showAccessRestrictedPages' => false
                ],
                true,
                ['uid' => 1],
                '',
                0,
                '',
                '',
                '',
                ''
            ],
            'standard parameter with access protected setting' => [
                [
                    'parameter' => 10,
                    'linkAccessRestrictedPages' => true,
                    'useCacheHash' => true
                ],
                [
                    'showAccessRestrictedPages' => true
                ],
                true,
                ['uid' => 10],
                '',
                0,
                '',
                '',
                '',
                ''
            ],
            'standard parameter with access protected setting "NONE" casts to boolean linkAccessRestrictedPages (delegates resolving to typoLink method internals)' => [
                [
                    'parameter' => 10,
                    'linkAccessRestrictedPages' => true,
                    'useCacheHash' => true
                ],
                [
                    'showAccessRestrictedPages' => 'NONE'
                ],
                true,
                ['uid' => 10],
                '',
                0,
                '',
                '',
                '',
                ''
            ],
            'standard parameter with access protected setting (int)67 casts to boolean linkAccessRestrictedPages (delegates resolving to typoLink method internals)' => [
                [
                    'parameter' => 10,
                    'linkAccessRestrictedPages' => true,
                    'useCacheHash' => true
                ],
                [
                    'showAccessRestrictedPages' => 67
                ],
                true,
                ['uid' => 10],
                '',
                0,
                '',
                '',
                '',
                ''
            ],
            'standard parameter with target' => [
                [
                    'parameter' => 1,
                    'target' => '_blank',
                    'linkAccessRestrictedPages' => false,
                    'useCacheHash' => true
                ],
                [
                    'showAccessRestrictedPages' => false
                ],
                true,
                ['uid' => 1],
                '_blank',
                0,
                '',
                '',
                '',
                ''
            ],
            'parameter with typeOverride=10' => [
                [
                    'parameter' => '10,10',
                    'linkAccessRestrictedPages' => false,
                    'useCacheHash' => true
                ],
                [
                    'showAccessRestrictedPages' => false
                ],
                true,
                ['uid' => 10],
                '',
                0,
                '',
                '',
                '',
                10
            ],
            'parameter with target and typeOverride=10' => [
                [
                    'parameter' => '10,10',
                    'linkAccessRestrictedPages' => false,
                    'useCacheHash' => true,
                    'target' => '_self'
                ],
                [
                    'showAccessRestrictedPages' => false
                ],
                true,
                ['uid' => 10],
                '_self',
                0,
                '',
                '',
                '',
                10
            ],
            'parameter with invalid value in typeOverride=foobar ignores typeOverride' => [
                [
                    'parameter' => 20,
                    'linkAccessRestrictedPages' => false,
                    'useCacheHash' => true,
                    'target' => '_self'
                ],
                [
                    'showAccessRestrictedPages' => false
                ],
                true,
                ['uid' => 20],
                '_self',
                0,
                '',
                '',
                '',
                'foobar'
            ],
            'standard parameter with section name' => [
                [
                    'parameter' => 10,
                    'target' => '_blank',
                    'linkAccessRestrictedPages' => false,
                    'no_cache' => true,
                    'section' => 'section-name'
                ],
                [
                    'showAccessRestrictedPages' => false
                ],
                true,
                [
                    'uid' => 10,
                    'sectionIndex_uid' => 'section-name'
                ],
                '_blank',
                1,
                '',
                '',
                '',
                ''
            ],
            'standard parameter with additional parameters' => [
                [
                    'parameter' => 10,
                    'linkAccessRestrictedPages' => false,
                    'no_cache' => true,
                    'section' => 'section-name',
                    'additionalParams' => '&test=foobar'
                ],
                [
                    'showAccessRestrictedPages' => false
                ],
                true,
                [
                    'uid' => 10,
                    'sectionIndex_uid' => 'section-name'
                ],
                '',
                1,
                '',
                '',
                '&test=foobar',
                ''
            ],
            'overridden page array uid value gets used as parameter' => [
                [
                    'parameter' => 99,
                    'linkAccessRestrictedPages' => false,
                    'no_cache' => true,
                    'section' => 'section-name'
                ],
                [
                    'showAccessRestrictedPages' => false
                ],
                true,
                [
                    'uid' => 10,
                    'sectionIndex_uid' => 'section-name'
                ],
                '',
                1,
                '',
                ['uid' => 99],
                '',
                ''
            ],
        ];
    }

    /**
     * @test
     * @dataProvider menuTypoLinkCreatesExpectedTypoLinkConfiurationDataProvider
     * @param array $expected
     * @param array $mconf
     * @param bool $useCacheHash
     * @param array $page
     * @param mixed $oTarget
     * @param int $no_cache
     * @param string $script
     * @param string $overrideArray
     * @param string $addParams
     * @param string $typeOverride
     */
    public function menuTypoLinkCreatesExpectedTypoLinkConfiguration(array $expected, array $mconf, $useCacheHash = true, array $page, $oTarget, $no_cache, $script, $overrideArray = '', $addParams = '', $typeOverride = '')
    {
        $this->subject->parent_cObj = $this->getMockBuilder(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class)
            ->setMethods(['typoLink'])
            ->getMock();
        $this->subject->mconf = $mconf;
        $this->subject->_set('useCacheHash', $useCacheHash);
        $this->subject->parent_cObj->expects($this->once())->method('typoLink')->with('|', $expected);
        $this->subject->menuTypoLink($page, $oTarget, $no_cache, $script, $overrideArray, $addParams, $typeOverride);
    }
}
