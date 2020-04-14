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

namespace TYPO3\CMS\Core\Tests\Unit\Html;

use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Html\HtmlParser
 */
class HtmlParserTest extends UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Html\HtmlParser
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new HtmlParser();
    }

    /**
     * @return array
     */
    public function cDataWillRemainUnmodifiedDataProvider(): array
    {
        return [
            'single-line CDATA' => [
                '/*<![CDATA[*/ <hello world> /*]]>*/',
                '/*<![CDATA[*/ <hello world> /*]]>*/',
            ],
            'multi-line CDATA #1' => [
                '/*<![CDATA[*/' . LF . '<hello world> /*]]>*/',
                '/*<![CDATA[*/' . LF . '<hello world> /*]]>*/',
            ],
            'multi-line CDATA #2' => [
                '/*<![CDATA[*/ <hello world>' . LF . '/*]]>*/',
                '/*<![CDATA[*/ <hello world>' . LF . '/*]]>*/',
            ],
            'multi-line CDATA #3' => [
                '/*<![CDATA[*/' . LF . '<hello world>' . LF . '/*]]>*/',
                '/*<![CDATA[*/' . LF . '<hello world>' . LF . '/*]]>*/',
            ],
        ];
    }

    /**
     * Data provider for splitIntoBlock
     *
     * @return array
     */
    public function splitIntoBlockDataProvider(): array
    {
        return [
            'splitBlock' => [
                'h1,span',
                '<body><h1>Title</h1><span>Note</span></body>',
                false,
                [
                    '<body>',
                    '<h1>Title</h1>',
                    '',
                    '<span>Note</span>',
                    '</body>'
                ]
            ],
            'splitBlock br' => [
                'h1,span',
                '<body><h1>Title</h1><br /><span>Note</span><br /></body>',
                false,
                [
                    '<body>',
                    '<h1>Title</h1>',
                    '<br />',
                    '<span>Note</span>',
                    '<br /></body>'
                ]
            ],
            'splitBlock with attribute' => [
                'h1,span',
                '<body><h1 class="title">Title</h1><span>Note</span></body>',
                false,
                [
                    '<body>',
                    '<h1 class="title">Title</h1>',
                    '',
                    '<span>Note</span>',
                    '</body>'
                ]
            ],
            'splitBlock span with attribute' => [
                'span',
                '<body><h1>Title</h1><span class="title">Note</span></body>',
                false,
                [
                    '<body><h1>Title</h1>',
                    '<span class="title">Note</span>',
                    '</body>'
                ]
            ],
            'splitBlock without extra end tags' => [
                'h1,span,div',
                '<body><h1>Title</h1><span>Note</span></body></div>',
                true,
                [
                    '<body>',
                    '<h1>Title</h1>',
                    '',
                    '<span>Note</span>',
                    '</body>'
                ]
            ],
        ];
    }

    /**
     * @test
     * @param string $tag List of tags, comma separated.
     * @param string $content HTML-content
     * @param bool $eliminateExtraEndTags If set, excessive end tags are ignored - you should probably set this in most cases.
     * @param array $expected The expected result
     * @dataProvider splitIntoBlockDataProvider
     */
    public function splitIntoBlock(string $tag, string $content, bool $eliminateExtraEndTags, array $expected): void
    {
        self::assertSame($expected, $this->subject->splitIntoBlock($tag, $content, $eliminateExtraEndTags));
    }

    /**
     * @test
     * @param string $source
     * @param string $expected
     * @dataProvider cDataWillRemainUnmodifiedDataProvider
     */
    public function xHtmlCleaningDoesNotModifyCDATA(string $source, string $expected): void
    {
        $result = $this->subject->HTMLcleaner($source, [], 1);
        self::assertSame($expected, $result);
    }

    /**
     * Data provider for spanTagCorrectlyRemovedWhenRmTagIfNoAttribIsConfigured
     */
    public static function spanTagCorrectlyRemovedWhenRmTagIfNoAttribIsConfiguredDataProvider(): array
    {
        return [
            'Span tag with no attrib' => [
                '<span>text</span>',
                'text'
            ],
            'Span tag with allowed id attrib' => [
                '<span id="id">text</span>',
                '<span id="id">text</span>'
            ],
            'Span tag with disallowed style attrib' => [
                '<span style="line-height: 12px;">text</span>',
                'text'
            ]
        ];
    }

    /**
     * @test
     * @param string $content
     * @param string $expectedResult
     * @dataProvider spanTagCorrectlyRemovedWhenRmTagIfNoAttribIsConfiguredDataProvider
     */
    public function tagCorrectlyRemovedWhenRmTagIfNoAttribIsConfigured(string $content, string $expectedResult): void
    {
        $tsConfig = [
            'allowTags' => 'span',
            'tags.' => [
                'span.' => [
                    'allowedAttribs' => 'id',
                    'rmTagIfNoAttrib' => 1
                ]
            ]
        ];
        self::assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
    }

    /**
     * @test
     */
    public function rmTagIfNoAttribIsConfiguredDoesNotChangeNestingType(): void
    {
        $tsConfig = [
            'allowTags' => 'div,span',
            'rmTagIfNoAttrib' => 'span',
            'globalNesting' => 'div,span'
        ];
        $content = '<span></span><span id="test"><div></span></div>';
        $expectedResult = '<span id="test"></span>';
        self::assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
    }

    /**
     * Data provider for localNestingCorrectlyRemovesInvalidTags
     *
     * @return array
     */
    public static function localNestingCorrectlyRemovesInvalidTagsDataProvider(): array
    {
        return [
            'Valid nesting is untouched' => [
                '<B><I></B></I>',
                '<B><I></B></I>'
            ],
            'Valid nesting with content is untouched' => [
                'testa<B>test1<I>test2</B>test3</I>testb',
                'testa<B>test1<I>test2</B>test3</I>testb'
            ],
            'Superfluous tags are removed' => [
                '</B><B><I></B></I></B>',
                '<B><I></B></I>'
            ],
            'Superfluous tags with content are removed' => [
                'test1</B>test2<B>test3<I>test4</B>test5</I>test6</B>test7',
                'test1test2<B>test3<I>test4</B>test5</I>test6test7'
            ],
            'Another valid nesting test' => [
                '<span><div></span></div>',
                '<span><div></span></div>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider localNestingCorrectlyRemovesInvalidTagsDataProvider
     * @param string $content
     * @param string $expectedResult
     */
    public function localNestingCorrectlyRemovesInvalidTags(string $content, string $expectedResult): void
    {
        $tsConfig = [
            'allowTags' => 'div,span,b,i',
            'localNesting' => 'div,span,b,i',
        ];
        self::assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
    }

    /**
     * Data provider for globalNestingCorrectlyRemovesInvalidTags
     *
     * @return array
     */
    public static function globalNestingCorrectlyRemovesInvalidTagsDataProvider(): array
    {
        return [
            'Valid nesting is untouched' => [
                '<B><I></I></B>',
                '<B><I></I></B>'
            ],
            'Valid nesting with content is untouched' => [
                'testa<B>test1<I>test2</I>test3</B>testb',
                'testa<B>test1<I>test2</I>test3</B>testb'
            ],
            'Invalid nesting is cleaned' => [
                '</B><B><I></B></I></B>',
                '<B></B>'
            ],
            'Invalid nesting with content is cleaned' => [
                'test1</B>test2<B>test3<I>test4</B>test5</I>test6</B>test7',
                'test1test2<B>test3test4</B>test5test6test7'
            ],
            'Another invalid nesting test' => [
                '<span><div></span></div>',
                '<span></span>',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider globalNestingCorrectlyRemovesInvalidTagsDataProvider
     * @param string $content
     * @param string $expectedResult
     */
    public function globalNestingCorrectlyRemovesInvalidTags(string $content, string $expectedResult): void
    {
        $tsConfig = [
            'allowTags' => 'span,div,b,i',
            'globalNesting' => 'span,div,b,i',
        ];
        self::assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
    }

    /**
     * @return array
     */
    public function emptyTagsDataProvider(): array
    {
        return [
            [false, null, false, '<h1></h1>', '<h1></h1>'],
            [true, null, false, '<h1></h1>', ''],
            [true, null, false, '<h1>hallo</h1>', '<h1>hallo</h1>'],
            [true, null, false, '<h1 class="something"></h1>', ''],
            [true, null, false, '<h1 class="something"></h1><h2></h2>', ''],
            [true, 'h2', false, '<h1 class="something"></h1><h2></h2>', '<h1 class="something"></h1>'],
            [true, 'h2, h1', false, '<h1 class="something"></h1><h2></h2>', ''],
            [true, null, false, '<div><p></p></div>', ''],
            [true, null, false, '<div><p>&nbsp;</p></div>', '<div><p>&nbsp;</p></div>'],
            [true, null, true, '<div><p>&nbsp;&nbsp;</p></div>', ''],
            [true, null, true, '<div>&nbsp;&nbsp;<p></p></div>', ''],
            [true, null, false, '<div>Some content<p></p></div>', '<div>Some content</div>'],
            [true, null, true, '<div>Some content<p></p></div>', '<div>Some content</div>'],
            [true, null, false, '<div>Some content</div>', '<div>Some content</div>'],
            [true, null, true, '<div>Some content</div>', '<div>Some content</div>'],
            [true, null, false, '<a href="#skiplinks">Skiplinks </a><b></b>', '<a href="#skiplinks">Skiplinks </a>'],
            [true, null, true, '<a href="#skiplinks">Skiplinks </a><b></b>', '<a href="#skiplinks">Skiplinks </a>'],
            [false, '', false, '<h1></h1>', '<h1></h1>'],
            [true, '', false, '<h1></h1>', ''],
            [true, '', false, '<h1>hallo</h1>', '<h1>hallo</h1>'],
            [true, '', false, '<h1 class="something"></h1>', ''],
            [true, '', false, '<h1 class="something"></h1><h2></h2>', ''],
            [true, '', false, '<div><p></p></div>', ''],
            [true, '', false, '<div><p>&nbsp;</p></div>', '<div><p>&nbsp;</p></div>'],
            [true, '', true, '<div><p>&nbsp;&nbsp;</p></div>', ''],
            [true, '', true, '<div>&nbsp;&nbsp;<p></p></div>', ''],
            [true, '', false, '<div>Some content<p></p></div>', '<div>Some content</div>'],
            [true, '', true, '<div>Some content<p></p></div>', '<div>Some content</div>'],
            [true, '', false, '<div>Some content</div>', '<div>Some content</div>'],
            [true, '', true, '<div>Some content</div>', '<div>Some content</div>'],
            [true, '', false, '<a href="#skiplinks">Skiplinks </a><b></b>', '<a href="#skiplinks">Skiplinks </a>'],
            [true, '', true, '<a href="#skiplinks">Skiplinks </a><b></b>', '<a href="#skiplinks">Skiplinks </a>'],
        ];
    }

    /**
     * @test
     * @dataProvider emptyTagsDataProvider
     * @param bool $stripOn TRUE if stripping should be activated.
     * @param string|bool $tagList Comma separated list of tags that should be stripped.
     * @param bool $treatNonBreakingSpaceAsEmpty If TRUE &nbsp; will be considered empty.
     * @param string $content The HTML code that should be modified.
     * @param string $expectedResult The expected HTML code result.
     */
    public function stripEmptyTags(
        bool $stripOn,
        $tagList,
        bool $treatNonBreakingSpaceAsEmpty,
        string $content,
        string $expectedResult
    ): void {
        $tsConfig = [
            'keepNonMatchedTags' => 1,
            'stripEmptyTags' => $stripOn,
            'stripEmptyTags.' => [
                'tags' => $tagList,
                'treatNonBreakingSpaceAsEmpty' => $treatNonBreakingSpaceAsEmpty
            ],
        ];

        $result = $this->parseConfigAndCleanHtml($tsConfig, $content);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * @return array
     */
    public function stripEmptyTagsKeepsConfiguredTagsDataProvider(): array
    {
        return [
            [
                'tr,td',
                false,
                '<div><p><tr><td></td></tr></p></div><div class="test"></div><tr></tr><p></p><td></td><i></i>',
                '<div><p><tr><td></td></tr></p></div><tr></tr><td></td>'
            ],
            [
                'tr,td',
                true,
                '<div><p><tr><td></td></tr></p></div><p class="test"> &nbsp; </p><tr></tr><p></p><td></td><i></i>',
                '<div><p><tr><td></td></tr></p></div><tr></tr><td></td>'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider stripEmptyTagsKeepsConfiguredTagsDataProvider
     * @param string $tagList List of tags that should be kept, event if they are empty.
     * @param bool $treatNonBreakingSpaceAsEmpty If true &nbsp; will be considered empty.
     * @param string $content The HTML content that should be parsed.
     * @param string $expectedResult The expected HTML code result.
     */
    public function stripEmptyTagsKeepsConfiguredTags(
        string $tagList,
        bool $treatNonBreakingSpaceAsEmpty,
        string $content,
        string $expectedResult
    ): void {
        $tsConfig = [
            'keepNonMatchedTags' => 1,
            'stripEmptyTags' => 1,
            'stripEmptyTags.' => [
                'keepTags' => $tagList,
                'treatNonBreakingSpaceAsEmpty' => $treatNonBreakingSpaceAsEmpty
            ],
        ];

        $result = $this->parseConfigAndCleanHtml($tsConfig, $content);
        self::assertEquals($expectedResult, $result);
    }

    /**
     * Calls HTMLparserConfig() and passes the generated config to the HTMLcleaner() method on the current subject.
     *
     * @param array $tsConfig The TypoScript that should be used to generate the HTML parser config.
     * @param string $content The content that should be parsed by the HTMLcleaner.
     * @return string The parsed content.
     */
    protected function parseConfigAndCleanHtml(array $tsConfig, string $content): string
    {
        $config = $this->subject->HTMLparserConfig($tsConfig);
        return $this->subject->HTMLcleaner($content, $config[0], $config[1], $config[2], $config[3]);
    }

    /**
     * Data provider for getFirstTag
     *
     * @return array
     */
    public function getFirstTagDataProvider(): array
    {
        return [
            ['<body><span></span></body>', '<body>'],
            ['<span>Wrapper<div>Some content</div></span>', '<span>'],
            ['Something before<span>Wrapper<div>Some content</div></span>Something after', 'Something before<span>'],
            ['Something without tag', '']
        ];
    }

    /**
     * Returns the first tag in $str
     * Actually everything from the beginning of the $str is returned, so you better make sure the tag is the first thing...
     *
     * @test
     * @dataProvider getFirstTagDataProvider
     *
     * @param string $str HTML string with tags
     * @param string $expected The expected result.
     */
    public function getFirstTag(string $str, string $expected): void
    {
        self::assertEquals($expected, $this->subject->getFirstTag($str));
    }

    /**
     * Data provider for getFirstTagName
     *
     * @return array
     */
    public function getFirstTagNameDataProvider(): array
    {
        return [
            [
                '<body><span></span></body>',
                false,
                'BODY'
            ],
            [
                '<body><span></span></body>',
                true,
                'body'
            ],
            [
                '<div class="test"><span></span></div>',
                false,
                'DIV'
            ],
            [
                '<div><span class="test"></span></div>',
                false,
                'DIV'
            ],
            [
                '<br /><span class="test"></span>',
                false,
                'BR'
            ],
            [
                '<img src="test.jpg" />',
                false,
                'IMG'
            ],
        ];
    }

    /**
     * Returns the NAME of the first tag in $str
     *
     * @test
     * @dataProvider getFirstTagNameDataProvider
     *
     * @param string $str HTML tag (The element name MUST be separated from the attributes by a space character! Just *whitespace* will not do)
     * @param bool $preserveCase If set, then the tag is NOT converted to uppercase by case is preserved.
     * @param string $expected The expected result.
     */
    public function getFirstTagName(string $str, bool $preserveCase, string $expected): void
    {
        self::assertEquals($expected, $this->subject->getFirstTagName($str, $preserveCase));
    }

    /**
     * @return array
     */
    public function removeFirstAndLastTagDataProvider(): array
    {
        return [
            ['<span>Wrapper<div>Some content</div></span>', 'Wrapper<div>Some content</div>'],
            ['<td><tr>Some content</tr></td>', '<tr>Some content</tr>'],
            [
                'Something before<span>Wrapper<div>Some content</div></span>Something after',
                'Wrapper<div>Some content</div>'
            ],
            ['<span class="hidden">Wrapper<div>Some content</div></span>', 'Wrapper<div>Some content</div>'],
            [
                '<span>Wrapper<div class="hidden">Some content</div></span>',
                'Wrapper<div class="hidden">Some content</div>'
            ],
            [
                'Some stuff before <span>Wrapper<div class="hidden">Some content</div></span> and after',
                'Wrapper<div class="hidden">Some content</div>'
            ],
        ];
    }

    /**
     * Removes the first and last tag in the string
     * Anything before the first and after the last tags respectively is also removed
     *
     * @test
     * @dataProvider removeFirstAndLastTagDataProvider
     * @param string $str String to process
     * @param string $expectedResult
     */
    public function removeFirstAndLastTag(string $str, string $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->subject->removeFirstAndLastTag($str));
    }

    /**
     * @return array
     */
    public function getTagAttributesDataProvider(): array
    {
        return [
            [
                '<a href="" data-shortCut="DXB" required>',
                [
                    ['href' => '', 'data-shortcut' => 'DXB', 'required' => ''],
                    [
                        'href' => ['origTag' => 'href', 'dashType' => '"'],
                        'data-shortcut' => ['origTag' => 'data-shortCut', 'dashType' => '"'],
                        'required' => ['origTag' => 'required']
                    ]
                ]
            ],
            [
                '<ul STYLE=\'background-image: (url: "fra.png")\' data-shortcut=FRA>',
                [
                    ['style' => 'background-image: (url: "fra.png")', 'data-shortcut' => 'FRA'],
                    [
                        'style' => ['origTag' => 'STYLE', 'dashType' => '\''],
                        'data-shortcut' => ['origTag' => 'data-shortcut', 'dashType' => '']
                    ]
                ]
            ]

        ];
    }

    /**
     * Returns an array with all attributes and its meta information from a tag.
     * Removes tag-name if found
     *
     * @test
     * @dataProvider getTagAttributesDataProvider
     * @param string $tag String to process
     * @param array $expectedResult
     */
    public function getTagAttributes(string $tag, array $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->subject->get_tag_attributes($tag));
    }

    /**
     * @return array
     */
    public function stripEmptyTagsDataProvider(): array
    {
        return [
            // Testing wrongly encapsulated and upper/lowercase tags
            [
                '<div>Denpassar</div><p> Bali</P><p></p><P></p><ul><li></li></ul>',
                '',
                false,
                '<div>Denpassar</div><p> Bali</P>'
            ],
            // Testing incomplete tags
            [
                '<p><div>Klungklung</div></p><p> Semarapura<p></p><p></p><ul><li></li></ul>',
                '',
                false,
                '<p><div>Klungklung</div></p><p> Semarapura'
            ],
            // Testing third parameter (break spaces
            [
                '<p><div>Badung</div></p><ul> Mangupura<p></p><p></p><ul><li>&nbsp;</li><li>Uluwatu</li></ul>',
                '',
                true,
                '<p><div>Badung</div></p><ul> Mangupura<ul><li>Uluwatu</li></ul>'
            ],
            // Testing fourth parameter (keeping empty other tags, keeping defined used tags)
            [
                '<p><div>Badung</div></p><ul> Mangupura<p></p><p></p><ul><li></li></ul>',
                'p,div',
                true,
                '<p><div>Badung</div></p><ul> Mangupura<ul><li></li></ul>'
            ],

        ];
    }

    /**
     * Strips empty tags from HTML.
     *
     * @test
     * @dataProvider stripEmptyTagsDataProvider
     * @param string $content The content to be stripped of empty tags
     * @param string $tagList The comma separated list of tags to be stripped.
     *                        If empty, all empty tags will be stripped
     * @param bool $treatNonBreakingSpaceAsEmpty If TRUE tags containing only &nbsp; entities will be treated as empty.
     * @param string $expectedResult
     */
    public function rawStripEmptyTagsTest(
        string $content,
        string $tagList,
        bool $treatNonBreakingSpaceAsEmpty,
        string $expectedResult
    ): void {
        self::assertEquals(
            $expectedResult,
            $this->subject->stripEmptyTags($content, $tagList, $treatNonBreakingSpaceAsEmpty)
        );
    }
}
