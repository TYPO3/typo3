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
use Doctrine\DBAL\Driver\Statement;
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
        $GLOBALS['TSFE'] = $this->getMockBuilder(\TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController::class)
            ->setConstructorArgs([$GLOBALS['TYPO3_CONF_VARS'], 1, 1])
            ->getMock();
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
        $this->subject->sys_page = $this->getMockBuilder(\TYPO3\CMS\Frontend\Page\PageRepository::class)->getMock();
        $this->subject->parent_cObj = $this->getMockBuilder(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class)->getMock();
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
     */
    public function sectionIndexThrowsAnExceptionIfTheInternalQueryFails()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1337334849);
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
        $statementProphet = $this->prophesize(Statement::class);
        $statementProphet->fetch()->shouldBeCalledTimes(2)->willReturn(['uid' => 0, 'header' => 'NOT_OVERLAID'], false);

        $this->prepareSectionIndexTest();
        $this->subject->mconf['sectionIndex.']['type'] = 'all';
        $GLOBALS['TSFE']->sys_language_contentOL = 1;
        $this->subject->sys_page->expects($this->once())->method('getPage')->will($this->returnValue(['_PAGES_OVERLAY_LANGUAGE' => 1]));
        $this->subject->parent_cObj->expects($this->once())->method('exec_getQuery')->willReturn($statementProphet->reveal());
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
        $statementProphet = $this->prophesize(Statement::class);
        $statementProphet->fetch()->willReturn($dataRow, false);

        $this->prepareSectionIndexTest();
        $this->subject->mconf['sectionIndex.']['type'] = 'header';
        $this->subject->sys_page->expects($this->once())->method('getPage')->will($this->returnValue([]));
        $this->subject->parent_cObj->expects($this->once())->method('exec_getQuery')
            ->willReturn($statementProphet->reveal());
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
        $statementProphet = $this->prophesize(Statement::class);
        $statementProphet->fetch()->willReturn([]);

        $this->prepareSectionIndexTest();
        $this->subject->sys_page->expects($this->once())->method('getPage')->will($this->returnValue([]));
        $this->subject->mconf['sectionIndex.'] = $configuration;
        $queryConfiguration = [
            'pidInList' => 12,
            'orderBy' => 'field',
            'languageField' => 'sys_language_uid',
            'where' => $whereClausePrefix
        ];
        $this->subject->parent_cObj->expects($this->once())->method('exec_getQuery')
            ->with('tt_content', $queryConfiguration)
            ->willReturn($statementProphet->reveal());
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
        $this->subject->parent_cObj = $this->getMockBuilder(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class)->getMock();

        $this->subject->sys_page->expects($this->once())->method('getMenu')->will($this->returnValue($menu));
        $this->subject->menuArr = [
            0 => ['uid' => 1]
        ];
        $this->subject->conf['excludeUidList'] = $excludeUidList;

        $this->assertEquals($expectedResult, $this->subject->isItemState('IFSUB', 0));
    }
}
