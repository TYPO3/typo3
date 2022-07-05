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

use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Resource\FileReference;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
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

    protected array $pathsToProvideInTestInstance = ['typo3/sysext/frontend/Tests/Functional/Fixtures/Images' => 'fileadmin/user_upload'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBackendUserFromFixture(1);
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
            if ($field === 'SELECT') {
                $quoteChar = $databasePlatform->getIdentifierQuoteCharacter();
                $value = str_replace(['[', ']'], [$quoteChar, $quoteChar], $value);
            }
            self::assertEquals($value, $result[$field]);
        }
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

        $tsfe->config['config']['spamProtectEmailAddresses'] = 1;
        $result = $subject->typoLink('Send me an email', ['parameter' => 'mailto:test@example.com']);
        self::assertEquals('<a href="#" data-mailto-token="nbjmup+uftuAfybnqmf/dpn" data-mailto-vector="1">Send me an email</a>', $result);
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

    public function checkIfReturnsExpectedValuesDataProvider(): iterable
    {
        yield 'isNull returns true if stdWrap returns null' => [
            'configuration' => [
                'isNull.' => [
                    'field' => 'unknown',
                ],
            ],
            'expected' => true,
        ];

        yield 'isNull returns false if stdWrap returns not null' => [
            'configuration' => [
                'isNull.' => [
                    'field' => 'known',
                ],
            ],
            'expected' => false,
        ];
    }

    /**
     * @test
     * @dataProvider checkIfReturnsExpectedValuesDataProvider
     */
    public function checkIfReturnsExpectedValues(array $configuration, bool $expected): void
    {
        $this->subject->data = [
            'known' => 'somevalue',
        ];
        self::assertSame($expected, $this->subject->checkIf($configuration));
    }

    public function imageLinkWrapWrapsTheContentAsConfiguredDataProvider(): iterable
    {
        $width = 900;
        $height = 600;
        $processingWidth = $width . 'm';
        $processingHeight = $height . 'm';
        $defaultConfiguration = [
            'wrap' => '<a href="javascript:close();"> | </a>',
            'width' => $processingWidth,
            'height' => $processingHeight,
            'JSwindow' => '1',
            'JSwindow.' => [
                'newWindow' => '0',
            ],
            'crop.' => [
                'data' => 'file:current:crop',
            ],
            'linkParams.' => [
                'ATagParams.' => [
                    'dataWrap' => 'class="lightbox" rel="lightbox[{field:uid}]"',
                ],
            ],
            'enable' => true,
        ];
        $imageTag = '<img class="image-embed-item" src="/fileadmin/_processed_/team-t3board10-processed.jpg" width="500" height="300" loading="lazy" alt="" />';
        $windowFeatures = 'width=' . $width . ',height=' . $height . ',status=0,menubar=0';

        $configurationEnableFalse = $defaultConfiguration;
        $configurationEnableFalse['enable'] = false;
        yield 'enable => false configuration returns image tag as is.' => [
            'content' => $imageTag,
            'configuration' => $configurationEnableFalse,
            'expected' => [$imageTag => true],
        ];

        yield 'image is wrapped with link tag.' => [
            'content' => $imageTag,
            'configuration' => $defaultConfiguration,
            'expected' => [
                '<a href="index.php?eID=tx_cms_showpic&amp;file=1' => true,
                $imageTag . '</a>' => true,
                'data-window-features="' . $windowFeatures => true,
                'data-window-target="thePicture"' => true,
                ' target="' . 'thePicture' => true,
            ],
        ];

        $paramsConfiguration = $defaultConfiguration;
        $windowFeaturesOverrides = 'width=420,status=1,menubar=1,foo=bar';
        $windowFeaturesOverriddenExpected = 'width=420,height=' . $height . ',status=1,menubar=1,foo=bar';
        $paramsConfiguration['JSwindow.']['params'] = $windowFeaturesOverrides;
        yield 'JSWindow.params overrides windowParams' => [
            'content' => $imageTag,
            'configuration' => $paramsConfiguration,
            'expected' => [
                'data-window-features="' . $windowFeaturesOverriddenExpected => true,
            ],
        ];

        $newWindowConfiguration = $defaultConfiguration;
        $newWindowConfiguration['JSwindow.']['newWindow'] = '1';
        yield 'data-window-target is not "thePicture" if newWindow = 1 but an md5 hash of the url.' => [
            'content' => $imageTag,
            'configuration' => $newWindowConfiguration,
            'expected' => [
                'data-window-target="thePicture' => false,
            ],
        ];

        $newWindowConfiguration = $defaultConfiguration;
        $newWindowConfiguration['JSwindow.']['expand'] = '20,40';
        $windowFeaturesExpand = 'width=' . ($width + 20) . ',height=' . ($height + 40) . ',status=0,menubar=0';
        yield 'expand increases the window size by its value' => [
            'content' => $imageTag,
            'configuration' => $newWindowConfiguration,
            'expected' => [
                'data-window-features="' . $windowFeaturesExpand => true,
            ],
        ];

        $directImageLinkConfiguration = $defaultConfiguration;
        $directImageLinkConfiguration['directImageLink'] = '1';
        yield 'Direct image link does not use eID and links directly to the image.' => [
            'content' => $imageTag,
            'configuration' => $directImageLinkConfiguration,
            'expected' => [
                'index.php?eID=tx_cms_showpic&amp;file=1' => false,
                '<a href="fileadmin/_processed_' => true,
                'data-window-url="fileadmin/_processed_' => true,
            ],
        ];

        // @todo Error: Object of class TYPO3\CMS\Core\Resource\FileReference could not be converted to string
//        $altUrlConfiguration = $defaultConfiguration;
//        $altUrlConfiguration['JSwindow.']['altUrl'] = '/alternative-url';
//        yield 'JSwindow.altUrl forces an alternative url.' => [
//            'content' => $imageTag,
//            'configuration' => $altUrlConfiguration,
//            'expected' => [
//                '<a href="/alternative-url' => true,
//                'data-window-url="/alternative-url' => true,
//            ],
//        ];

        $altUrlConfigurationNoDefault = $defaultConfiguration;
        $altUrlConfigurationNoDefault['JSwindow.']['altUrl'] = '/alternative-url';
        $altUrlConfigurationNoDefault['JSwindow.']['altUrl_noDefaultParams'] = '1';
        yield 'JSwindow.altUrl_noDefaultParams removes the default ?file= params' => [
            'content' => $imageTag,
            'configuration' => $altUrlConfigurationNoDefault,
            'expected' => [
                '<a href="/alternative-url' => true,
                'data-window-url="/alternative-url' => true,
                'data-window-url="/alternative-url?file=' => false,
            ],
        ];

        $targetConfiguration = $defaultConfiguration;
        $targetConfiguration['target'] = 'myTarget';
        yield 'Setting target overrides the default target "thePicture.' => [
            'content' => $imageTag,
            'configuration' => $targetConfiguration,
            'expected' => [
                ' target="myTarget"' => true,
                'data-window-target="thePicture"' => true,
            ],
        ];

        $parameters = [
            'sample' => '1',
            'width' => $processingWidth,
            'height' => $processingHeight,
            'effects' => 'gamma=1.3 | flip | rotate=180',
            'bodyTag' => '<body style="margin:0; background:#fff;">',
            'title' => 'My Title',
            'wrap' => '<div class="my-wrap">|</div>',
            'crop' => '{"default":{"cropArea":{"x":0,"y":0,"width":1,"height":1},"selectedRatio":"NaN","focusArea":null}}',
        ];
        $parameterConfiguration = array_replace($defaultConfiguration, $parameters);
        $expectedParameters = $parameters;
        $expectedParameters['sample'] = 1;
        yield 'Setting one of [width, height, effects, bodyTag, title, wrap, crop, sample] will add them to the parameter list.' => [
            'content' => $imageTag,
            'configuration' => $parameterConfiguration,
            'expected' => [],
            'expectedParams' => $expectedParameters,
        ];

        $stdWrapConfiguration = $defaultConfiguration;
        $stdWrapConfiguration['stdWrap.'] = [
            'append' => 'TEXT',
            'append.' => [
                'value' => 'appendedString',
            ],
        ];
        yield 'stdWrap is called upon the whole content.' => [
            'content' => $imageTag,
            'configuration' => $stdWrapConfiguration,
            'expected' => [
                'appendedString' => true,
            ],
        ];
    }

    /**
     * @dataProvider imageLinkWrapWrapsTheContentAsConfiguredDataProvider
     * @test
     */
    public function imageLinkWrapWrapsTheContentAsConfigured(string $content, array $configuration, array $expected, array $expectedParams = []): void
    {
        $GLOBALS['TSFE'] = $this->typoScriptFrontendController;
        $this->importCSVDataSet(__DIR__ . '/DataSet/FileReferences.csv');
        $fileReferenceData = [
            'uid' => 1,
            'uid_local' => 1,
            'crop' => '{"default":{"cropArea":{"x":0,"y":0,"width":1,"height":1},"selectedRatio":"NaN","focusArea":null}}',
        ];
        $fileReference = new FileReference($fileReferenceData);
        $this->subject->setCurrentFile($fileReference);
        $result = $this->subject->imageLinkWrap($content, $fileReference, $configuration);

        foreach ($expected as $expectedString => $shouldContain) {
            if ($shouldContain) {
                self::assertStringContainsString($expectedString, $result);
            } else {
                self::assertStringNotContainsString($expectedString, $result);
            }
        }

        if ($expectedParams !== []) {
            preg_match('@href="(.*)"@U', $result, $matches);
            self::assertArrayHasKey(1, $matches);
            $url = parse_url(html_entity_decode($matches[1]));
            parse_str($url['query'], $queryResult);
            $base64_string = implode('', $queryResult['parameters']);
            $base64_decoded = base64_decode($base64_string);
            $jsonDecodedArray = json_decode($base64_decoded, true);
            self::assertSame($expectedParams, $jsonDecodedArray);
        }
    }
}
