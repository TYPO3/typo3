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

namespace TYPO3\CMS\RteCKEditor\Tests\Functional\HtmlParser;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\Richtext;
use TYPO3\CMS\Core\Html\RteHtmlParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class HtmlParserProcessingTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = true;
    protected array $coreExtensionsToLoad = ['rte_ckeditor'];

    protected array $removeTagsExpectation = [
        'link',
        'meta',
        'o:p',
        'sdfield',
        'style',
        'title',
        'font',
        'center',
    ];

    // Just some inserted block elements to make expectation more realistic
    protected array $allowedTagExpectation = [
        'p',
        'h1',
        'h2',
        'h3',
        'div',
    ];

    #[Test]
    public function HtmlParserProcessingReceivesRemovedTagsConfiguration(): void
    {
        $richTextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richTextConfigurationConfiguration = $richTextConfigurationProvider
            ->getConfiguration('any', 'any', 0, 'any', []);

        self::assertSame($this->removeTagsExpectation, $richTextConfigurationConfiguration['processing']['HTMLparser_db']['removeTags']);
        // TypoScript/TSconfig assertion, still valid too.
        self::assertSame($this->removeTagsExpectation, $richTextConfigurationConfiguration['proc.']['HTMLparser_db.']['removeTags.']);
    }

    #[Test]
    public function HtmlParserProcessingReceivesTypoScriptStringConfiguration(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/rte-pages-string.csv');

        $extraConfig = [
            'richtextConfiguration' => 'testing',
        ];
        // NOTICE: This is just plain TSconfig. No YAML preset exist. For TSconfig,
        // the "preset" name has no relevance.
        $richTextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richTextConfigurationConfiguration = $richTextConfigurationProvider
            ->getConfiguration('pages', 'TSconfig', 1, 'any', $extraConfig);

        self::assertSame('span,style,meta,code', $richTextConfigurationConfiguration['proc.']['HTMLparser_db.']['removeTags']);
    }

    #[Test]
    public function HtmlParserProcessingReceivesTypoScriptArrayConfiguration(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/rte-pages-array.csv');

        $extraConfig = [
            'richtextConfiguration' => 'testing',
        ];
        // NOTICE: This is just plain TSconfig. No YAML preset exist. For TSconfig,
        // the "preset" name has no relevance.
        $richTextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richTextConfigurationConfiguration = $richTextConfigurationProvider
            ->getConfiguration('pages', 'TSconfig', 1, 'any', $extraConfig);

        self::assertSame(['empty', 'span', 'style', 'meta', 'code'], $richTextConfigurationConfiguration['proc.']['HTMLparser_db.']['removeTags.']);
    }

    #[Test]
    public function HtmlParserProcessingAppliesTypoScriptStringConfiguration(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/rte-pages-string.csv');

        $extraConfig = [
            'richtextConfiguration' => 'testing',
        ];
        // NOTICE: This is just plain TSconfig. No YAML preset exist. For TSconfig,
        // the "preset" name has no relevance.
        $richTextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richTextConfigurationConfiguration = $richTextConfigurationProvider
            ->getConfiguration('pages', 'TSconfig', 1, 'any', $extraConfig);
        $richTextConfigurationConfigurationProc = $richTextConfigurationConfiguration['proc.'] ?? [];

        // Note: RteHtmlParser->TS_transform_db() is another layer of scrubbing
        //       which already removes any inline-level tags if they occur at
        //       block-level range. Thus, removeTags has no influence on that.
        //       We're testing removeTags functionality for removing inline-level tags.
        $html = "<p>This stays.</p>\n<blockquote>This stays</blockquote>\n<div><b>This stays</b>.</div><div><code>This vanishes</code></div>\n<div><span>This vanishes</span></div>\n<div><style type='text/css'>This vanishes</style></div>\n<div><meta>This vanishes</meta></div>\n";
        $htmlExpected = "<p>This stays.</p>\r\n<blockquote>This stays</blockquote>\r\n<div><b>This stays</b>.</div>\r\n<div>This vanishes</div>\r\n<div>This vanishes</div>\r\n<div>This vanishes</div>\r\n<div>This vanishes</div>";

        $subject = $this->get(RteHtmlParser::class);
        $result = $subject->transformTextForPersistence($html, $richTextConfigurationConfigurationProc);

        self::assertSame($htmlExpected, $result);
    }

    #[Test]
    public function HtmlParserProcessingAppliesTypoScriptArrayConfiguration(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/rte-pages-array.csv');

        $extraConfig = [
            'richtextConfiguration' => 'testing',
        ];
        // NOTICE: This is just plain TSconfig. No YAML preset exist. For TSconfig,
        // the "preset" name has no relevance.
        $richTextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richTextConfigurationConfiguration = $richTextConfigurationProvider
            ->getConfiguration('pages', 'TSconfig', 1, 'any', $extraConfig);
        $richTextConfigurationConfigurationProc = $richTextConfigurationConfiguration['proc.'] ?? [];

        // Note: RteHtmlParser->TS_transform_db() is another layer of scrubbing
        //       which already removes any inline-level tags if they occur at
        //       block-level range. Thus, removeTags has no influence on that.
        //       We're testing removeTags functionality for removing inline-level tags.
        $html = "<p>This stays.</p>\n<blockquote>This stays</blockquote>\n<div><i><invalid>This partially stays</invalid></i>.</div><div><code>This vanishes</code></div>\n<div><span>This vanishes</span></div>\n<div><style type='text/css'>This vanishes</style></div>\n<div><meta>This vanishes</meta></div>\n";
        $htmlExpected = "<p>This stays.</p>\r\n<blockquote>This stays</blockquote>\r\n<div><i>This partially stays</i>.</div>\r\n<div>This vanishes</div>\r\n<div>This vanishes</div>\r\n<div>This vanishes</div>\r\n<div>This vanishes</div>";

        $subject = $this->get(RteHtmlParser::class);
        $result = $subject->transformTextForPersistence($html, $richTextConfigurationConfigurationProc);

        self::assertSame($htmlExpected, $result);
    }

    #[Test]
    public function HtmlParserProcessingAppliesRemovedTagsConfiguration(): void
    {
        $richTextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richTextConfigurationConfiguration = $richTextConfigurationProvider
            ->getConfiguration('any', 'any', 0, 'any', []);
        $richTextConfigurationConfigurationProc = $richTextConfigurationConfiguration['proc.'] ?? [];

        $itemsRemove = array_map(fn(string $tagName) => $this->buildHtmlElement($tagName), $this->removeTagsExpectation);
        $itemsAllowed = array_map(fn(string $tagName) => $this->buildHtmlElement($tagName), $this->allowedTagExpectation);
        $items = array_merge($itemsRemove, $itemsAllowed);
        $html = implode("\n", $items);

        $subject = $this->get(RteHtmlParser::class);
        $result = $subject->transformTextForPersistence($html, $richTextConfigurationConfigurationProc);

        // Expectation: All forbidden tags are removed and joined without newlines inside a 'p'
        //              The allowed tags are passed through as-is with \r\n linebreaks.
        $expected = '<p>' . strip_tags(implode(' ', $itemsRemove)) . '</p>' . "\r\n"
                     . implode("\r\n", $itemsAllowed);
        self::assertEquals($expected, $result);
    }

    /**
     * Data provider for HtmlParserProcessingParsesFixAttribs
     * @see TYPO3\CMS\Core\Tests\Unit\Html->fixAttribCanUseArrayAndStringNotations()
     */
    public static function HtmlParserProcessingParsesFixAttribsDataProvider(): array
    {
        return [
            'basic text' => [
                'content' => '<a class="none">text</a>',
                'expectedResult' => '<a class="button">text</a>',
            ],

            'denyTags' => [
                'content' => '<img class="allowed-button" src="/something.jpg" /><span>allowed</span><font class="no-button">forbidden</font>',
                'expectedResult' => '<img class="allowed-button" src="/something.jpg" />allowed<font class="button">forbidden</font>',
            ],

            'inline in block element' => [
                'content' => '<table><span>BlockElement: This is alright</span></table>',
                'expectedResult' => '<table><span>BlockElement: This is alright</span></table>',
            ],

            'block element in inline element' => [
                'content' => '<span><table>BlockElement: This is not good</table></span>',
                'expectedResult' => '<table>BlockElement: This is not good</table>',
            ],

            'class may contain "btn" or "button"' => [
                'content' => '<a class=" btn ">text</a><a class=" button ">text</a><a class="somethingElse">text</a><a>text</a>',
                'expectedResult' => '<a class="btn">text</a><a class="button">text</a><a class="button">text</a><a class="btn">text</a>',
            ],
            // This span enumberation is used to not need to create a distinct YAML file for each tag, as they would influence each other when all put onto "span".
            'data-custom, case insensitive' => [
                'content' => '<span1 data-custom=" bTn ">text</span1>',
                'expectedResult' => '<span1 data-custom="bTn">text</span1>',
            ],
            'data-custom, case insensitive in list' => [
                'content' => '<span2 data-custom2=" bTn ">text</span2>',
                'expectedResult' => '<span2 data-custom2="bTn">text</span2>',
            ],
            'data-custom3, case sensitive' => [
                'content' => '<span3 data-custom3=" bTn ">text</span3>',
                'expectedResult' => '<span3 data-custom3="button">text</span3>',
            ],
            'data-custom4, case sensitive in list' => [
                'content' => '<span4 data-custom4=" btn ">text</span4>',
                'expectedResult' => '<span4 data-custom4="buTTon">text</span4>',
            ],
            'data-custom5, in range' => [
                'content' => '<span5 data-custom5=" 0 ">text</span5><span5 data-custom5=" abc ">text</span5><span5 data-custom5="2">text</span5><span5 data-custom5=" 4 ">text</span5><span5 data-custom5=" 3castmetoint ">text</span5>',
                'expectedResult' => '<span5 data-custom5="0">text</span5><span5 data-custom5="0">text</span5><span5 data-custom5="2">text</span5><span5 data-custom5="3">text</span5><span5 data-custom5="3">text</span5>',
            ],
            'data-custom6, in single range' => [
                'content' => '<span6 data-custom6=" 0 ">text</span6><span6 data-custom6=" abc ">text</span6><span6 data-custom6=" 2 ">text</span6><span6 data-custom6=" 2castmetoint">text</span6>',
                'expectedResult' => '<span6 data-custom6="2">text</span6><span6 data-custom6="2">text</span6><span6 data-custom6="2">text</span6><span6 data-custom6="2">text</span6>',
            ],
            'data-custom7, set' => [
                'content' => '<span7 data-custom7=" abc ">text</span7>',
                'expectedResult' => '<span7 data-custom7="setval">text</span7>',
            ],
            'data-custom8, unset' => [
                'content' => '<span8 data-custom8="unsetval">text</span8>',
                'expectedResult' => '<span8>text</span8>',
            ],
            'data-custom9, unset + remove due to empty attrib' => [
                'content' => '<span9 data-custom9="unsetval">text</span9>',
                'expectedResult' => 'text',
            ],
            'data-custom10, unset + no remove due to one more attrib' => [
                'content' => '<span10 data-custom10="unsetval" class="something">text</span10>',
                'expectedResult' => '<span10 class="something">text</span10>',
            ],
            'data-custom11, intval' => [
                'content' => '<span11 data-custom11="5even">text</span11>',
                'expectedResult' => '<span11 data-custom11="5">text</span11>',
            ],
            'data-custom12, lower' => [
                'content' => '<span12 data-custom12="LOWER">text</span12>',
                'expectedResult' => '<span12 data-custom12="lower">text</span12>',
            ],
            'data-custom13, upper' => [
                'content' => '<span13 data-custom13="upper">text</span13>',
                'expectedResult' => '<span13 data-custom13="UPPER">text</span13>',
            ],
            'data-custom14, removal if false' => [
                'content' => '<span14 data-custom14="">text</span14><span14 data-custom14="0">text</span14><span14 data-custom14="false">text</span14><span14 data-custom14="true">text</span14><span14 data-custom14="blank">text</span14>',
                'expectedResult' => '<span14>text</span14><span14>text</span14><span14 data-custom14="false">text</span14><span14 data-custom14="true">text</span14><span14 data-custom14="blank">text</span14>',
            ],
            'data-custom15, removal if equals, case sensitive' => [
                'content' => '<span15 data-custom15="Blank">text</span15><span15 data-custom15="_blank">text</span15>',
                'expectedResult' => '<span15>text</span15><span15 data-custom15="_blank">text</span15>',
            ],
            'data-custom16, removal if equals, case insensitive' => [
                'content' => '<span16 data-custom16="BlAnK">text</span16><span16 data-custom16="_blank">text</span16>',
                'expectedResult' => '<span16>text</span16><span16 data-custom16="_blank">text</span16>',
            ],
            'data-custom17, prefixRelPathWith' => [
                'content' => '<span17 data-custom17="anything/linked/to/something/">text</span17>',
                'expectedResult' => '<span17 data-custom17="ftps://anything/linked/to/something/">text</span17>',
            ],
            'data-custom18, userfunc' => [
                'content' => '<span18 data-custom18="anything/linked/to/something/">text</span18>',
                'expectedResult' => '<span18 data-custom18="Called|anything/linked/to/something/">text</span18>',
            ],
            /* @todo - This notation does not work in YAML yet
            'data-custom19, userfunc with custom parm' => [
                'content' => '<span19 data-custom19=\'anything/linked/to/something/\'>text</span19>',
                'expectedResult' => '<span19 data-custom19=\'ParamCalled|{"anythingParm":"anythingValue","0":"moreParm","attributeValue":"anything\/linked\/to\/something\/"}\'>text</span19>',
            ],
            */
        ];
    }

    #[DataProvider('HtmlParserProcessingParsesFixAttribsDataProvider')]
    #[Test]
    public function HtmlParserProcessingParsesFixAttribsWithStringConfiguration(string $content, string $expectedResult): void
    {
        $preset = 'RteConfigStringFixture';
        $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets'][$preset] = 'EXT:rte_ckeditor/Tests/Functional/Fixtures/' . $preset . '.yaml';
        $richTextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richTextConfigurationConfiguration = $richTextConfigurationProvider
            ->getConfiguration('any', 'any', 0, 'any', ['richtextConfiguration' => $preset]);
        $richTextConfigurationConfigurationProc = $richTextConfigurationConfiguration['proc.'] ?? [];

        // This assertion ensures that the YAML was loaded.
        self::assertEquals('testingValue', $richTextConfigurationConfigurationProc['typo3Testing.']['testingKey'], 'YAML could not be parsed for Array configuration.');
        $subject = $this->get(RteHtmlParser::class);
        $result = $subject->transformTextForPersistence($content, $richTextConfigurationConfigurationProc);
        self::assertEquals(trim($expectedResult), trim($result));
    }

    #[DataProvider('HtmlParserProcessingParsesFixAttribsDataProvider')]
    #[Test]
    public function HtmlParserProcessingParsesFixAttribsWithArrayConfiguration(string $content, string $expectedResult): void
    {
        $preset = 'RteConfigArrayFixture';
        $GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets'][$preset] = 'EXT:rte_ckeditor/Tests/Functional/Fixtures/' . $preset . '.yaml';
        $richTextConfigurationProvider = GeneralUtility::makeInstance(Richtext::class);
        $richTextConfigurationConfiguration = $richTextConfigurationProvider
            ->getConfiguration('any', 'any', 0, 'any', ['richtextConfiguration' => $preset]);
        $richTextConfigurationConfigurationProc = $richTextConfigurationConfiguration['proc.'] ?? [];

        // This assertion ensures that the YAML was loaded.
        self::assertEquals('testingValue', $richTextConfigurationConfigurationProc['typo3Testing.']['testingKey'], 'YAML could not be parsed for Array configuration.');
        $subject = $this->get(RteHtmlParser::class);
        $result = $subject->transformTextForPersistence($content, $richTextConfigurationConfigurationProc);
        self::assertEquals(trim($expectedResult), trim($result));
    }

    private function buildHtmlElement(string $tagName): string
    {
        return sprintf('<%1$s>text-%1$s-text</%1$s>', $tagName);
    }
}
