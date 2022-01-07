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

namespace TYPO3\CMS\Frontend\Tests\Functional\ContentObject;

use Doctrine\DBAL\Platforms\SQLServer2012Platform as SQLServerPlatform;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Typolink\PageLinkBuilder;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Testcase for TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
 */
class ContentObjectRendererTest extends FunctionalTestCase
{
    use ProphecyTrait;
    use SiteBasedTestTrait;

    /**
     * @var array[]
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    /**
     * @var ContentObjectRenderer
     */
    protected $subject;

    /**
     * @var TypoScriptFrontendController
     */
    protected $typoScriptFrontendController;

    protected function setUp(): void
    {
        parent::setUp();
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404]),
        );
        $_SERVER['HTTP_HOST'] = 'example.com';
        $_SERVER['REQUEST_URI'] = '/en/';
        $_GET['id'] = 1;
        GeneralUtility::flushInternalRuntimeCaches();
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByIdentifier('test');

        $this->typoScriptFrontendController = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            GeneralUtility::makeInstance(Context::class),
            $site,
            $site->getDefaultLanguage(),
            new PageArguments(1, '0', []),
            GeneralUtility::makeInstance(FrontendUserAuthentication::class)
        );
        $this->typoScriptFrontendController->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $this->typoScriptFrontendController->tmpl = GeneralUtility::makeInstance(TemplateService::class);
        $this->subject = GeneralUtility::makeInstance(ContentObjectRenderer::class, $this->typoScriptFrontendController);
        $this->subject->setRequest($this->prophesize(ServerRequestInterface::class)->reveal());
    }

    /**
     * Data provider for the getQuery test
     *
     * @return array multi-dimensional array with the second level like this:
     * @see getQuery
     */
    public function getQueryDataProvider(): array
    {
        return [
            'testing empty conf' => [
                'tt_content',
                [],
                [
                    'SELECT' => '*',
                ],
            ],
            'testing #17284: adding uid/pid for workspaces' => [
                'tt_content',
                [
                    'selectFields' => 'header,bodytext',
                ],
                [
                    'SELECT' => 'header,bodytext, [tt_content].[uid] AS [uid], [tt_content].[pid] AS [pid], [tt_content].[t3ver_state] AS [t3ver_state]',
                ],
            ],
            'testing #17284: no need to add' => [
                'tt_content',
                [
                    'selectFields' => 'tt_content.*',
                ],
                [
                    'SELECT' => 'tt_content.*',
                ],
            ],
            'testing #17284: no need to add #2' => [
                'tt_content',
                [
                    'selectFields' => '*',
                ],
                [
                    'SELECT' => '*',
                ],
            ],
            'testing #29783: joined tables, prefix tablename' => [
                'tt_content',
                [
                    'selectFields' => 'tt_content.header,be_users.username',
                    'join' => 'be_users ON tt_content.cruser_id = be_users.uid',
                ],
                [
                    'SELECT' => 'tt_content.header,be_users.username, [tt_content].[uid] AS [uid], [tt_content].[pid] AS [pid], [tt_content].[t3ver_state] AS [t3ver_state]',
                ],
            ],
            'testing #34152: single count(*), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'count(*)',
                ],
                [
                    'SELECT' => 'count(*)',
                ],
            ],
            'testing #34152: single max(crdate), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'max(crdate)',
                ],
                [
                    'SELECT' => 'max(crdate)',
                ],
            ],
            'testing #34152: single min(crdate), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'min(crdate)',
                ],
                [
                    'SELECT' => 'min(crdate)',
                ],
            ],
            'testing #34152: single sum(is_siteroot), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'sum(is_siteroot)',
                ],
                [
                    'SELECT' => 'sum(is_siteroot)',
                ],
            ],
            'testing #34152: single avg(crdate), add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'avg(crdate)',
                ],
                [
                    'SELECT' => 'avg(crdate)',
                ],
            ],
            'single distinct, add nothing' => [
                'tt_content',
                [
                    'selectFields' => 'DISTINCT crdate',
                ],
                [
                    'SELECT' => 'DISTINCT crdate',
                ],
            ],
            'testing #96321: pidInList=root does not raise PHP 8 warning' => [
                'tt_content',
                [
                    'selectFields' => '*',
                    'recursive' => '5',
                    'pidInList' => 'root',
                ],
                [
                    'SELECT' => '*',
                ],
            ],
        ];
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
    public function getQuery(string $table, array $conf, array $expected): void
    {
        $GLOBALS['TCA'] = [
            'pages' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'hidden',
                    ],
                ],
            ],
            'tt_content' => [
                'ctrl' => [
                    'enablecolumns' => [
                        'disabled' => 'hidden',
                    ],
                    'versioningWS' => true,
                ],
            ],
        ];

        $result = $this->subject->getQuery($table, $conf, true);

        $databasePlatform = (new ConnectionPool())->getConnectionForTable('tt_content')->getDatabasePlatform();
        foreach ($expected as $field => $value) {
            // Replace the MySQL backtick quote character with the actual quote character for the DBMS,
            if (!($databasePlatform instanceof SQLServerPlatform) && $field === 'SELECT') {
                $quoteChar = $databasePlatform->getIdentifierQuoteCharacter();
                $value = str_replace(['[', ']'], [$quoteChar, $quoteChar], $value);
            }
            self::assertEquals($value, $result[$field]);
        }
    }

    /**
     * @test
     */
    public function getQueryCallsGetTreeListWithNegativeValuesIfRecursiveIsSet(): void
    {
        $this->subject = $this->getAccessibleMock(ContentObjectRenderer::class, ['getTreeList'], [$this->typoScriptFrontendController]);
        $this->subject->start([], 'tt_content');

        $conf = [
            'recursive' => '15',
            'pidInList' => '16, -35',
        ];

        $this->subject->expects(self::exactly(2))
            ->method('getTreeList')
            ->withConsecutive([-16, 15], [-35, 15])
            ->willReturnOnConsecutiveCalls('15,16', '15,35');

        $this->subject->getQuery('tt_content', $conf, true);
    }

    /**
     * @test
     */
    public function getQueryCallsGetTreeListWithCurrentPageIfThisIsSet(): void
    {
        $this->typoScriptFrontendController->id = 27;

        $this->subject = $this->getAccessibleMock(ContentObjectRenderer::class, ['getTreeList'], [$this->typoScriptFrontendController]);
        $this->subject->start([], 'tt_content');

        $conf = [
            'pidInList' => 'this',
            'recursive' => '4',
        ];

        $this->subject->expects(self::once())
            ->method('getTreeList')
            ->with(-27)
            ->willReturn('27');

        $this->subject->getQuery('tt_content', $conf, true);
    }

    /**
     * @return array
     */
    public function getWhereReturnCorrectQueryDataProvider(): array
    {
        return [
            [
                [
                    'tt_content' => [
                        'ctrl' => [
                        ],
                        'columns' => [
                        ],
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
                        ],
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
                        ],
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
     * @return array
     */
    public function typolinkReturnsCorrectLinksForPagesDataProvider(): array
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
    public function typolinkReturnsCorrectLinksForPages(string $linkText, array $configuration, array $pageArray, string $expectedResult): void
    {
        // @todo Merge with existing link generation test
        // reason for failing is, that PageLinkBuilder is using a context-specific
        // instance of PageRepository instead of reusing a shared global instance
        self::markTestIncomplete('This test has side effects and is based on non-asserted assumptions');

        $pageRepositoryMockObject = $this->getMockBuilder(PageRepository::class)
            ->onlyMethods(['getPage'])
            ->getMock();
        $pageRepositoryMockObject->method('getPage')->willReturn($pageArray);

        $typoScriptFrontendController = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setConstructorArgs([null, 1, 0])
            ->addMethods(['dummy'])
            ->getMock();
        $typoScriptFrontendController->config = [
            'config' => [],
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
        self::assertEquals($expectedResult, $subject->typoLink($linkText, $configuration));
    }

    /**
     * @test
     */
    public function typolinkReturnsCorrectLinkForEmails(): void
    {
        $expected = '<a href="mailto:test@example.com">Send me an email</a>';
        $subject = new ContentObjectRenderer();
        $result = $subject->typoLink('Send me an email', ['parameter' => 'mailto:test@example.com']);
        self::assertEquals($expected, $result);

        $result = $subject->typoLink('Send me an email', ['parameter' => 'test@example.com']);
        self::assertEquals($expected, $result);
    }

    /**
     * @test
     */
    public function typolinkReturnsCorrectLinkForSpamEncryptedEmails(): void
    {
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)->disableOriginalConstructor()->getMock();
        $subject = new ContentObjectRenderer($tsfe);

        $tsfe->spamProtectEmailAddresses = 1;
        $result = $subject->typoLink('Send me an email', ['parameter' => 'mailto:test@example.com']);
        self::assertEquals('<a href="#" data-mailto-token="nbjmup+uftuAfybnqmf/dpn" data-mailto-vector="1">Send me an email</a>', $result);
    }

    /**
     * @test
     */
    public function typolinkReturnsCorrectLinkForSectionToHomePageWithUrlRewriting(): void
    {
        // @todo Merge with existing link generation test
        // reason for failing is, that PageLinkBuilder is using a context-specific
        // instance of PageRepository instead of reusing a shared global instance
        self::markTestIncomplete('This test has side effects and is based on non-asserted assumptions');

        $pageRepositoryMockObject = $this->getMockBuilder(PageRepository::class)
            ->onlyMethods(['getPage'])
            ->getMock();
        $pageRepositoryMockObject->method('getPage')->willReturn([
            'uid' => 1,
            'title' => 'Page title',
        ]);

        $templateServiceMockObject = $this->getMockBuilder(TemplateService::class)
            ->getMock();
        $templateServiceMockObject->setup = [
            'lib.' => [
                'parseFunc.' => $this->getLibParseFunc(),
            ],
        ];

        $subject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $pageLinkBuilder = $this->getMockBuilder(PageLinkBuilder::class)
            ->onlyMethods(['createTotalUrlAndLinkData'])
            ->setConstructorArgs([$subject])
            ->getMock();
        $pageLinkBuilder->expects(self::once())->method('createTotalUrlAndLinkData')->willReturn([
            'url' => '/index.php?id=1',
            'target' => '',
            'type' => '',
            'orig_type' => '',
            'no_cache' => '',
            'linkVars' => '',
            'sectionIndex' => '',
            'totalURL' => '/',
        ]);
        GeneralUtility::addInstance(PageLinkBuilder::class, $pageLinkBuilder);

        $typoScriptFrontendController = $this->getMockBuilder(TypoScriptFrontendController::class)
            ->setConstructorArgs([null, 1, 0])
            ->addMethods(['dummy'])
            ->getMock();
        $typoScriptFrontendController->config = [
            'config' => [],
        ];
        $typoScriptFrontendController->sys_page = $pageRepositoryMockObject;
        $typoScriptFrontendController->tmpl = $templateServiceMockObject;
        $GLOBALS['TSFE'] = $typoScriptFrontendController;

        $configuration = [
            'parameter' => 1,
            'section' => 'content',
        ];

        self::assertEquals('<a href="#content">Page title</a>', $subject->typoLink('', $configuration));
    }

    /**
     * @test
     */
    public function searchWhereWithTooShortSearchWordWillReturnValidWhereStatement(): void
    {
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)->disableOriginalConstructor()->getMock();
        $subject = new ContentObjectRenderer($tsfe);
        $subject->start([], 'tt_content');

        $expected = '';
        $actual = $subject->searchWhere('ab', 'header,bodytext', 'tt_content');
        self::assertEquals($expected, $actual);
    }

    /**
     * @test
     */
    public function libParseFuncProperlyKeepsTagsUnescaped(): void
    {
        $tsfe = $this->getMockBuilder(TypoScriptFrontendController::class)->disableOriginalConstructor()->getMock();
        $subject = new ContentObjectRenderer($tsfe);
        $subject->setRequest($this->prophesize(ServerRequestInterface::class)->reveal());
        $subject->setLogger(new NullLogger());
        $input = 'This is a simple inline text, no wrapping configured';
        $result = $subject->parseFunc($input, $this->getLibParseFunc());
        self::assertEquals($input, $result);

        $input = '<p>A one liner paragraph</p>';
        $result = $subject->parseFunc($input, $this->getLibParseFunc());
        self::assertEquals($input, $result);

        $input = 'A one liner paragraph
And another one';
        $result = $subject->parseFunc($input, $this->getLibParseFunc());
        self::assertEquals($input, $result);

        $input = '<p>A one liner paragraph</p><p>And another one and the spacing is kept</p>';
        $result = $subject->parseFunc($input, $this->getLibParseFunc());
        self::assertEquals($input, $result);

        $input = '<p>text to a <a href="https://www.example.com">an external page</a>.</p>';
        $result = $subject->parseFunc($input, $this->getLibParseFunc());
        self::assertEquals($input, $result);
    }

    /**
     * @return array
     */
    protected function getLibParseFunc(): array
    {
        return [
            'htmlSanitize' => '1',
            'makelinks' => '1',
            'makelinks.' => [
                'http.' => [
                    'keep' => '{$styles.content.links.keep}',
                    'extTarget' => '',
                    'mailto.' => [
                        'keep' => 'path',
                    ],
                ],
            ],
            'tags.' => [
                'link' => 'TEXT',
                'link.' => [
                    'current' => '1',
                    'typolink.' => [
                        'parameter.' => [
                            'data' => 'parameters : allParams',
                        ],
                    ],
                    'parseFunc.' => [
                        'constants' => '1',
                    ],
                ],
                'a' => 'TEXT',
                'a.' => [
                    'current' => '1',
                    'typolink.' => [
                        'parameter.' => [
                            'data' => 'parameters:href',
                        ],
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
