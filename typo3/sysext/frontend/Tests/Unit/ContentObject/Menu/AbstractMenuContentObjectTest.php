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

namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Menu;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\MockObject\MockObject;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Cache\Frontend\VariableFrontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\LanguageStore;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Localization\LocalizationFactory;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Page\ImportMap;
use TYPO3\CMS\Core\Page\ImportMapFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Tests\Unit\Page\PageRendererFactoryTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Menu\AbstractMenuContentObject;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AbstractMenuContentObjectTest extends UnitTestCase
{
    use ProphecyTrait;
    use PageRendererFactoryTrait;

    /**
     * @var AbstractMenuContentObject|MockObject|AccessibleObjectInterface
     */
    protected $subject;

    /**
     * Set up this testcase
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com', 'GET'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);
        $this->subject = $this->getAccessibleMockForAbstractClass(AbstractMenuContentObject::class);
        $site = new Site('test', 1, [
            'base' => 'https://www.example.com',
            'languages' => [
                [
                    'languageId' => 0,
                    'title' => 'English',
                    'locale' => 'en_UK',
                    'base' => '/',
                ],
            ],
        ]);
        $packageManagerProphecy = $this->prophesize(PackageManager::class);
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('l10n')->willReturn($cacheFrontendProphecy->reveal());
        $cacheFrontendProphecy->get(Argument::cetera())->willReturn(false);
        $cacheFrontendProphecy->set(Argument::cetera())->willReturn(null);
        $languageService = new LanguageService(new Locales(), new LocalizationFactory(new LanguageStore($packageManagerProphecy->reveal()), $cacheManagerProphecy->reveal()), $cacheFrontendProphecy->reveal());
        $languageServiceFactoryProphecy = $this->prophesize(LanguageServiceFactory::class);
        $languageServiceFactoryProphecy->createFromSiteLanguage(Argument::any())->willReturn($languageService);
        GeneralUtility::addInstance(LanguageServiceFactory::class, $languageServiceFactoryProphecy->reveal());
        $importMapProphecy = $this->prophesize(ImportMap::class);
        $importMapProphecy->render(Argument::type('string'), Argument::type('string'))->willReturn('');
        $importMapFactoryProphecy = $this->prophesize(ImportMapFactory::class);
        $importMapFactoryProphecy->create()->willReturn($importMapProphecy->reveal());
        GeneralUtility::setSingletonInstance(ImportMapFactory::class, $importMapFactoryProphecy->reveal());
        GeneralUtility::setSingletonInstance(
            PageRenderer::class,
            new PageRenderer(...$this->getPageRendererConstructorArgs()),
        );
        $frontendUserProphecy = $this->prophesize(FrontendUserAuthentication::class);
        $GLOBALS['TSFE'] = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setConstructorArgs([new Context(), $site, $site->getDefaultLanguage(), new PageArguments(1, '1', []), $frontendUserProphecy->reveal()])
            ->onlyMethods(['initCaches'])
            ->getMock();
        $GLOBALS['TSFE']->cObj = new ContentObjectRenderer();
        $GLOBALS['TSFE']->page = [];
    }

    /**
     * Reset singleton instances
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    ////////////////////////////////
    // Tests concerning sectionIndex
    ////////////////////////////////
    /**
     * Prepares a test for the method sectionIndex
     */
    protected function prepareSectionIndexTest(): void
    {
        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->getExpressionBuilder()->willReturn(new ExpressionBuilder($connectionProphet->reveal()));
        $connectionProphet->quoteIdentifier(Argument::cetera())->willReturnArgument(0);

        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getConnectionForTable('tt_content')->willReturn($connectionProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());
    }

    /**
     * @test
     */
    public function sectionIndexReturnsEmptyArrayIfTheRequestedPageCouldNotBeFetched(): void
    {
        $this->prepareSectionIndexTest();
        $pageRepository = $this->getMockBuilder(PageRepository::class)->getMock();
        $pageRepository->expects(self::once())->method('getPage')->willReturn(null);
        $this->subject->_set('sys_page', $pageRepository);
        $result = $this->subject->_call('sectionIndex', 'field');
        self::assertEquals([], $result);
    }

    /**
     * @test
     */
    public function sectionIndexUsesTheInternalIdIfNoPageIdWasGiven(): void
    {
        $this->prepareSectionIndexTest();
        $pageRepository = $this->getMockBuilder(PageRepository::class)->getMock();
        $pageRepository->expects(self::once())->method('getPage')->willReturn(null)->with(10);
        $this->subject->_set('sys_page', $pageRepository);
        $this->subject->_set('id', 10);
        $result = $this->subject->_call('sectionIndex', 'field');
        self::assertEquals([], $result);
    }

    /**
     * @test
     */
    public function sectionIndexThrowsAnExceptionIfTheInternalQueryFails(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1337334849);
        $this->prepareSectionIndexTest();
        $pageRepository = $this->getMockBuilder(PageRepository::class)->getMock();
        $pageRepository->expects(self::once())->method('getPage')->willReturn([]);
        $this->subject->_set('sys_page', $pageRepository);
        $this->subject->_set('id', 10);

        $cObject = $this->getMockBuilder(ContentObjectRenderer::class)->getMock();
        $cObject->expects(self::once())->method('exec_getQuery')->willReturn(0);
        $this->subject->_set('parent_cObj', $cObject);

        $this->subject->_call('sectionIndex', 'field');
    }

    /**
     * @test
     */
    public function sectionIndexReturnsOverlaidRowBasedOnTheLanguageOfTheGivenPage(): void
    {
        $statementProphet = $this->prophesize(Result::class);
        $statementProphet->fetchAssociative()->shouldBeCalledTimes(2)->willReturn(['uid' => 0, 'header' => 'NOT_OVERLAID'], false);

        $this->prepareSectionIndexTest();
        $this->subject->_set('mconf', [
            'sectionIndex.' => [
                'type' => 'all',
            ],
        ]);
        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_MIXED));

        $pageRepository = $this->getMockBuilder(PageRepository::class)->setConstructorArgs([$context])->getMock();
        $pageRepository->expects(self::once())->method('getPage')->willReturn(['_PAGES_OVERLAY_LANGUAGE' => 1]);
        $pageRepository->expects(self::once())->method('getRecordOverlay')->willReturn(['uid' => 0, 'header' => 'OVERLAID']);
        $this->subject->_set('sys_page', $pageRepository);

        $cObject = $this->getMockBuilder(ContentObjectRenderer::class)->getMock();
        $cObject->expects(self::once())->method('exec_getQuery')->willReturn($statementProphet->reveal());
        $this->subject->_set('parent_cObj', $cObject);

        $result = $this->subject->_call('sectionIndex', 'field');
        self::assertEquals('OVERLAID', $result[0]['title']);
    }

    /**
     * @return array
     */
    public function sectionIndexFiltersDataProvider(): array
    {
        return [
            'unfiltered fields' => [
                1,
                [
                    'sectionIndex' => 1,
                    'header' => 'foo',
                    'header_layout' => 1,
                ],
            ],
            'with unset section index' => [
                0,
                [
                    'sectionIndex' => 0,
                    'header' => 'foo',
                    'header_layout' => 1,
                ],
            ],
            'with unset header' => [
                0,
                [
                    'sectionIndex' => 1,
                    'header' => '',
                    'header_layout' => 1,
                ],
            ],
            'with header layout 100' => [
                0,
                [
                    'sectionIndex' => 1,
                    'header' => 'foo',
                    'header_layout' => 100,
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider sectionIndexFiltersDataProvider
     * @param int $expectedAmount
     * @param array $dataRow
     */
    public function sectionIndexFilters(int $expectedAmount, array $dataRow): void
    {
        $statementProphet = $this->prophesize(Result::class);
        $statementProphet->fetchAssociative()->willReturn($dataRow, false);

        $this->prepareSectionIndexTest();
        $this->subject->_set('mconf', [
            'sectionIndex.' => [
                'type' => 'header',
            ],
        ]);

        $pageRepository = $this->getMockBuilder(PageRepository::class)->getMock();
        $pageRepository->expects(self::once())->method('getPage')->willReturn(['_PAGES_OVERLAY_LANGUAGE' => 1]);
        $pageRepository->expects(self::once())->method('getPage')->willReturn([]);
        $this->subject->_set('sys_page', $pageRepository);

        $cObject = $this->getMockBuilder(ContentObjectRenderer::class)->getMock();
        $cObject->expects(self::once())->method('exec_getQuery')->willReturn($statementProphet->reveal());
        $this->subject->_set('parent_cObj', $cObject);

        $result = $this->subject->_call('sectionIndex', 'field');
        self::assertCount($expectedAmount, $result);
    }

    /**
     * @return array
     */
    public function sectionIndexQueriesWithDifferentColPosDataProvider(): array
    {
        return [
            'no configuration' => [
                [],
                '0',
                'colPos = 0',
            ],
            'with useColPos 2' => [
                [
                    'useColPos' => 2,
                ],
                '2',
                'colPos = 2',
            ],
            'with useColPos -1' => [
                [
                    'useColPos' => -1,
                ],
                '-1',
                '',
            ],
            'with stdWrap useColPos' => [
                [
                    'useColPos.' => [
                        'wrap' => '2|',
                    ],
                ],
                '2',
                'colPos = 2',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider sectionIndexQueriesWithDifferentColPosDataProvider
     * @param array $configuration
     * @param string $colPosFromStdWrapValue
     * @param string $whereClausePrefix
     */
    public function sectionIndexQueriesWithDifferentColPos(array $configuration, string $colPosFromStdWrapValue, string $whereClausePrefix): void
    {
        $statementProphet = $this->prophesize(Result::class);
        $statementProphet->fetchAssociative()->willReturn([]);

        $this->prepareSectionIndexTest();
        $this->subject->_set('mconf', ['sectionIndex.' => $configuration]);

        $pageRepository = $this->getMockBuilder(PageRepository::class)->getMock();
        $pageRepository->expects(self::once())->method('getPage')->willReturn([]);
        $this->subject->_set('sys_page', $pageRepository);

        $queryConfiguration = [
            'pidInList' => 12,
            'orderBy' => 'field',
            'languageField' => 'sys_language_uid',
            'where' => $whereClausePrefix,
        ];

        $cObject = $this->getMockBuilder(ContentObjectRenderer::class)->getMock();
        $cObject
            ->expects(self::once())
            ->method('stdWrapValue')
            ->with('useColPos', $configuration)
            ->willReturn($colPosFromStdWrapValue);
        $cObject
            ->expects(self::once())
            ->method('exec_getQuery')
            ->with('tt_content', $queryConfiguration)
            ->willReturn($statementProphet->reveal());
        $this->subject->parent_cObj = $cObject;

        $this->subject->_call('sectionIndex', 'field', 12);
    }

    ////////////////////////////////////
    // Tests concerning menu item states
    ////////////////////////////////////
    /**
     * @return array
     */
    public function ifsubHasToCheckExcludeUidListDataProvider(): array
    {
        return [
            'none excluded' => [
                [12, 34, 56],
                '1, 23, 456',
                true,
            ],
            'one excluded' => [
                [1, 234, 567],
                '1, 23, 456',
                true,
            ],
            'three excluded' => [
                [1, 23, 456],
                '1, 23, 456',
                false,
            ],
            'empty excludeList' => [
                [1, 123, 45],
                '',
                true,
            ],
            'empty menu' => [
                [],
                '1, 23, 456',
                false,
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
    public function ifsubHasToCheckExcludeUidList(array $menuItems, string $excludeUidList, bool $expectedResult): void
    {
        $menu = [];
        foreach ($menuItems as $page) {
            $menu[] = ['uid' => $page];
        }
        $runtimeCacheMock = $this->getMockBuilder(VariableFrontend::class)->onlyMethods(['get', 'set'])->disableOriginalConstructor()->getMock();
        $runtimeCacheMock->expects(self::once())->method('get')->with(self::anything())->willReturn(false);
        $runtimeCacheMock->expects(self::once())->method('set')->with(self::anything(), ['result' => $expectedResult]);

        $this->subject = $this->getAccessibleMockForAbstractClass(AbstractMenuContentObject::class, [], '', true, true, true, ['getRuntimeCache']);
        $cObjectMock = $this->getMockBuilder(ContentObjectRenderer::class)->getMock();
        $cObjectMock
            ->expects(self::once())
            ->method('stdWrapValue')
            ->with('excludeUidList', ['excludeUidList' => $excludeUidList])
            ->willReturn($excludeUidList);
        $this->subject->parent_cObj = $cObjectMock;
        $this->subject->expects(self::once())->method('getRuntimeCache')->willReturn($runtimeCacheMock);
        $this->prepareSectionIndexTest();

        $pageRepository = $this->getMockBuilder(PageRepository::class)->getMock();
        $pageRepository->expects(self::once())->method('getMenu')->willReturn($menu);
        $this->subject->_set('sys_page', $pageRepository);
        $this->subject->_set('menuArr', [
            0 => ['uid' => 1],
        ]);
        $this->subject->_set('conf', ['excludeUidList' => $excludeUidList]);

        self::assertEquals($expectedResult, $this->subject->_call('isItemState', 'IFSUB', 0));
    }

    /**
     * @return array
     */
    public function menuTypoLinkCreatesExpectedTypoLinkConfigurationDataProvider(): array
    {
        return [
            'standard parameter without access protected setting' => [
                [
                    'parameter' => 1,
                ],
                [
                    'showAccessRestrictedPages' => false,
                ],
                ['uid' => 1],
                '',
                0,
                '',
            ],
            'standard parameter with access protected setting' => [
                [
                    'parameter' => 10,
                ],
                [
                    'showAccessRestrictedPages' => true,
                ],
                ['uid' => 10],
                '',
                0,
                '',
            ],
            'standard parameter with access protected setting "NONE" casts to boolean linkAccessRestrictedPages (delegates resolving to typoLink method internals)' => [
                [
                    'parameter' => 10,
                ],
                [
                    'showAccessRestrictedPages' => 'NONE',
                ],
                ['uid' => 10],
                '',
                0,
                '',
            ],
            'standard parameter with access protected setting (int)67 casts to boolean linkAccessRestrictedPages (delegates resolving to typoLink method internals)' => [
                [
                    'parameter' => 10,
                ],
                [
                    'showAccessRestrictedPages' => 67,
                ],
                ['uid' => 10],
                '',
                0,
                '',
            ],
            'standard parameter with target' => [
                [
                    'parameter' => 1,
                    'target' => '_blank',
                ],
                [
                    'showAccessRestrictedPages' => false,
                ],
                ['uid' => 1],
                '_blank',
                0,
                '',
            ],
            'parameter with typeOverride=10' => [
                [
                    'parameter' => '10,10',
                ],
                [
                    'showAccessRestrictedPages' => false,
                ],
                ['uid' => 10],
                '',
                '',
                10,
            ],
            'parameter with target and typeOverride=10' => [
                [
                    'parameter' => '10,10',
                    'target' => '_self',
                ],
                [
                    'showAccessRestrictedPages' => false,
                ],
                ['uid' => 10],
                '_self',
                '',
                '10',
            ],
            'parameter with invalid value in typeOverride=foobar ignores typeOverride' => [
                [
                    'parameter' => 20,
                    'target' => '_self',
                ],
                [
                    'showAccessRestrictedPages' => false,
                ],
                ['uid' => 20],
                '_self',
                '',
                'foobar',
                20,
            ],
            'standard parameter with section name' => [
                [
                    'parameter' => 10,
                    'target' => '_blank',
                    'section' => 'section-name',
                ],
                [
                    'showAccessRestrictedPages' => false,
                ],
                [
                    'uid' => 10,
                    'sectionIndex_uid' => 'section-name',
                ],
                '_blank',
                '',
                '',
            ],
            'standard parameter with additional parameters' => [
                [
                    'parameter' => 10,
                    'section' => 'section-name',
                    'additionalParams' => '&test=foobar',
                ],
                [
                    'showAccessRestrictedPages' => false,
                ],
                [
                    'uid' => 10,
                    'sectionIndex_uid' => 'section-name',
                ],
                '',
                '&test=foobar',
                '',
            ],
            'overridden page array uid value gets used as parameter' => [
                [
                    'parameter' => 99,
                    'section' => 'section-name',
                ],
                [
                    'showAccessRestrictedPages' => false,
                ],
                [
                    'uid' => 10,
                    'sectionIndex_uid' => 'section-name',
                ],
                '',
                '',
                '',
                99,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider menuTypoLinkCreatesExpectedTypoLinkConfigurationDataProvider
     * @param array $expected
     * @param array $mconf
     * @param array $page
     * @param mixed $oTarget
     * @param string|int $addParams
     * @param string|int $typeOverride
     * @param int|string|null $overrideId
     */
    public function menuTypoLinkCreatesExpectedTypoLinkConfiguration(array $expected, array $mconf, array $page, string $oTarget, $addParams = '', $typeOverride = '', $overrideId = null): void
    {
        $cObject = $this->getMockBuilder(ContentObjectRenderer::class)
            ->onlyMethods(['typoLink'])
            ->getMock();
        $cObject->expects(self::once())->method('typoLink')->with('|', $expected);
        $this->subject->_set('parent_cObj', $cObject);
        $this->subject->_set('mconf', $mconf);
        $this->subject->_call('menuTypoLink', $page, $oTarget, $addParams, $typeOverride, $overrideId);
    }
}
