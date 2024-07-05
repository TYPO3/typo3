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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for \TYPO3\CMS\Core\Html\HtmlParser
 */
final class HtmlParserTest extends UnitTestCase
{
    protected ?HtmlParser $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = new HtmlParser();
    }

    public static function cDataWillRemainUnmodifiedDataProvider(): array
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
     */
    public static function splitIntoBlockDataProvider(): array
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
                    '</body>',
                ],
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
                    '<br /></body>',
                ],
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
                    '</body>',
                ],
            ],
            'splitBlock span with attribute' => [
                'span',
                '<body><h1>Title</h1><span class="title">Note</span></body>',
                false,
                [
                    '<body><h1>Title</h1>',
                    '<span class="title">Note</span>',
                    '</body>',
                ],
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
                    '</body>',
                ],
            ],
        ];
    }

    /**
     * @param string $tag List of tags, comma separated.
     * @param string $content HTML-content
     * @param bool $eliminateExtraEndTags If set, excessive end tags are ignored - you should probably set this in most cases.
     * @param array $expected The expected result
     */
    #[DataProvider('splitIntoBlockDataProvider')]
    #[Test]
    public function splitIntoBlock(string $tag, string $content, bool $eliminateExtraEndTags, array $expected): void
    {
        self::assertSame($expected, $this->subject->splitIntoBlock($tag, $content, $eliminateExtraEndTags));
    }

    #[DataProvider('cDataWillRemainUnmodifiedDataProvider')]
    #[Test]
    public function xHtmlCleaningDoesNotModifyCDATA(string $source, string $expected): void
    {
        $result = $this->subject->HTMLcleaner($source, [], 1);
        self::assertSame($expected, $result);
    }

    #[DataProvider('htmlWithDifferentSingleTagsDataProvider')]
    #[Test]
    public function htmlCleanerKeepsSingleTagsWithAndWithoutEndingSlashUnchanged(string $exampleString): void
    {
        $result = $this->subject->HTMLcleaner($exampleString, ['br' => true], 0);
        self::assertSame($exampleString, $result);
    }

    /**
     * Data provider for htmlCleanerCanHandleSingleTagsWithEndingSlashes
     */
    public static function htmlWithDifferentSingleTagsDataProvider(): array
    {
        return [
            'no slash' => ['one<br>two'],
            'slash without space' => ['one<br/>two'],
            'space and slash' => ['one<br />two'],
        ];
    }

    /**
     * Data provider for spanTagCorrectlyRemovedWhenRmTagIfNoAttribIsConfigured
     */
    public static function spanTagCorrectlyRemovedWhenRmTagIfNoAttribIsConfiguredDataProvider(): array
    {
        return [
            'Span tag with no attrib' => [
                '<span>text</span>',
                'text',
            ],
            'Span tag with allowed id attrib' => [
                '<span id="id">text</span>',
                '<span id="id">text</span>',
            ],
            'Span tag with disallowed style attrib' => [
                '<span style="line-height: 12px;">text</span>',
                'text',
            ],
        ];
    }

    #[DataProvider('spanTagCorrectlyRemovedWhenRmTagIfNoAttribIsConfiguredDataProvider')]
    #[Test]
    public function tagCorrectlyRemovedWhenRmTagIfNoAttribIsConfigured(string $content, string $expectedResult): void
    {
        $tsConfig = [
            'allowTags' => 'span',
            'tags.' => [
                'span.' => [
                    'allowedAttribs' => 'id',
                    'rmTagIfNoAttrib' => 1,
                ],
            ],
        ];
        self::assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
    }

    #[Test]
    public function rmTagIfNoAttribIsConfiguredDoesNotChangeNestingType(): void
    {
        $tsConfig = [
            'allowTags' => 'div,span',
            'rmTagIfNoAttrib' => 'span',
            'globalNesting' => 'div,span',
        ];
        $content = '<span></span><span id="test"><div></span></div>';
        $expectedResult = '<span id="test"></span>';
        self::assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
    }

    /**
     * Data provider for fixAttribCanUseArrayAndStringNotations
     */
    public static function fixAttribCanUseArrayAndStringNotationsDataProvider(): array
    {
        return [
            'denyTags' => [
                'content' => '<img class="allowed-button" src="/something.jpg" /><span>allowed</span><font class="no-button">forbidden</font>',
                'expectedResult' => '<img class="allowed-button" src="/something.jpg" /><span>allowed</span><font class="button">forbidden</font>',
                'tsConfig' => [
                    'allowTags' => 'span,img,font',
                    'tags.' => [
                        'font.' => [
                            'fixAttrib.' => [
                                'class.' => [
                                    'default' => 'btn',
                                    'list' => 'button,btn',
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            'class may contain "btn" or "button"' => [
                'content' => '<span class=" btn ">text</span><span class=" button ">text</span><span class="somethingElse">text</span><span>text</span>',
                'expectedResult' => '<span class="btn">text</span><span class="button">text</span><span class="button">text</span><span class="btn">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'class.' => [
                                    'always' => '1',
                                    'trim' => '1',
                                    'default' => 'btn',
                                    'list' => 'button,btn', // Note: If a class is given but does not match "btn" or "button", not the 'default' value is used, but the first index of "list".
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom, case insensitive' => [
                'content' => '<span data-custom=" bTn ">text</span>',
                'expectedResult' => '<span data-custom="bTn">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom.' => [
                                    'always' => '0',
                                    'trim' => '1',
                                    'default' => 'btn',
                                    'casesensitiveComp' => 0,
                                    'list' => 'button,btn',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom, case insensitive in list' => [
                'content' => '<span data-custom2=" bTn ">text</span>',
                'expectedResult' => '<span data-custom2="bTn">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom2.' => [
                                    'always' => '0',
                                    'trim' => '1',
                                    'default' => 'btn',
                                    'casesensitiveComp' => 0,
                                    'list' => 'buTTon,bTn',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom3, case sensitive' => [
                'content' => '<span data-custom3=" bTn ">text</span>',
                'expectedResult' => '<span data-custom3="button">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom3.' => [
                                    'always' => '0',
                                    'trim' => '1',
                                    'default' => 'btn',
                                    'casesensitiveComp' => 1,
                                    'list' => 'button,btn',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom4, case sensitive in list' => [
                'content' => '<span data-custom4=" btn ">text</span>',
                'expectedResult' => '<span data-custom4="buTTon">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom4.' => [
                                    'always' => '0',
                                    'trim' => '1',
                                    'default' => 'bTn',
                                    'casesensitiveComp' => 1,
                                    'list' => 'buTTon,bTn',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom5, in range' => [
                'content' => '<span data-custom5=" 0 ">text</span><span data-custom5=" abc ">text</span><span data-custom5="2">text</span><span data-custom5=" 4 ">text</span><span data-custom5=" 3castmetoint ">text</span>',
                'expectedResult' => '<span data-custom5="0">text</span><span data-custom5="0">text</span><span data-custom5="2">text</span><span data-custom5="3">text</span><span data-custom5="3">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom5.' => [
                                    'always' => '0',
                                    'trim' => '1',
                                    'casesensitiveComp' => 1,
                                    'range' => '0,3',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom6, in single range' => [
                'content' => '<span data-custom6=" 0 ">text</span><span data-custom6=" abc ">text</span><span data-custom6=" 2 ">text</span><span data-custom6=" 2castmetoint">text</span>',
                'expectedResult' => '<span data-custom6="2">text</span><span data-custom6="2">text</span><span data-custom6="2">text</span><span data-custom6="2">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom6.' => [
                                    'always' => '0',
                                    'trim' => '1',
                                    'default' => 'bTn',
                                    'casesensitiveComp' => 1,
                                    'range' => '2',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom7, set' => [
                'content' => '<span data-custom7=" abc ">text</span>',
                'expectedResult' => '<span data-custom7="setval">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom7.' => [
                                    'always' => '0',
                                    'set' => 'setval',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom8, unset' => [
                'content' => '<span data-custom8="unsetval">text</span>',
                'expectedResult' => '<span>text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom8.' => [
                                    'always' => '0',
                                    'unset' => '1',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom9, unset + remove due to empty attrib' => [
                'content' => '<span data-custom9="unsetval">text</span>',
                'expectedResult' => 'text',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'rmTagIfNoAttrib' => 1,
                            'fixAttrib.' => [
                                'data-custom9.' => [
                                    'always' => '0',
                                    'unset' => '1',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom10, unset + no remove due to one more attrib' => [
                'content' => '<span data-custom10="unsetval" class="something">text</span>',
                'expectedResult' => '<span class="something">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'rmTagIfNoAttrib' => 1,
                            'fixAttrib.' => [
                                'data-custom10.' => [
                                    'always' => '0',
                                    'unset' => '1',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom11, intval' => [
                'content' => '<span data-custom11="5even">text</span>',
                'expectedResult' => '<span data-custom11="5">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom11.' => [
                                    'intval' => '1',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom12, lower' => [
                'content' => '<span data-custom12="LOWER">text</span>',
                'expectedResult' => '<span data-custom12="lower">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom12.' => [
                                    'lower' => '1',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom13, upper' => [
                'content' => '<span data-custom13="upper">text</span>',
                'expectedResult' => '<span data-custom13="UPPER">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom13.' => [
                                    'upper' => '1',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom14, removal if false' => [
                'content' => '<span data-custom14="">text</span><span data-custom14="0">text</span><span data-custom14="false">text</span><span data-custom14="true">text</span><span data-custom14="blank">text</span>',
                'expectedResult' => '<span>text</span><span>text</span><span data-custom14="false">text</span><span data-custom14="true">text</span><span data-custom14="blank">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom14.' => [
                                    'removeIfFalse' => '1',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom15, removal if equals, case sensitive' => [
                'content' => '<span data-custom15="Blank">text</span><span data-custom15="_blank">text</span>',
                'expectedResult' => '<span>text</span><span data-custom15="_blank">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom15.' => [
                                    'removeIfEquals' => 'Blank',
                                    'casesensitiveComp' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom16, removal if equals, case insensitive' => [
                'content' => '<span data-custom16="BlAnK">text</span><span data-custom16="_blank">text</span>',
                'expectedResult' => '<span>text</span><span data-custom16="_blank">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom16.' => [
                                    'removeIfEquals' => 'bLaNk',
                                    'casesensitiveComp' => 0,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom17, prefixRelPathWith' => [
                'content' => '<span data-custom17="anything/linked/to/something/">text</span>',
                'expectedResult' => '<span data-custom17="ftps://anything/linked/to/something/">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom17.' => [
                                    'prefixRelPathWith' => 'ftps://',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom18, userfunc' => [
                'content' => '<span data-custom18="anything/linked/to/something/">text</span>',
                'expectedResult' => '<span data-custom18="Called|anything/linked/to/something/">text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom18.' => [
                                    'userFunc' => 'TYPO3\CMS\Core\Tests\Unit\Html\Fixture\HtmlParserUserFuncFixture->userfuncFixAttrib',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'data-custom19, userfunc with custom parm' => [
                'content' => '<span data-custom19=\'anything/linked/to/something/\'>text</span>',
                'expectedResult' => '<span data-custom19=\'ParamCalled|{"anythingParm":"anythingValue","0":"moreParm","attributeValue":"anything\/linked\/to\/something\/"}\'>text</span>',
                'tsConfig' => [
                    'allowTags' => 'span',
                    'tags.' => [
                        'span.' => [
                            'fixAttrib.' => [
                                'data-custom19.' => [
                                    'userFunc' => 'TYPO3\CMS\Core\Tests\Unit\Html\Fixture\HtmlParserUserFuncFixture->userfuncFixAttribWithParam',
                                    'userFunc.' => [
                                        'anythingParm' => 'anythingValue',
                                        'moreParm',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('fixAttribCanUseArrayAndStringNotationsDataProvider')]
    #[Test]
    public function fixAttribCanUseArrayAndStringNotations(string $content, string $expectedResult, array $tsConfig): void
    {
        self::assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
    }

    /**
     * Data provider for localNestingCorrectlyRemovesInvalidTags
     */
    public static function localNestingCorrectlyRemovesInvalidTagsDataProvider(): array
    {
        return [
            'Valid nesting is untouched' => [
                '<B><I></B></I>',
                '<B><I></B></I>',
            ],
            'Valid nesting with content is untouched' => [
                'testa<B>test1<I>test2</B>test3</I>testb',
                'testa<B>test1<I>test2</B>test3</I>testb',
            ],
            'Superfluous tags are removed' => [
                '</B><B><I></B></I></B>',
                '<B><I></B></I>',
            ],
            'Superfluous tags with content are removed' => [
                'test1</B>test2<B>test3<I>test4</B>test5</I>test6</B>test7',
                'test1test2<B>test3<I>test4</B>test5</I>test6test7',
            ],
            'Another valid nesting test' => [
                '<span><div></span></div>',
                '<span><div></span></div>',
            ],
        ];
    }

    #[DataProvider('localNestingCorrectlyRemovesInvalidTagsDataProvider')]
    #[Test]
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
     */
    public static function globalNestingCorrectlyRemovesInvalidTagsDataProvider(): array
    {
        return [
            'Valid nesting is untouched' => [
                '<B><I></I></B>',
                '<B><I></I></B>',
            ],
            'Valid nesting with content is untouched' => [
                'testa<B>test1<I>test2</I>test3</B>testb',
                'testa<B>test1<I>test2</I>test3</B>testb',
            ],
            'Invalid nesting is cleaned' => [
                '</B><B><I></B></I></B>',
                '<B></B>',
            ],
            'Invalid nesting with content is cleaned' => [
                'test1</B>test2<B>test3<I>test4</B>test5</I>test6</B>test7',
                'test1test2<B>test3test4</B>test5test6test7',
            ],
            'Another invalid nesting test' => [
                '<span><div></span></div>',
                '<span></span>',
            ],
        ];
    }

    #[DataProvider('globalNestingCorrectlyRemovesInvalidTagsDataProvider')]
    #[Test]
    public function globalNestingCorrectlyRemovesInvalidTags(string $content, string $expectedResult): void
    {
        $tsConfig = [
            'allowTags' => 'span,div,b,i',
            'globalNesting' => 'span,div,b,i',
        ];
        self::assertEquals($expectedResult, $this->parseConfigAndCleanHtml($tsConfig, $content));
    }

    public static function emptyTagsDataProvider(): array
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
     * @param bool $stripOn TRUE if stripping should be activated.
     * @param string|bool $tagList Comma separated list of tags that should be stripped.
     * @param bool $treatNonBreakingSpaceAsEmpty If TRUE &nbsp; will be considered empty.
     * @param string $content The HTML code that should be modified.
     * @param string $expectedResult The expected HTML code result.
     */
    #[DataProvider('emptyTagsDataProvider')]
    #[Test]
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
                'treatNonBreakingSpaceAsEmpty' => $treatNonBreakingSpaceAsEmpty,
            ],
        ];

        $result = $this->parseConfigAndCleanHtml($tsConfig, $content);
        self::assertEquals($expectedResult, $result);
    }

    public static function stripEmptyTagsKeepsConfiguredTagsDataProvider(): array
    {
        return [
            [
                'tr,td',
                false,
                '<div><p><tr><td></td></tr></p></div><div class="test"></div><tr></tr><p></p><td></td><i></i>',
                '<div><p><tr><td></td></tr></p></div><tr></tr><td></td>',
            ],
            [
                'tr,td',
                true,
                '<div><p><tr><td></td></tr></p></div><p class="test"> &nbsp; </p><tr></tr><p></p><td></td><i></i>',
                '<div><p><tr><td></td></tr></p></div><tr></tr><td></td>',
            ],
        ];
    }

    /**
     * @param string $tagList List of tags that should be kept, event if they are empty.
     * @param bool $treatNonBreakingSpaceAsEmpty If true &nbsp; will be considered empty.
     * @param string $content The HTML content that should be parsed.
     * @param string $expectedResult The expected HTML code result.
     */
    #[DataProvider('stripEmptyTagsKeepsConfiguredTagsDataProvider')]
    #[Test]
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
                'treatNonBreakingSpaceAsEmpty' => $treatNonBreakingSpaceAsEmpty,
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
     */
    public static function getFirstTagDataProvider(): array
    {
        return [
            ['<body><span></span></body>', '<body>'],
            ['<span>Wrapper<div>Some content</div></span>', '<span>'],
            ['Something before<span>Wrapper<div>Some content</div></span>Something after', 'Something before<span>'],
            ['Something without tag', ''],
            ['text</span>', 'text</span>'],
            ['<span class=<other><inner></span>', '<span class=<other>'],
            ['<sp-an class=<other><inner></sp-an>', '<sp-an class=<other>'],
            ['<span/class=<other><inner></span>', '<span/class=<other>'],
            ['<span class="<other>"><inner></span>', '<span class="<other>">'],
            ['<span class=""<other>""><inner></span>', '<span class=""<other>'],
            ['<span class=<other>>><inner></span>', '<span class=<other>'],
            ['<span class="', ''],
            ['<span class=""', ''],
            ['<span class="<"', ''],
            ['<span class=">"', ''],
            ['<span class="<other><inner></span>', ''],
            ["<span class='<other><inner></span>", ''],
            ['<span class="<other>\'<inner></span>', ''],
            ["<span class='<other>\"<inner></span>", ''],
        ];
    }

    /**
     * Returns the first tag in $str
     * Actually everything from the beginning of the $str is returned, so you better make sure the tag is the first thing...
     *
     *
     * @param string $str HTML string with tags
     * @param string $expected The expected result.
     */
    #[DataProvider('getFirstTagDataProvider')]
    #[Test]
    public function getFirstTag(string $str, string $expected): void
    {
        self::assertEquals($expected, $this->subject->getFirstTag($str));
    }

    /**
     * Data provider for getFirstTagName
     */
    public static function getFirstTagNameDataProvider(): array
    {
        return [
            [
                '<body><span></span></body>',
                false,
                'BODY',
            ],
            [
                '<body><span></span></body>',
                true,
                'body',
            ],
            [
                '<div class="test"><span></span></div>',
                false,
                'DIV',
            ],
            [
                '<div><span class="test"></span></div>',
                false,
                'DIV',
            ],
            [
                '<br /><span class="test"></span>',
                false,
                'BR',
            ],
            [
                '<img src="test.jpg" />',
                false,
                'IMG',
            ],
            ['text</span>', false, ''],
            ['<span class=<other><inner></span>', false, 'SPAN'],
            ['<sp-an class=<other><inner></sp-an>', false, 'SP-AN'],
            ['<span/class=<other><inner></span>', false, 'SPAN'],
            ['<span class="<other>"><inner></span>', false, 'SPAN'],
            ['<span class=""<other>""><inner></span>', false, 'SPAN'],
            ['<span class=<other>>><inner></span>', false, 'SPAN'],
            ['<span class="', false, ''],
            ['<span class=""', false, ''],
            ['<span class="<"', false, ''],
            ['<span class=">"', false, ''],
            ['<span class="<other><inner></span>', false, ''],
            ["<span class='<other><inner></span>", false, ''],
            ['<span class="<other>\'<inner></span>', false, ''],
            ["<span class='<other>\"<inner></span>", false, ''],

        ];
    }

    /**
     * Returns the NAME of the first tag in $str
     *
     *
     * @param string $str HTML tag (The element name MUST be separated from the attributes by a space character! Just *whitespace* will not do)
     * @param bool $preserveCase If set, then the tag is NOT converted to uppercase by case is preserved.
     * @param string $expected The expected result.
     */
    #[DataProvider('getFirstTagNameDataProvider')]
    #[Test]
    public function getFirstTagName(string $str, bool $preserveCase, string $expected): void
    {
        self::assertEquals($expected, $this->subject->getFirstTagName($str, $preserveCase));
    }

    public static function removeFirstAndLastTagDataProvider(): array
    {
        return [
            ['<span>Wrapper<div>Some content</div></span>', 'Wrapper<div>Some content</div>'],
            ['<td><tr>Some content</tr></td>', '<tr>Some content</tr>'],
            [
                'Something before<span>Wrapper<div>Some content</div></span>Something after',
                'Wrapper<div>Some content</div>',
            ],
            ['<span class="hidden">Wrapper<div>Some content</div></span>', 'Wrapper<div>Some content</div>'],
            [
                '<span>Wrapper<div class="hidden">Some content</div></span>',
                'Wrapper<div class="hidden">Some content</div>',
            ],
            [
                'Some stuff before <span>Wrapper<div class="hidden">Some content</div></span> and after',
                'Wrapper<div class="hidden">Some content</div>',
            ],
            ['text', ''],
            ['<span>text', ''],
            ['text</span>', ''],
            ['<span class=<other><inner></span>', '<inner>'],
            ['<sp-an class=<other><inner></sp-an>', '<inner>'],
            ['<span/class=<other><inner></span>', '<inner>'],
            ['<span class="<other>"><inner></span>', '<inner>'],
            ['<span class=""<other>""><inner></span>', '""><inner>'],
            ['<span class=<other>>><inner></span>', '>><inner>'],
            ['<span class="', ''],
            ['<span class=""', ''],
            ['<span class="<"', ''],
            ['<span class=">"', ''],
            ['<span class="<other><inner></span>', ''],
            ["<span class='<other><inner></span>", ''],
            ['<span class="<other>\'<inner></span>', ''],
            ["<span class='<other>\"<inner></span>", ''],
        ];
    }

    /**
     * Removes the first and last tag in the string
     * Anything before the first and after the last tags respectively is also removed
     *
     * @param string $str String to process
     */
    #[DataProvider('removeFirstAndLastTagDataProvider')]
    #[Test]
    public function removeFirstAndLastTag(string $str, string $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->subject->removeFirstAndLastTag($str));
    }

    public static function getTagAttributesDataProvider(): array
    {
        return [
            [
                '<a href="" data-shortCut="DXB" required>',
                [
                    ['href' => '', 'data-shortcut' => 'DXB', 'required' => ''],
                    [
                        'href' => ['origTag' => 'href', 'dashType' => '"'],
                        'data-shortcut' => ['origTag' => 'data-shortCut', 'dashType' => '"'],
                        'required' => ['origTag' => 'required'],
                    ],
                ],
            ],
            [
                '<ul STYLE=\'background-image: (url: "fra.png")\' data-shortcut=FRA>',
                [
                    ['style' => 'background-image: (url: "fra.png")', 'data-shortcut' => 'FRA'],
                    [
                        'style' => ['origTag' => 'STYLE', 'dashType' => '\''],
                        'data-shortcut' => ['origTag' => 'data-shortcut', 'dashType' => ''],
                    ],
                ],
            ],

        ];
    }

    /**
     * Returns an array with all attributes and its meta information from a tag.
     * Removes tag-name if found
     *
     * @param string $tag String to process
     */
    #[DataProvider('getTagAttributesDataProvider')]
    #[Test]
    public function getTagAttributes(string $tag, array $expectedResult): void
    {
        self::assertEquals($expectedResult, $this->subject->get_tag_attributes($tag));
    }

    public static function stripEmptyTagsDataProvider(): array
    {
        return [
            // Testing wrongly encapsulated and upper/lowercase tags
            [
                '<div>Denpassar</div><p> Bali</P><p></p><P></p><ul><li></li></ul>',
                '',
                false,
                '<div>Denpassar</div><p> Bali</P>',
            ],
            // Testing incomplete tags
            [
                '<p><div>Klungklung</div></p><p> Semarapura<p></p><p></p><ul><li></li></ul>',
                '',
                false,
                '<p><div>Klungklung</div></p><p> Semarapura',
            ],
            // Testing third parameter (break spaces
            [
                '<p><div>Badung</div></p><ul> Mangupura<p></p><p></p><ul><li>&nbsp;</li><li>Uluwatu</li></ul>',
                '',
                true,
                '<p><div>Badung</div></p><ul> Mangupura<ul><li>Uluwatu</li></ul>',
            ],
            // Testing fourth parameter (keeping empty other tags, keeping defined used tags)
            [
                '<p><div>Badung</div></p><ul> Mangupura<p></p><p></p><ul><li></li></ul>',
                'p,div',
                true,
                '<p><div>Badung</div></p><ul> Mangupura<ul><li></li></ul>',
            ],

        ];
    }

    /**
     * Strips empty tags from HTML.
     *
     * @param string $content The content to be stripped of empty tags
     * @param string $tagList The comma separated list of tags to be stripped.
     *                        If empty, all empty tags will be stripped
     * @param bool $treatNonBreakingSpaceAsEmpty If TRUE tags containing only &nbsp; entities will be treated as empty.
     */
    #[DataProvider('stripEmptyTagsDataProvider')]
    #[Test]
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

    public static function prefixResourcePathDataProvider(): array
    {
        return [
            '<td background="test.png">' => [
                '<table><tr><td background="test.png">Test</td></tr></table>',
                '/prefix/',
                '<table><tr><td background="/prefix/test.png">Test</td></tr></table>',

            ],
            '<table background="test.png">' => [
                '<table background="test.png"><tr><td>Test</td></tr></table>',
                '/prefix/',
                '<table background="/prefix/test.png"><tr><td>Test</td></tr></table>',
            ],
            '<body background="test.png">' => [
                '<body background="test.png">',
                '/prefix/',
                '<body background="/prefix/test.png">',
            ],
            '<img src="test.png">' => [
                '<img src="test.png">',
                '/prefix/',
                '<img src="/prefix/test.png">',
            ],
            '<input src="test.png">' => [
                '<input type="image" src="test.png"/>',
                '/prefix/',
                '<input type="image" src="/prefix/test.png" />',
            ],
            '<script src="test.js">' => [
                '<script src="test.js"/>',
                '/assets/',
                '<script src="/assets/test.js" />',
            ],
            '<embed src="test.swf">' => [
                '<embed src="test.swf"></embed>',
                '/media/',
                '<embed src="/media/test.swf"></embed>',
            ],
            '<a href="test.pdf">' => [
                '<a href="something/test.pdf">Test PDF</a>',
                '/',
                '<a href="/something/test.pdf">Test PDF</a>',
            ],
            '<link href="test.css">' => [
                '<link rel="stylesheet" type="text/css" href="theme.css">',
                '/css/',
                '<link rel="stylesheet" type="text/css" href="/css/theme.css">',
            ],
            '<form action="test/">' => [
                '<form action="test/"></form>',
                '/',
                '<form action="/test/"></form>',
            ],
            '<param name="movie" value="test.mp4">' => [
                '<param name="movie" value="test.mp4" />',
                '/test/',
                '<param name="movie" value="/test/test.mp4" />',
            ],
            '<source srcset="large.jpg">' => [
                '<source srcset="large.jpg">',
                '/assets/',
                '<source srcset="/assets/large.jpg">',
            ],
            '<source media="(min-width: 56.25em)" srcset="large.jpg 1x, large@2x.jpg 2x">' => [
                '<source media="(min-width: 56.25em)" srcset="large.jpg 1x, large@2x.jpg 2x">',
                '/assets/',
                '<source media="(min-width: 56.25em)" srcset="/assets/large.jpg 1x, /assets/large@2x.jpg 2x">',
            ],
        ];
    }

    #[DataProvider('prefixResourcePathDataProvider')]
    #[Test]
    public function prefixResourcePathTest(string $content, string $prefix, string $expectedResult): void
    {
        self::assertSame(
            $expectedResult,
            $this->subject->prefixResourcePath($prefix, $content)
        );
    }
}
