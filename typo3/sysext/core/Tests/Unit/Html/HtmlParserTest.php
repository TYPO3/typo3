<?php
namespace TYPO3\CMS\Core\Tests\Unit\Html;

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

use TYPO3\CMS\Core\Html\HtmlParser;

/**
 * Testcase for \TYPO3\CMS\Core\Html\HtmlParser
 */
class HtmlParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Html\HtmlParser
     */
    protected $subject = null;

    protected function setUp()
    {
        $this->subject = new HtmlParser();
    }

    /**
     * @return array
     */
    public function cDataWillRemainUnmodifiedDataProvider()
    {
        return array(
            'single-line CDATA' => array(
                '/*<![CDATA[*/ <hello world> /*]]>*/',
                '/*<![CDATA[*/ <hello world> /*]]>*/',
            ),
            'multi-line CDATA #1' => array(
                '/*<![CDATA[*/' . LF . '<hello world> /*]]>*/',
                '/*<![CDATA[*/' . LF . '<hello world> /*]]>*/',
            ),
            'multi-line CDATA #2' => array(
                '/*<![CDATA[*/ <hello world>' . LF . '/*]]>*/',
                '/*<![CDATA[*/ <hello world>' . LF . '/*]]>*/',
            ),
            'multi-line CDATA #3' => array(
                '/*<![CDATA[*/' . LF . '<hello world>' . LF . '/*]]>*/',
                '/*<![CDATA[*/' . LF . '<hello world>' . LF . '/*]]>*/',
            ),
        );
    }

    /**
     * Data provider for splitIntoBlock
     *
     * @return array
     */
    public function splitIntoBlockDataProvider()
    {
        return array(
            'splitBlock' => array(
                'h1,span',
                '<body><h1>Title</h1><span>Note</span></body>',
                false,
                array('<body>',
                    '<h1>Title</h1>',
                    '',
                    '<span>Note</span>',
                    '</body>')
            ),
            'splitBlock br' => array(
                'h1,span',
                '<body><h1>Title</h1><br /><span>Note</span><br /></body>',
                false,
                array('<body>',
                    '<h1>Title</h1>',
                    '<br />',
                    '<span>Note</span>',
                    '<br /></body>')
            ),
            'splitBlock with attribute' => array(
                'h1,span',
                '<body><h1 class="title">Title</h1><span>Note</span></body>',
                false,
                array('<body>',
                    '<h1 class="title">Title</h1>',
                    '',
                    '<span>Note</span>',
                    '</body>')
            ),
            'splitBlock span with attribute' => array(
                'span',
                '<body><h1>Title</h1><span class="title">Note</span></body>',
                false,
                array('<body><h1>Title</h1>',
                    '<span class="title">Note</span>',
                    '</body>')
            ),
            'splitBlock without extra end tags' => array(
                'h1,span,div',
                '<body><h1>Title</h1><span>Note</span></body></div>',
                true,
                array('<body>',
                    '<h1>Title</h1>',
                    '',
                    '<span>Note</span>',
                    '</body>')
            ),
        );
    }

    /**
     * @test
     * @param string $tag List of tags, comma separated.
     * @param string $content HTML-content
     * @param bool $eliminateExtraEndTags If set, excessive end tags are ignored - you should probably set this in most cases.
     * @param array $expected The expected result
     * @dataProvider splitIntoBlockDataProvider
     */
    public function splitIntoBlock($tag, $content, $eliminateExtraEndTags, $expected)
    {
        $this->assertSame($expected, $this->subject->splitIntoBlock($tag, $content, $eliminateExtraEndTags));
    }

    /**
     * @test
     * @param string $source
     * @param string $expected
     * @dataProvider cDataWillRemainUnmodifiedDataProvider
     */
    public function xHtmlCleaningDoesNotModifyCDATA($source, $expected)
    {
        $result = $this->subject->XHTML_clean($source);
        $this->assertSame($expected, $result);
    }

    /**
     * Data provider for spanTagCorrectlyRemovedWhenRmTagIfNoAttribIsConfigured
     */
    public static function spanTagCorrectlyRemovedWhenRmTagIfNoAttribIsConfiguredDataProvider()
    {
        return array(
            'Span tag with no attrib' => array(
                '<span>text</span>',
                'text'
            ),
            'Span tag with allowed id attrib' => array(
                '<span id="id">text</span>',
                '<span id="id">text</span>'
            ),
            'Span tag with disallowed style attrib' => array(
                '<span style="line-height: 12px;">text</span>',
                'text'
            )
        );
    }

    /**
     * @test
     * @param string $content
     * @param string $expectedResult
     * @dataProvider spanTagCorrectlyRemovedWhenRmTagIfNoAttribIsConfiguredDataProvider
     */
    public function tagCorrectlyRemovedWhenRmTagIfNoAttribIsConfigured($content, $expectedResult)
    {
        $tsConfig = array(
            'allowTags' => 'span',
            'tags.' => array(
                'span.' => array(
                    'allowedAttribs' => 'id',
                    'rmTagIfNoAttrib' => 1
                )
            )
        );
        $this->assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
    }

    /**
     * @test
     */
    public function rmTagIfNoAttribIsConfiguredDoesNotChangeNestingType()
    {
        $tsConfig = array(
            'allowTags' => 'div,span',
            'rmTagIfNoAttrib' => 'span',
            'globalNesting' => 'div,span'
        );
        $content = '<span></span><span id="test"><div></span></div>';
        $expectedResult = '<span id="test"></span>';
        $this->assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
    }

    /**
     * Data provider for localNestingCorrectlyRemovesInvalidTags
     *
     * @return array
     */
    public static function localNestingCorrectlyRemovesInvalidTagsDataProvider()
    {
        return array(
            'Valid nesting is untouched' => array(
                '<B><I></B></I>',
                '<B><I></B></I>'
            ),
            'Valid nesting with content is untouched' => array(
                'testa<B>test1<I>test2</B>test3</I>testb',
                'testa<B>test1<I>test2</B>test3</I>testb'
            ),
            'Superflous tags are removed' => array(
                '</B><B><I></B></I></B>',
                '<B><I></B></I>'
            ),
            'Superflous tags with content are removed' => array(
                'test1</B>test2<B>test3<I>test4</B>test5</I>test6</B>test7',
                'test1test2<B>test3<I>test4</B>test5</I>test6test7'
            ),
            'Another valid nesting test' => array(
                '<span><div></span></div>',
                '<span><div></span></div>',
            ),
        );
    }

    /**
     * @test
     * @dataProvider localNestingCorrectlyRemovesInvalidTagsDataProvider
     * @param string $content
     * @param string $expectedResult
     */
    public function localNestingCorrectlyRemovesInvalidTags($content, $expectedResult)
    {
        $tsConfig = array(
            'allowTags' => 'div,span,b,i',
            'localNesting' => 'div,span,b,i',
        );
        $this->assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
    }

    /**
     * Data provider for globalNestingCorrectlyRemovesInvalidTags
     *
     * @return array
     */
    public static function globalNestingCorrectlyRemovesInvalidTagsDataProvider()
    {
        return array(
            'Valid nesting is untouched' => array(
                '<B><I></I></B>',
                '<B><I></I></B>'
            ),
            'Valid nesting with content is untouched' => array(
                'testa<B>test1<I>test2</I>test3</B>testb',
                'testa<B>test1<I>test2</I>test3</B>testb'
            ),
            'Invalid nesting is cleaned' => array(
                '</B><B><I></B></I></B>',
                '<B></B>'
            ),
            'Invalid nesting with content is cleaned' => array(
                'test1</B>test2<B>test3<I>test4</B>test5</I>test6</B>test7',
                'test1test2<B>test3test4</B>test5test6test7'
            ),
            'Another invalid nesting test' => array(
                '<span><div></span></div>',
                '<span></span>',
            ),
        );
    }

    /**
     * @test
     * @dataProvider globalNestingCorrectlyRemovesInvalidTagsDataProvider
     * @param string $content
     * @param string $expectedResult
     */
    public function globalNestingCorrectlyRemovesInvalidTags($content, $expectedResult)
    {
        $tsConfig = array(
            'allowTags' => 'span,div,b,i',
            'globalNesting' => 'span,div,b,i',
        );
        $this->assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
    }

    /**
     * @return array
     */
    public function emptyTagsDataProvider()
    {
        return array(
            array(0 , null, false, '<h1></h1>', '<h1></h1>'),
            array(1 , null, false, '<h1></h1>', ''),
            array(1 , null, false, '<h1>hallo</h1>', '<h1>hallo</h1>'),
            array(1 , null, false, '<h1 class="something"></h1>', ''),
            array(1 , null, false, '<h1 class="something"></h1><h2></h2>', ''),
            array(1 , 'h2', false, '<h1 class="something"></h1><h2></h2>', '<h1 class="something"></h1>'),
            array(1 , 'h2, h1', false, '<h1 class="something"></h1><h2></h2>', ''),
            array(1 , null, false, '<div><p></p></div>', ''),
            array(1 , null, false, '<div><p>&nbsp;</p></div>', '<div><p>&nbsp;</p></div>'),
            array(1 , null, true, '<div><p>&nbsp;&nbsp;</p></div>', ''),
            array(1 , null, true, '<div>&nbsp;&nbsp;<p></p></div>', ''),
            array(1 , null, false, '<div>Some content<p></p></div>', '<div>Some content</div>'),
            array(1 , null, true, '<div>Some content<p></p></div>', '<div>Some content</div>'),
            array(1 , null, false, '<div>Some content</div>', '<div>Some content</div>'),
            array(1 , null, true, '<div>Some content</div>', '<div>Some content</div>'),
            array(1 , null, false, '<a href="#skiplinks">Skiplinks </a><b></b>', '<a href="#skiplinks">Skiplinks </a>'),
            array(1 , null, true, '<a href="#skiplinks">Skiplinks </a><b></b>', '<a href="#skiplinks">Skiplinks </a>'),
        );
    }

    /**
     * @test
     * @dataProvider emptyTagsDataProvider
     * @param bool $stripOn TRUE if stripping should be activated.
     * @param string $tagList Comma seperated list of tags that should be stripped.
     * @param bool $treatNonBreakingSpaceAsEmpty If TRUE &nbsp; will be considered empty.
     * @param string $content The HTML code that should be modified.
     * @param string $expectedResult The expected HTML code result.
     */
    public function stripEmptyTags($stripOn, $tagList, $treatNonBreakingSpaceAsEmpty, $content, $expectedResult)
    {
        $tsConfig = array(
            'keepNonMatchedTags' => 1,
            'stripEmptyTags' => $stripOn,
            'stripEmptyTags.' => array(
                'tags' => $tagList,
                'treatNonBreakingSpaceAsEmpty' => $treatNonBreakingSpaceAsEmpty
            ),
        );

        $result = $this->parseConfigAndCleanHtml($tsConfig, $content);
        $this->assertEquals($expectedResult, $result);
    }

    /**
     * Calls HTMLparserConfig() and passes the generated config to the HTMLcleaner() method on the current subject.
     *
     * @param array $tsConfig The TypoScript that should be used to generate the HTML parser config.
     * @param string $content The content that should be parsed by the HTMLcleaner.
     * @return string The parsed content.
     */
    protected function parseConfigAndCleanHtml(array $tsConfig, $content)
    {
        $config = $this->subject->HTMLparserConfig($tsConfig);
        return $this->subject->HTMLcleaner($content, $config[0], $config[1], $config[2], $config[3]);
    }

    /**
     * Data provider for getFirstTag
     *
     * @return array
     */
    public function getFirstTagDataProvider()
    {
        return array(
            array('<body><span></span></body>', '<body>'),
            array('<span>Wrapper<div>Some content</div></span>', '<span>'),
            array('Something before<span>Wrapper<div>Some content</div></span>Something after', 'Something before<span>'),
            array('Something without tag', '')
        );
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
    public function getFirstTag($str, $expected)
    {
        $this->assertEquals($expected, $this->subject->getFirstTag($str));
    }

    /**
     * Data provider for getFirstTagName
     *
     * @return array
     */
    public function getFirstTagNameDataProvider()
    {
        return array(
            array('<body><span></span></body>',
                false,
                'BODY'),
            array('<body><span></span></body>',
                true,
                'body'),
            array('<div class="test"><span></span></div>',
                false,
                'DIV'),
            array('<div><span class="test"></span></div>',
                false,
                'DIV'),
            array('<br /><span class="test"></span>',
                false,
                'BR'),
            array('<img src="test.jpg" />',
                false,
                'IMG'),
        );
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
    public function getFirstTagName($str, $preserveCase, $expected)
    {
        $this->assertEquals($expected, $this->subject->getFirstTagName($str, $preserveCase));
    }

    /**
     * @return array
     */
    public function removeFirstAndLastTagDataProvider()
    {
        return array(
            array('<span>Wrapper<div>Some content</div></span>', 'Wrapper<div>Some content</div>'),
            array('<td><tr>Some content</tr></td>', '<tr>Some content</tr>'),
            array('Something before<span>Wrapper<div>Some content</div></span>Something after', 'Wrapper<div>Some content</div>'),
            array('<span class="hidden">Wrapper<div>Some content</div></span>', 'Wrapper<div>Some content</div>'),
            array('<span>Wrapper<div class="hidden">Some content</div></span>', 'Wrapper<div class="hidden">Some content</div>'),
        );
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
    public function removeFirstAndLastTag($str, $expectedResult)
    {
        $this->assertEquals($expectedResult, $this->subject->removeFirstAndLastTag($str));
    }
}
