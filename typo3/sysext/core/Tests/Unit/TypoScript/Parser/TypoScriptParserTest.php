<?php
namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\Parser;

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

/**
 * Test case for \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser
 */
class TypoScriptParserTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $typoScriptParser = null;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $accessibleClassName = $this->buildAccessibleProxy(\TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::class);
        $this->typoScriptParser = new $accessibleClassName();
    }

    /**
     * Data provider for executeValueModifierReturnsModifiedResult
     *
     * @return array modifier name, modifier arguments, current value, expected result
     */
    public function executeValueModifierDataProvider()
    {
        return [
            'prependString with string' => [
                'prependString',
                'abc',
                '!',
                '!abc'
            ],
            'prependString with empty string' => [
                'prependString',
                'foo',
                '',
                'foo',
            ],
            'appendString with string' => [
                'appendString',
                'abc',
                '!',
                'abc!',
            ],
            'appendString with empty string' => [
                'appendString',
                'abc',
                '',
                'abc',
            ],
            'removeString removes simple string' => [
                'removeString',
                'abcdef',
                'bc',
                'adef',
            ],
            'removeString removes nothing if no match' => [
                'removeString',
                'abcdef',
                'foo',
                'abcdef',
            ],
            'removeString removes multiple matches' => [
                'removeString',
                'FooBarFoo',
                'Foo',
                'Bar',
            ],
            'replaceString replaces simple match' => [
                'replaceString',
                'abcdef',
                'bc|123',
                'a123def',
            ],
            'replaceString replaces simple match with nothing' => [
                'replaceString',
                'abcdef',
                'bc',
                'adef',
            ],
            'replaceString replaces multiple matches' => [
                'replaceString',
                'FooBarFoo',
                'Foo|Bar',
                'BarBarBar',
            ],
            'addToList adds at end of existing list' => [
                'addToList',
                '123,456',
                '789',
                '123,456,789',
            ],
            'addToList adds at end of existing list including white-spaces' => [
                'addToList',
                '123,456',
                ' 789 , 32 , 12 ',
                '123,456, 789 , 32 , 12 ',
            ],
            'addToList adds nothing' => [
                'addToList',
                '123,456',
                '',
                '123,456,', // This result is probably not what we want (appended comma) ... fix it?
            ],
            'addToList adds to empty list' => [
                'addToList',
                '',
                'foo',
                'foo',
            ],
            'removeFromList removes value from list' => [
                'removeFromList',
                '123,456,789,abc',
                '456',
                '123,789,abc',
            ],
            'removeFromList removes value at beginning of list' => [
                'removeFromList',
                '123,456,abc',
                '123',
                '456,abc',
            ],
            'removeFromList removes value at end of list' => [
                'removeFromList',
                '123,456,abc',
                'abc',
                '123,456',
            ],
            'removeFromList removes multiple values from list' => [
                'removeFromList',
                'foo,123,bar,123',
                '123',
                'foo,bar',
            ],
            'removeFromList removes empty values' => [
                'removeFromList',
                'foo,,bar',
                '',
                'foo,bar',
            ],
            'uniqueList removes duplicates' => [
                'uniqueList',
                '123,456,abc,456,456',
                '',
                '123,456,abc',
            ],
            'uniqueList removes duplicate empty list values' => [
                'uniqueList',
                '123,,456,,abc',
                '',
                '123,,456,abc',
            ],
            'reverseList returns list reversed' => [
                'reverseList',
                '123,456,abc,456',
                '',
                '456,abc,456,123',
            ],
            'reverseList keeps empty values' => [
                'reverseList',
                ',123,,456,abc,,456',
                '',
                '456,,abc,456,,123,',
            ],
            'reverseList does not change single element' => [
                'reverseList',
                '123',
                '',
                '123',
            ],
            'sortList sorts a list' => [
                'sortList',
                '10,100,0,20,abc',
                '',
                '0,10,20,100,abc',
            ],
            'sortList sorts a list numeric' => [
                'sortList',
                '10,0,100,-20',
                'numeric',
                '-20,0,10,100',
            ],
            'sortList sorts a list descending' => [
                'sortList',
                '10,100,0,20,abc,-20',
                'descending',
                'abc,100,20,10,0,-20',
            ],
            'sortList sorts a list numeric descending' => [
                'sortList',
                '10,100,0,20,-20',
                'descending,numeric',
                '100,20,10,0,-20',
            ],
            'sortList ignores invalid modifier arguments' => [
                'sortList',
                '10,100,20',
                'foo,descending,bar',
                '100,20,10',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider executeValueModifierDataProvider
     */
    public function executeValueModifierReturnsModifiedResult($modifierName, $currentValue, $modifierArgument, $expected)
    {
        $actualValue = $this->typoScriptParser->_call('executeValueModifier', $modifierName, $modifierArgument, $currentValue);
        $this->assertEquals($expected, $actualValue);
    }

    /**
     * Data provider for executeValueModifierThrowsException
     *
     * @return array modifier name, modifier arguments, current value, expected result
     */
    public function executeValueModifierInvalidDataProvider()
    {
        return [
            'sortList sorts a list numeric' => [
                'sortList',
                '10,0,100,-20,abc',
                'numeric',
            ],
            'sortList sorts a list numeric descending' => [
                'sortList',
                '10,100,0,20,abc,-20',
                'descending,numeric',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider executeValueModifierInvalidDataProvider
     */
    public function executeValueModifierThrowsException($modifierName, $currentValue, $modifierArgument)
    {
        $this->setExpectedException('InvalidArgumentException', 'The list "' . $currentValue . '" should be sorted numerically but contains a non-numeric value');
        $this->typoScriptParser->_call('executeValueModifier', $modifierName, $modifierArgument, $currentValue);
    }

    /**
     * @param string $typoScript
     * @param array $expected
     * @dataProvider typoScriptIsParsedToArrayDataProvider
     * @test
     */
    public function typoScriptIsParsedToArray($typoScript, array $expected)
    {
        $this->typoScriptParser->parse($typoScript);
        $this->assertEquals($expected, $this->typoScriptParser->setup);
    }

    /**
     * @return array
     */
    public function typoScriptIsParsedToArrayDataProvider()
    {
        return [
            'simple assignment' => [
                'key = value',
                [
                    'key' => 'value',
                ]
            ],
            'simple assignment with escaped dot at the beginning' => [
                '\\.key = value',
                [
                    '.key' => 'value',
                ]
            ],
            'simple assignment with protected escaped dot at the beginning' => [
                '\\\\.key = value',
                [
                    '\\.' => [
                        'key' => 'value',
                    ],
                ]
            ],
            'nested assignment' => [
                'lib.key = value',
                [
                    'lib.' => [
                        'key' => 'value',
                    ],
                ],
            ],
            'nested assignment with escaped key' => [
                'lib\\.key = value',
                [
                    'lib.key' => 'value',
                ],
            ],
            'nested assignment with escaped key and escaped dot at the beginning' => [
                '\\.lib\\.key = value',
                [
                    '.lib.key' => 'value',
                ],
            ],
            'nested assignment with protected escaped key' => [
                'lib\\\\.key = value',
                [
                    'lib\\.' => ['key' => 'value'],
                ],
            ],
            'nested assignment with protected escaped key and protected escaped dot at the beginning' => [
                '\\\\.lib\\\\.key = value',
                [
                    '\\.' => [
                        'lib\\.' => ['key' => 'value'],
                    ],
                ],
            ],
            'assignment with escaped an non escaped keys' => [
                'firstkey.secondkey\\.thirdkey.setting = value',
                [
                    'firstkey.' => [
                        'secondkey.thirdkey.' => [
                            'setting' => 'value'
                        ]
                    ]
                ]
            ],
            'nested structured assignment' => [
                'lib {' . LF .
                    'key = value' . LF .
                '}',
                [
                    'lib.' => [
                        'key' => 'value',
                    ],
                ],
            ],
            'nested structured assignment with escaped key inside' => [
                'lib {' . LF .
                    'key\\.nextkey = value' . LF .
                '}',
                [
                    'lib.' => [
                        'key.nextkey' => 'value',
                    ],
                ],
            ],
            'nested structured assignment with escaped key inside and escaped dots at the beginning' => [
                '\\.lib {' . LF .
                    '\\.key\\.nextkey = value' . LF .
                '}',
                [
                    '.lib.' => [
                        '.key.nextkey' => 'value',
                    ],
                ],
            ],
            'nested structured assignment with protected escaped key inside' => [
                'lib {' . LF .
                'key\\\\.nextkey = value' . LF .
                '}',
                [
                    'lib.' => [
                        'key\\.' => ['nextkey' => 'value'],
                    ],
                ],
            ],
            'nested structured assignment with protected escaped key inside and protected escaped dots at the beginning' => [
                '\\\\.lib {' . LF .
                    '\\\\.key\\\\.nextkey = value' . LF .
                '}',
                [
                    '\\.' => [
                        'lib.' => [
                            '\\.' => [
                                'key\\.' => ['nextkey' => 'value'],
                            ],
                        ],
                    ],
                ],
            ],
            'nested structured assignment with escaped key' => [
                'lib\\.anotherkey {' . LF .
                    'key = value' . LF .
                '}',
                [
                    'lib.anotherkey.' => [
                        'key' => 'value',
                    ],
                ],
            ],
            'nested structured assignment with protected escaped key' => [
                'lib\\\\.anotherkey {' . LF .
                'key = value' . LF .
                '}',
                [
                    'lib\\.' => [
                        'anotherkey.' => [
                            'key' => 'value',
                        ],
                    ],
                ],
            ],
            'multiline assignment' => [
                'key (' . LF .
                    'first' . LF .
                    'second' . LF .
                ')',
                [
                    'key' => 'first' . LF . 'second',
                ],
            ],
            'multiline assignment with escaped key' => [
                'key\\.nextkey (' . LF .
                    'first' . LF .
                    'second' . LF .
                ')',
                [
                    'key.nextkey' => 'first' . LF . 'second',
                ],
            ],
            'multiline assignment with protected escaped key' => [
                'key\\\\.nextkey (' . LF .
                'first' . LF .
                'second' . LF .
                ')',
                [
                    'key\\.' => ['nextkey' => 'first' . LF . 'second'],
                ],
            ],
            'copying values' => [
                'lib.default = value' . LF .
                'lib.copy < lib.default',
                [
                    'lib.' => [
                        'default' => 'value',
                        'copy' => 'value',
                    ],
                ],
            ],
            'copying values with escaped key' => [
                'lib\\.default = value' . LF .
                'lib.copy < lib\\.default',
                [
                    'lib.default' => 'value',
                    'lib.' => [
                        'copy' => 'value',
                    ],
                ],
            ],
            'copying values with protected escaped key' => [
                'lib\\\\.default = value' . LF .
                'lib.copy < lib\\\\.default',
                [
                    'lib\\.' => ['default' => 'value'],
                    'lib.' => [
                        'copy' => 'value',
                    ],
                ],
            ],
            'one-line hash comment' => [
                'first = 1' . LF .
                '# ignore = me' . LF .
                'second = 2',
                [
                    'first' => '1',
                    'second' => '2',
                ],
            ],
            'one-line slash comment' => [
                'first = 1' . LF .
                '// ignore = me' . LF .
                'second = 2',
                [
                    'first' => '1',
                    'second' => '2',
                ],
            ],
            'multi-line slash comment' => [
                'first = 1' . LF .
                '/*' . LF .
                    'ignore = me' . LF .
                '*/' . LF .
                'second = 2',
                [
                    'first' => '1',
                    'second' => '2',
                ],
            ],
            'nested assignment repeated segment names' => [
                'test.test.test = 1',
                [
                    'test.' => [
                        'test.' => [
                            'test' => '1',
                        ],
                    ]
                ],
            ],
            'simple assignment operator with tab character before "="' => [
                'test	 = someValue',
                [
                    'test' => 'someValue',
                ],
            ],
            'simple assignment operator character as value "="' => [
                'test ==TEST=',
                [
                    'test' => '=TEST=',
                ],
            ],
            'nested assignment operator character as value "="' => [
                'test.test ==TEST=',
                [
                    'test.' => [
                        'test' => '=TEST=',
                    ],
                ],
            ],
            'simple assignment character as value "<"' => [
                'test =<TEST>',
                [
                    'test' => '<TEST>',
                ],
            ],
            'nested assignment character as value "<"' => [
                'test.test =<TEST>',
                [
                    'test.' => [
                        'test' => '<TEST>',
                    ],
                ],
            ],
            'simple assignment character as value ">"' => [
                'test =>TEST<',
                [
                    'test' => '>TEST<',
                ],
            ],
            'nested assignment character as value ">"' => [
                'test.test =>TEST<',
                [
                    'test.' => [
                        'test' => '>TEST<',
                    ],
                ],
            ],
            'nested assignment repeated segment names with whitespaces' => [
                'test.test.test = 1' . " \t",
                [
                    'test.' => [
                        'test.' => [
                            'test' => '1',
                        ],
                    ]
                ],
            ],
            'simple assignment operator character as value "=" with whitespaces' => [
                'test = =TEST=' . " \t",
                [
                    'test' => '=TEST=',
                ],
            ],
            'nested assignment operator character as value "=" with whitespaces' => [
                'test.test = =TEST=' . " \t",
                [
                    'test.' => [
                        'test' => '=TEST=',
                    ],
                ],
            ],
            'simple assignment character as value "<" with whitespaces' => [
                'test = <TEST>' . " \t",
                [
                    'test' => '<TEST>',
                ],
            ],
            'nested assignment character as value "<" with whitespaces' => [
                'test.test = <TEST>' . " \t",
                [
                    'test.' => [
                        'test' => '<TEST>',
                    ],
                ],
            ],
            'simple assignment character as value ">" with whitespaces' => [
                'test = >TEST<' . " \t",
                [
                    'test' => '>TEST<',
                ],
            ],
            'nested assignment character as value ">" with whitespaces' => [
                'test.test = >TEST<',
                [
                    'test.' => [
                        'test' => '>TEST<',
                    ],
                ],
            ],
            'CSC example #1' => [
                'linkParams.ATagParams.dataWrap =  class="{$styles.content.imgtext.linkWrap.lightboxCssClass}" rel="{$styles.content.imgtext.linkWrap.lightboxRelAttribute}"',
                [
                    'linkParams.' => [
                        'ATagParams.' => [
                            'dataWrap' => 'class="{$styles.content.imgtext.linkWrap.lightboxCssClass}" rel="{$styles.content.imgtext.linkWrap.lightboxRelAttribute}"',
                        ],
                    ],
                ],
            ],
            'CSC example #2' => [
                'linkParams.ATagParams {' . LF .
                    'dataWrap = class="{$styles.content.imgtext.linkWrap.lightboxCssClass}" rel="{$styles.content.imgtext.linkWrap.lightboxRelAttribute}"' . LF .
                '}',
                [
                    'linkParams.' => [
                        'ATagParams.' => [
                            'dataWrap' => 'class="{$styles.content.imgtext.linkWrap.lightboxCssClass}" rel="{$styles.content.imgtext.linkWrap.lightboxRelAttribute}"',
                        ],
                    ],
                ],
            ],
            'CSC example #3' => [
                'linkParams.ATagParams.dataWrap (' . LF .
                    'class="{$styles.content.imgtext.linkWrap.lightboxCssClass}" rel="{$styles.content.imgtext.linkWrap.lightboxRelAttribute}"' . LF .
                ')',
                [
                    'linkParams.' => [
                        'ATagParams.' => [
                            'dataWrap' => 'class="{$styles.content.imgtext.linkWrap.lightboxCssClass}" rel="{$styles.content.imgtext.linkWrap.lightboxRelAttribute}"',
                        ],
                    ],
                ],
            ],
            'key with colon' => [
                'some:key = is valid',
                [
                    'some:key' => 'is valid'
                ]
            ],
            'special operator' => [
                'some := addToList(a)',
                [
                    'some' => 'a'
                ]
            ],
            'special operator with white-spaces' => [
                'some := addToList (a)',
                [
                    'some' => 'a'
                ]
            ],
            'special operator with tabs' => [
                'some :=	addToList	(a)',
                [
                    'some' => 'a'
                ]
            ],
            'special operator with white-spaces and tabs in value' => [
                'some := addToList( a, b,	c )',
                [
                    'some' => 'a, b,	c'
                ]
            ],
            'special operator and colon, no spaces' => [
                'some:key:=addToList(a)',
                [
                    'some:key' => 'a'
                ]
            ],
            'key with all special symbols' => [
                'someSpecial\\_:-\\.Chars = is valid',
                [
                    'someSpecial\\_:-.Chars' => 'is valid'
                ]
            ],
        ];
    }

    /**
     * @test
     */
    public function setValCanBeCalledWithArrayValueParameter()
    {
        $string = '';
        $setup = [];
        $value = [];
        $this->typoScriptParser->setVal($string, $setup, $value);
    }

    /**
     * @test
     */
    public function setValCanBeCalledWithStringValueParameter()
    {
        $string = '';
        $setup = [];
        $value = '';
        $this->typoScriptParser->setVal($string, $setup, $value);
    }

    /**
     * @test
     * @dataProvider parseNextKeySegmentReturnsCorrectNextKeySegmentDataProvider
     */
    public function parseNextKeySegmentReturnsCorrectNextKeySegment($key, $expectedKeySegment, $expectedRemainingKey)
    {
        list($keySegment, $remainingKey) = $this->typoScriptParser->_call('parseNextKeySegment', $key);
        $this->assertSame($expectedKeySegment, $keySegment);
        $this->assertSame($expectedRemainingKey, $remainingKey);
    }

    /**
     * @return array
     */
    public function parseNextKeySegmentReturnsCorrectNextKeySegmentDataProvider()
    {
        return [
            'key without separator' => [
                'testkey',
                'testkey',
                ''
            ],
            'key with normal separator' => [
                'test.key',
                'test',
                'key'
            ],
            'key with multiple normal separators' => [
                'test.key.subkey',
                'test',
                'key.subkey'
            ],
            'key with separator and escape character' => [
                'te\\st.test',
                'te\\st',
                'test'
            ],
            'key with escaped separators' => [
                'test\\.key\\.subkey',
                'test.key.subkey',
                ''
            ],
            'key with escaped and unescaped separator 1' => [
                'test.test\\.key',
                'test',
                'test\\.key'
            ],
            'key with escaped and unescaped separator 2' => [
                'test\\.test.key\\.key2',
                'test.test',
                'key\\.key2'
            ],
            'key with escaped escape character' => [
                'test\\\\.key',
                'test\\',
                'key'
            ],
            'key with escaped separator and additional escape character' => [
                'test\\\\\\.key',
                'test\\\\',
                'key'
            ],

            'multiple escape characters within the key are preserved' => [
                'te\\\\st\\\\.key',
                'te\\\\st\\',
                'key'
            ]
        ];
    }
}
