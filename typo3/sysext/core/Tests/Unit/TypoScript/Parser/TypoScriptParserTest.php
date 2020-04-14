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

namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\Parser;

use Prophecy\Argument;
use TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching\ConditionMatcher;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class TypoScriptParserTest extends UnitTestCase
{
    /**
     * @var TypoScriptParser|AccessibleObjectInterface
     */
    protected $typoScriptParser;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->typoScriptParser = $this->getAccessibleMock(TypoScriptParser::class, ['dummy']);
    }

    /**
     * Tear down
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * Data provider for executeValueModifierReturnsModifiedResult
     *
     * @return array modifier name, modifier arguments, current value, expected result
     */
    public function executeValueModifierDataProvider(): array
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
     * @param string $modifierName
     * @param string $currentValue
     * @param string $modifierArgument
     * @param string $expected
     */
    public function executeValueModifierReturnsModifiedResult(
        string $modifierName,
        string $currentValue,
        string $modifierArgument,
        string $expected
    ): void {
        $actualValue = $this->typoScriptParser->_call(
            'executeValueModifier',
            $modifierName,
            $modifierArgument,
            $currentValue
        );
        self::assertEquals($expected, $actualValue);
    }

    public function executeGetEnvModifierDataProvider(): array
    {
        return [
            'environment variable not set' => [
                [],
                'bar',
                'FOO',
                null,
            ],
            'empty environment variable' => [
                ['FOO' => ''],
                'bar',
                'FOO',
                '',
            ],
            'empty current value' => [
                ['FOO' => 'baz'],
                null,
                'FOO',
                'baz',
            ],
            'environment variable and current value set' => [
                ['FOO' => 'baz'],
                'bar',
                'FOO',
                'baz',
            ],
            'neither environment variable nor current value set' => [
                [],
                null,
                'FOO',
                null,
            ],
            'empty environment variable name' => [
                ['FOO' => 'baz'],
                'bar',
                '',
                null,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider executeGetEnvModifierDataProvider
     * @param string $modifierName
     * @param string $currentValue
     * @param string $modifierArgument
     * @param string $expected
     */
    public function executeGetEnvModifierReturnsModifiedResult(
        array $environmentVariables,
        ?string $currentValue,
        string $modifierArgument,
        ?string $expected
    ): void {
        foreach ($environmentVariables as $environmentVariable => $value) {
            putenv($environmentVariable . '=' . $value);
        }
        $actualValue = $this->typoScriptParser->_call(
            'executeValueModifier',
            'getEnv',
            $modifierArgument,
            $currentValue
        );
        self::assertEquals($expected, $actualValue);
        foreach ($environmentVariables as $environmentVariable => $_) {
            putenv($environmentVariable);
        }
    }

    /**
     * Data provider for executeValueModifierThrowsException
     *
     * @return array modifier name, modifier arguments, current value, expected result
     */
    public function executeValueModifierInvalidDataProvider(): array
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
     * @param string $modifierName
     * @param string $currentValue
     * @param string $modifierArgument
     */
    public function executeValueModifierThrowsException(
        string $modifierName,
        string $currentValue,
        string $modifierArgument
    ): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1438191758);
        $this->typoScriptParser->_call('executeValueModifier', $modifierName, $modifierArgument, $currentValue);
    }

    /**
     * @test
     */
    public function invalidCharactersInObjectNamesAreReported(): void
    {
        $timeTrackerProphecy = $this->prophesize(TimeTracker::class);
        GeneralUtility::setSingletonInstance(TimeTracker::class, $timeTrackerProphecy->reveal());

        $typoScript = '$.10 = invalid';
        $this->typoScriptParser->parse($typoScript);
        $expected = 'Line 0: Object Name String, "$.10" contains invalid character "$". Must be alphanumeric or one of: "_:-\."';
        self::assertEquals($expected, $this->typoScriptParser->errors[0][0]);
    }

    public function invalidConditionsDataProvider(): array
    {
        return [
            '[1 == 1]a' => ['[1 == 1]a', false],
            '[1 == 1] # a comment' => ['[1 == 1] # a comment', false],
            '[1 == 1]' => ['[1 == 1]', true],
        ];
    }

    /**
     * @test
     * @dataProvider invalidConditionsDataProvider
     * @param string $condition
     * @param bool $isValid
     */
    public function invalidConditionsAreReported(string $condition, bool $isValid): void
    {
        $timeTrackerProphecy = $this->prophesize(TimeTracker::class);
        GeneralUtility::setSingletonInstance(TimeTracker::class, $timeTrackerProphecy->reveal());

        $this->typoScriptParser->parse($condition);
        if (!$isValid) {
            $expected = 'Line 0: Invalid condition found, any condition must end with "]": ' . $condition;
            self::assertEquals($expected, $this->typoScriptParser->errors[0][0]);
        }
    }

    /**
     * @test
     */
    public function emptyConditionIsReported(): void
    {
        $timeTrackerProphecy = $this->prophesize(TimeTracker::class);
        GeneralUtility::setSingletonInstance(TimeTracker::class, $timeTrackerProphecy->reveal());

        $typoScript = '[]';
        $this->typoScriptParser->parse($typoScript);
        $expected = 'Empty condition is always false, this does not make sense. At line 0';
        self::assertEquals($expected, $this->typoScriptParser->errors[0][0]);
    }

    /**
     * @return array
     */
    public function doubleSlashCommentsDataProvider(): array
    {
        return [
            'valid, without spaces' => ['// valid, without spaces'],
            'valid, with one space' => [' // valid, with one space'],
            'valid, with multiple spaces' => ['  // valid, with multiple spaces'],
        ];
    }

    /**
     * @test
     * @dataProvider doubleSlashCommentsDataProvider
     * @param string $typoScript
     */
    public function doubleSlashCommentsAreValid(string $typoScript): void
    {
        $this->typoScriptParser->parse($typoScript);
        self::assertEmpty($this->typoScriptParser->errors);
    }

    /**
     * @return array
     */
    public function includeFileDataProvider(): array
    {
        return [
            'TS code before not matching include' => [
                '
                foo = bar
                <INCLUDE_TYPOSCRIPT: source="FILE:dev.ts" condition="applicationContext matches \"/^NotMatched/\"">
                '
            ],
            'TS code after not matching include' => [
                '
                <INCLUDE_TYPOSCRIPT: source="FILE:dev.ts" condition="applicationContext matches \"/^NotMatched/\"">
                foo = bar
                '
            ],
        ];
    }

    /**
     * @test
     * @dataProvider includeFileDataProvider
     * @param string $typoScript
     */
    public function includeFilesWithConditions(string $typoScript): void
    {
        // This test triggers a BackendUtility::BEgetRootLine() down below, we need to suppress the cache call
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        $cacheProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('runtime')->willReturn($cacheProphecy->reveal());
        $cacheProphecy->get(Argument::cetera())->willReturn(false);
        $cacheProphecy->set(Argument::cetera())->willReturn(false);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());

        $p = $this->prophesize(ConditionMatcher::class);
        $p->match(Argument::cetera())->willReturn(false);
        GeneralUtility::addInstance(ConditionMatcher::class, $p->reveal());

        $resolvedIncludeLines = TypoScriptParser::checkIncludeLines($typoScript);
        self::assertStringContainsString('foo = bar', $resolvedIncludeLines);
        self::assertStringNotContainsString('INCLUDE_TYPOSCRIPT', $resolvedIncludeLines);
    }

    /**
     * @return array
     */
    public function importFilesDataProvider(): array
    {
        return [
            'Found include file as single file is imported' => [
                // Input TypoScript
                '@import "EXT:core/Tests/Unit/TypoScript/Fixtures/ext_typoscript_setup.txt"'
                ,
                // Expected
                '
### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/ext_typoscript_setup.txt\' begin ###
test.Core.TypoScript = 1
### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/ext_typoscript_setup.txt\' end ###
'
            ],
            'Found include file is imported' => [
                // Input TypoScript
                'bennilove = before
@import "EXT:core/Tests/Unit/TypoScript/Fixtures/ext_typoscript_setup.txt"
'
                ,
                // Expected
                '
bennilove = before

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/ext_typoscript_setup.txt\' begin ###
test.Core.TypoScript = 1
### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/ext_typoscript_setup.txt\' end ###
'
            ],
            'Not found file is not imported' => [
                // Input TypoScript
                'bennilove = before
@import "EXT:core/Tests/Unit/TypoScript/Fixtures/notfoundfile.txt"
'
                ,
                // Expected
                '
bennilove = before

###
### ERROR: No file or folder found for importing TypoScript on "EXT:core/Tests/Unit/TypoScript/Fixtures/notfoundfile.txt".
###
'
            ],
            'All files with glob are imported' => [
                // Input TypoScript
                'bennilove = before
@import "EXT:core/Tests/Unit/TypoScript/Fixtures/*.txt"
'
                ,
                // Expected
                '
bennilove = before

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/ext_typoscript_setup.txt\' begin ###
test.Core.TypoScript = 1
### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/ext_typoscript_setup.txt\' end ###
'
            ],
            'Specific file with typoscript ending is imported' => [
                // Input TypoScript
                'bennilove = before
@import "EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript"
'
                ,
                // Expected
                '
bennilove = before

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' begin ###
test.TYPO3Forever.TypoScript = 1

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' end ###
'
            ],
            'All files in folder are imported, sorted by name' => [
                // Input TypoScript
                'bennilove = before
@import "EXT:core/Tests/Unit/TypoScript/Fixtures/"
'
                ,
                // Expected
                '
bennilove = before

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/recursive_includes_setup.typoscript\' begin ###

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' begin ###
test.TYPO3Forever.TypoScript = 1

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' end ###

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/recursive_includes_setup.typoscript\' end ###


### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' begin ###
test.TYPO3Forever.TypoScript = 1

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' end ###
'
            ],
            'All files ending with typoscript in folder are imported' => [
                // Input TypoScript
                'bennilove = before
@import "EXT:core/Tests/Unit/TypoScript/Fixtures/*typoscript"
'
                ,
                // Expected
                '
bennilove = before

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/recursive_includes_setup.typoscript\' begin ###

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' begin ###
test.TYPO3Forever.TypoScript = 1

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' end ###

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/recursive_includes_setup.typoscript\' end ###


### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' begin ###
test.TYPO3Forever.TypoScript = 1

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' end ###
'
            ],
            'All typoscript files in folder are imported' => [
                // Input TypoScript
                'bennilove = before
@import "EXT:core/Tests/Unit/TypoScript/Fixtures/*.typoscript"
'
                ,
                // Expected
                '
bennilove = before

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/recursive_includes_setup.typoscript\' begin ###

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' begin ###
test.TYPO3Forever.TypoScript = 1

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' end ###

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/recursive_includes_setup.typoscript\' end ###


### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' begin ###
test.TYPO3Forever.TypoScript = 1

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' end ###
'
            ],
            'All typoscript files in folder with glob are not imported due to recursion level=0' => [
                // Input TypoScript
                'bennilove = before
@import "EXT:core/Tests/Unit/**/*.typoscript"
'
                ,
                // Expected
                '
bennilove = before

###
### ERROR: No file or folder found for importing TypoScript on "EXT:core/Tests/Unit/**/*.typoscript".
###
'
            ],
            'TypoScript file ending is automatically added' => [
                // Input TypoScript
                'bennilove = before
@import "EXT:core/Tests/Unit/TypoScript/Fixtures/setup"
'
                ,
                // Expected
                '
bennilove = before

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' begin ###
test.TYPO3Forever.TypoScript = 1

### @import \'EXT:core/Tests/Unit/TypoScript/Fixtures/setup.typoscript\' end ###
'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider importFilesDataProvider
     * @param string $typoScript
     * @param string $expected
     */
    public function importFiles(string $typoScript, string $expected): void
    {
        $resolvedIncludeLines = TypoScriptParser::checkIncludeLines($typoScript);
        self::assertEquals($expected, $resolvedIncludeLines);
    }

    /**
     * @param string $typoScript
     * @param array $expected
     * @dataProvider typoScriptIsParsedToArrayDataProvider
     * @test
     */
    public function typoScriptIsParsedToArray(string $typoScript, array $expected): void
    {
        $this->typoScriptParser->parse($typoScript);
        self::assertEquals($expected, $this->typoScriptParser->setup);
    }

    /**
     * @return array
     */
    public function typoScriptIsParsedToArrayDataProvider(): array
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
            'multi-line slash comment in one line' => [
                'first = 1' . LF .
                '/* ignore = me   */' . LF .
                '/**** ignore = me   **/' . LF .
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
    public function setValCanBeCalledWithArrayValueParameter(): void
    {
        $string = '';
        $setup = [];
        $value = [];
        $typoScriptParser = new TypoScriptParser();
        $mock = \Closure::bind(
            static function (TypoScriptParser $typoScriptParser) use ($string, &$setup, $value) {
                return $typoScriptParser->setVal($string, $setup, $value);
            },
            null,
            TypoScriptParser::class
        );
        $mock($typoScriptParser);
    }

    /**
     * @test
     */
    public function setValCanBeCalledWithStringValueParameter(): void
    {
        $string = '';
        $setup = [];
        $value = '';
        $typoScriptParser = new TypoScriptParser();
        $mock = \Closure::bind(
            static function (TypoScriptParser $typoScriptParser) use ($string, &$setup, $value) {
                return $typoScriptParser->setVal($string, $setup, $value);
            },
            null,
            TypoScriptParser::class
        );
        $mock($typoScriptParser);
    }

    /**
     * @test
     * @dataProvider parseNextKeySegmentReturnsCorrectNextKeySegmentDataProvider
     * @param string $key
     * @param string $expectedKeySegment
     * @param string $expectedRemainingKey
     */
    public function parseNextKeySegmentReturnsCorrectNextKeySegment(
        string $key,
        string $expectedKeySegment,
        string $expectedRemainingKey
    ): void {
        [$keySegment, $remainingKey] = $this->typoScriptParser->_call('parseNextKeySegment', $key);
        self::assertSame($expectedKeySegment, $keySegment);
        self::assertSame($expectedRemainingKey, $remainingKey);
    }

    /**
     * @return array
     */
    public function parseNextKeySegmentReturnsCorrectNextKeySegmentDataProvider(): array
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
