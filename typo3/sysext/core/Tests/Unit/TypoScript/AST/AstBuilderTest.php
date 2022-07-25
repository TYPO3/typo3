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

namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\AST;

use TYPO3\CMS\Core\Tests\Unit\Fixtures\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\TypoScript\AST\AstBuilder;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\ReferenceChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\Tokenizer\LosslessTokenizer;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\ConstantAwareTokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\IdentifierToken;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\IdentifierTokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AstBuilderTest extends UnitTestCase
{
    public function buildDataProvider(): \Generator
    {
        $expectedAst = new RootNode();
        yield 'ignore comment line' => [
            '# comment',
            $expectedAst,
            [],
        ];

        $expectedAst = new RootNode();
        yield 'ignore invalid line' => [
            'invalid',
            $expectedAst,
            [],
        ];

        $expectedAst = new RootNode();
        $objectNode = new ChildNode('foo');
        $objectNode->setValue('bar');
        $expectedAst->addChild($objectNode);
        yield 'single object assignment' => [
            'foo = bar',
            $expectedAst,
            [
                'foo' => 'bar',
            ],
        ];

        $expectedAst = new RootNode();
        $objectNode = new ChildNode('foo');
        $objectNode->setValue('');
        $expectedAst->addChild($objectNode);
        yield 'single object assignment with empty string as value' => [
            'foo =',
            $expectedAst,
            [
                'foo' => '',
            ],
        ];

        $expectedAst = new RootNode();
        $objectNode = new ChildNode('foo');
        $objectNode->setValue('0');
        $expectedAst->addChild($objectNode);
        yield 'single object assignment with zero as value' => [
            'foo = 0',
            $expectedAst,
            [
                'foo' => '0',
            ],
        ];

        $expectedAst = new RootNode();
        $nestedObjectNode = new ChildNode('bar');
        $nestedObjectNode->setValue('baz');
        $objectNode = new ChildNode('foo');
        $objectNode->addChild($nestedObjectNode);
        $expectedAst->addChild($objectNode);
        yield 'nested object assignment' => [
            'foo.bar = baz',
            $expectedAst,
            [
                'foo.' => [
                    'bar' => 'baz',
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $objectNode = new ChildNode('foo');
        $objectNode->setValue('bar2');
        $expectedAst->addChild($objectNode);
        yield 'override simple value assignment' => [
            "foo = bar\n" .
            'foo = bar2',
            $expectedAst,
            [
                'foo' => 'bar2',
            ],
        ];

        $expectedAst = new RootNode();
        $nestedObjectNode = new ChildNode('bar');
        $nestedObjectNode->setValue('baz2');
        $objectNode = new ChildNode('foo');
        $objectNode->addChild($nestedObjectNode);
        $expectedAst->addChild($objectNode);
        yield 'override nested value assignment' => [
            "foo.bar = baz\n" .
            'foo.bar = baz2',
            $expectedAst,
            [
                'foo.' => [
                    'bar' => 'baz2',
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $nestedObjectNode = new ChildNode('bar');
        $nestedObjectNode->setValue('baz');
        $objectNode = new ChildNode('foo');
        $objectNode->addChild($nestedObjectNode);
        $expectedAst->addChild($objectNode);
        yield 'simple curly brackets with assignments' => [
            "foo {\n" .
            "  bar = baz\n" .
            '}',
            $expectedAst,
            [
                'foo.' => [
                    'bar' => 'baz',
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $objectNode = new ChildNode('foo');
        $expectedAst->addChild($objectNode);
        $nestedObjectNode = new ChildNode('bar');
        $nestedObjectNode->setValue('barValue');
        $objectNode->addChild($nestedObjectNode);
        $nestedObjectNode = new ChildNode('baz');
        $nestedObjectNode->setValue('bazValue');
        $objectNode->addChild($nestedObjectNode);
        yield 'simple curly brackets with two assignments' => [
            "foo {\n" .
            "  bar = barValue\n" .
            "  baz = bazValue\n" .
            '}',
            $expectedAst,
            [
                'foo.' => [
                    'bar' => 'barValue',
                    'baz' => 'bazValue',
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $bazNode = new ChildNode('baz');
        $bazNode->setValue('baz1');
        $barNode = new ChildNode('bar');
        $barNode->setValue('bar1');
        $barNode->addChild($bazNode);
        $foobazNode = new ChildNode('foobaz');
        $foobazNode->setValue('foobaz1');
        $foobarNode = new ChildNode('foobar');
        $foobarNode->setValue('foobar1');
        $foobarNode->addChild($foobazNode);
        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo1');
        $fooNode->addChild($barNode);
        $fooNode->addChild($foobarNode);
        $expectedAst->addChild($fooNode);
        yield 'nested curly brackets assignments' => [
            "foo = foo1\n" .
            "foo {\n" .
            "  bar = bar1\n" .
            "  bar {\n" .
            "    baz = baz1\n" .
            "  }\n" .
            "  foobar = foobar1\n" .
            "  foobar {\n" .
            "    foobaz = foobaz1\n" .
            "  }\n" .
            '}',
            $expectedAst,
            [
                'foo' => 'foo1',
                'foo.' => [
                    'bar' => 'bar1',
                    'bar.' => [
                        'baz' => 'baz1',
                    ],
                    'foobar' => 'foobar1',
                    'foobar.' => [
                        'foobaz' => 'foobaz1',
                    ],
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $bazNode = new ChildNode('baz');
        $bazNode->setValue('baz2');
        $barNode = new ChildNode('bar');
        $barNode->setValue('bar1');
        $barNode->addChild($bazNode);
        $foobazNode = new ChildNode('foobaz');
        $foobazNode->setValue('foobaz2');
        $foobarNode = new ChildNode('foobar');
        $foobarNode->setValue('foobar1');
        $foobarNode->addChild($foobazNode);
        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo2');
        $fooNode->addChild($barNode);
        $fooNode->addChild($foobarNode);
        $expectedAst->addChild($fooNode);
        yield 'nested curly brackets assignments with re-assignments' => [
            "foo = foo1\n" .
            "foo {\n" .
            "  bar = bar1\n" .
            "  bar {\n" .
            "    baz = baz1\n" .
            "  }\n" .
            "  bar.baz = baz2\n" .
            "  foobar = foobar1\n" .
            "  foobar {\n" .
            "    foobaz = foobaz1\n" .
            "  }\n" .
            "  foobar.foobaz = foobaz2\n" .
            "}\n" .
            'foo = foo2',
            $expectedAst,
            [
                'foo' => 'foo2',
                'foo.' => [
                    'bar' => 'bar1',
                    'bar.' => [
                        'baz' => 'baz2',
                    ],
                    'foobar' => 'foobar1',
                    'foobar.' => [
                        'foobaz' => 'foobaz2',
                    ],
                ],
            ],
        ];

        $fooNode = new ChildNode('foo');
        $barNode = new ChildNode('bar');
        $barNode->setValue('bar1');
        $fooNode->addChild($barNode);
        $bazNode = new ChildNode('baz');
        $bazNode->setValue('baz1');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        $expectedAst->addChild($bazNode);
        yield 'nested curly brackets in excess does not crash' => [
            "foo {\n" .
            "  bar = bar1\n" .
            "}\n" .
            "}\n" .
            'baz = baz1',
            $expectedAst,
            [
                'foo.' => [
                    'bar' => 'bar1',
                ],
                'baz' => 'baz1',
            ],
        ];

        $expectedAst = new RootNode();
        yield 'multiline assignment empty' => [
            'foo ()',
            $expectedAst,
            [
                // @todo: This creates an empty value "'foo' => ''" with old parser. Not sure if this is a problem?!
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo1');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'multiline assignment one-line string' => [
            'foo (foo1)',
            $expectedAst,
            [
                'foo' => 'foo1',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue("  line1Value\n  line2Value");
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'multiline assignment multi-line string' => [
            "foo (\n" .
            "  line1Value\n" .
            "  line2Value\n" .
            ')',
            $expectedAst,
            [
                'foo' => "  line1Value\n  line2Value",
            ],
        ];

        $expectedAst = new RootNode();
        $keepNode = new ChildNode('keep');
        $keepNode->setValue('keep1');
        $expectedAst->addChild($keepNode);
        yield 'top level unset removes node' => [
            "foo = foo\n" .
            "foo.bar = bar1\n" .
            "keep = keep1\n" .
            "foo >\n",
            $expectedAst,
            [
                'keep' => 'keep1',
            ],
        ];

        $expectedAst = new RootNode();
        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo1');
        $expectedAst->addChild($fooNode);
        yield 'top level unset does not choke on not existing node' => [
            "foo = foo1\n" .
            "bar >\n",
            $expectedAst,
            [
                'foo' => 'foo1',
            ],
        ];

        $expectedAst = new RootNode();
        $fooNode = new ChildNode('foo');
        $keepNode = new ChildNode('keep');
        $keepNode->setValue('keep1');
        $expectedAst->addChild($keepNode);
        $expectedAst->addChild($fooNode);
        yield 'nested unset removes nodes' => [
            "foo\n" .
            "foo.bar = bar1\n" .
            "keep = keep1\n" .
            'foo.bar >',
            $expectedAst,
            [
                'keep' => 'keep1',
            ],
        ];

        $expectedAst = new RootNode();
        $fooNode = new ChildNode('foo');
        $expectedAst->addChild($fooNode);
        $keepNode = new ChildNode('keep');
        $keepNode->setValue('keep1');
        $fooNode->addChild($keepNode);
        yield 'nested unset removes correct nodes with same name 1' => [
            "foo\n" .
            "foo.foo = foo1\n" .
            "foo.keep = keep1\n" .
            'foo.foo >',
            $expectedAst,
            [
                'foo.' => [
                    'keep' => 'keep1',
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $fooNode = new ChildNode('foo');
        $expectedAst->addChild($fooNode);
        $fooSubNode = new ChildNode('foo');
        $fooNode->addChild($fooSubNode);
        $keepNode = new ChildNode('keep');
        $keepNode->setValue('keep1');
        $fooNode->addChild($keepNode);
        yield 'nested unset removes correct nodes with same name 2' => [
            "foo\n" .
            "foo.foo.foo = foo2\n" .
            "foo.keep = keep1\n" .
            'foo.foo.foo >',
            $expectedAst,
            [
                'foo.' => [
                    'keep' => 'keep1',
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $barNode = new ChildNode('bar');
        $barNode->setValue('bar1');
        $fooNode = new ChildNode('foo');
        $fooNode->addChild($barNode);
        $expectedAst->addChild($fooNode);
        yield 'nested unset does not choke on not existing node' => [
            "foo\n" .
            "foo.bar = bar1\n" .
            'foo.baz >',
            $expectedAst,
            [
                'foo.' => [
                    'bar' => 'bar1',
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $fooNode = new ChildNode('foo');
        $expectedAst->addChild($fooNode);
        $keepNode = new ChildNode('keep');
        $keepNode->setValue('keep1');
        $fooNode->addChild($keepNode);
        yield 'nested unset removes nodes using curly brackets' => [
            "foo {\n" .
            "  bar = bar1\n" .
            "  bar.baz = baz1\n" .
            "  keep = keep1\n" .
            "}\n" .
            'foo.bar >',
            $expectedAst,
            [
                'foo.' => [
                    'keep' => 'keep1',
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $fooNode = new ChildNode('foo');
        $expectedAst->addChild($fooNode);
        $keepNode = new ChildNode('keep');
        $keepNode->setValue('keep1');
        $fooNode->addChild($keepNode);
        yield 'nested unset removes nodes using curly brackets and unset in bracket' => [
            "foo {\n" .
            "  bar = bar1\n" .
            "  bar.baz = baz1\n" .
            "  keep = keep1\n" .
            "  bar >\n" .
            "}\n",
            $expectedAst,
            [
                'foo.' => [
                    'keep' => 'keep1',
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo1');
        $barNode = new ChildNode('bar');
        $barNode->setValue('foo1');
        $expectedAst->addChild($fooNode);
        $expectedAst->addChild($barNode);
        yield 'copy operator copies node' => [
            "foo = foo1\n" .
            'bar < foo',
            $expectedAst,
            [
                'foo' => 'foo1',
                'bar' => 'foo1',
            ],
        ];

        $expectedAst = new RootNode();
        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo1');
        $expectedAst->addChild($fooNode);
        yield 'copy operator when source does not exist does not add node' => [
            "foo = foo1\n" .
            'bar < baz',
            $expectedAst,
            [
                'foo' => 'foo1',
            ],
        ];

        $expectedAst = new RootNode();
        $bar1Node = new ChildNode('bar');
        $bar1Node->setValue('bar1');
        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo1');
        $fooNode->addChild($bar1Node);
        $bar2Node = new ChildNode('bar');
        $bar2Node->setValue('bar1');
        $bazNode = new ChildNode('baz');
        $bazNode->setValue('foo1');
        $bazNode->addChild($bar2Node);
        $expectedAst->addChild($fooNode);
        $expectedAst->addChild($bazNode);
        yield 'copy operator copies tree' => [
            "foo = foo1\n" .
            "foo.bar = bar1\n" .
            'baz < foo',
            $expectedAst,
            [
                'foo' => 'foo1',
                'foo.' => [
                    'bar' => 'bar1',
                ],
                'baz' => 'foo1',
                'baz.' => [
                    'bar' => 'bar1',
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $libNode = new ChildNode('lib');
        $expectedAst->addChild($libNode);
        $myLibNode = new ChildNode('myLib');
        $libNode->addChild($myLibNode);
        $barNode = new ChildNode('bar');
        $myLibNode->addChild($barNode);
        $barNode->setValue('barValue');
        $fooNode = new ChildNode('foo');
        $expectedAst->addChild($fooNode);
        $fooNode->setValue('fooValue');
        $fooNode->addChild(clone $barNode);
        yield 'copy operator keeps target value if source has none on top level' => [
            "lib.myLib {\n" .
            "    bar = barValue\n" .
            "}\n" .
            "foo = fooValue\n" .
            'foo < lib.myLib',
            $expectedAst,
            [
                'lib.' => [
                    'myLib.' => [
                        'bar' => 'barValue',
                    ],
                ],
                'foo' => 'fooValue',
                'foo.' => [
                    'bar' => 'barValue',
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $libNode = new ChildNode('lib');
        $expectedAst->addChild($libNode);
        $myLibNode = new ChildNode('myLib');
        $libNode->addChild($myLibNode);
        $barNode = new ChildNode('bar');
        $myLibNode->addChild($barNode);
        $barNode->setValue('barValue');
        $fooNode = new ChildNode('foo');
        $expectedAst->addChild($fooNode);
        $bazNode = new ChildNode('baz');
        $fooNode->addChild($bazNode);
        $bazNode->setValue('bazValue');
        $bazNode->addChild(clone $barNode);
        yield 'copy operator keeps target value if source has none on second level' => [
            "lib.myLib {\n" .
            "    bar = barValue\n" .
            "}\n" .
            "foo.baz = bazValue\n" .
            'foo.baz < lib.myLib',
            $expectedAst,
            [
                'lib.' => [
                    'myLib.' => [
                        'bar' => 'barValue',
                    ],
                ],
                'foo.' => [
                    'baz' => 'bazValue',
                    'baz.' => [
                        'bar' => 'barValue',
                    ],
                ],
            ],
        ];

        $level1FirstNode = new ChildNode('level1First');
        $level1FirstNode->setValue('level1FirstValue');
        $level2FirstNode = new ChildNode('level2First');
        $level2FirstNode->setValue('level2FirstValue');
        $level1FirstNode->addChild($level2FirstNode);
        $level1SecondNode = new ChildNode('level1Second');
        $level1SecondNode->setValue('level2FirstValue');
        $expectedAst = new RootNode();
        $expectedAst->addChild($level1FirstNode);
        $expectedAst->addChild($level1SecondNode);
        yield 'copy operator copies sub tree' => [
            "level1First = level1FirstValue\n" .
            "level1First.level2First = level2FirstValue\n" .
            'level1Second < level1First.level2First',
            $expectedAst,
            [
                'level1First' => 'level1FirstValue',
                'level1First.' => [
                    'level2First' => 'level2FirstValue',
                ],
                'level1Second' => 'level2FirstValue',
            ],
        ];

        $level1FirstNode = new ChildNode('level1First');
        $level1FirstNode->setValue('level1FirstValue');
        $level2FirstNode = new ChildNode('level2First');
        $level2FirstNode->setValue('level2FirstValue');
        $level1FirstNode->addChild($level2FirstNode);
        $level1SecondNode = new ChildNode('level1Second');
        $level1SecondNode->setValue('level1SecondValue');
        $level2SecondNode = new ChildNode('level2Second');
        $level2SecondNode->setValue('level2FirstValue');
        $level1SecondNode->addChild($level2SecondNode);
        $expectedAst = new RootNode();
        $expectedAst->addChild($level1FirstNode);
        $expectedAst->addChild($level1SecondNode);
        yield 'copy operator copies sub tree in nested path' => [
            "level1First = level1FirstValue\n" .
            "level1First.level2First = level2FirstValue\n" .
            "level1Second = level1SecondValue\n" .
            'level1Second.level2Second < level1First.level2First',
            $expectedAst,
            [
                'level1First' => 'level1FirstValue',
                'level1First.' => [
                    'level2First' => 'level2FirstValue',
                ],
                'level1Second' => 'level1SecondValue',
                'level1Second.' => [
                    'level2Second' => 'level2FirstValue',
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $bar1Node = new ChildNode('bar');
        $bar1Node->setValue('barChanged');
        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo1');
        $fooNode->addChild($bar1Node);
        $bar2Node = new ChildNode('bar');
        $bar2Node->setValue('bar1');
        $bazNode = new ChildNode('baz');
        $bazNode->setValue('foo1');
        $bazNode->addChild($bar2Node);
        $expectedAst->addChild($fooNode);
        $expectedAst->addChild($bazNode);
        yield 'copy operator copies tree and dereferences child when changing source tree' => [
            "foo = foo1\n" .
            "foo.bar = bar1\n" .
            "baz < foo\n" .
            'foo.bar = barChanged',
            $expectedAst,
            [
                'foo' => 'foo1',
                'foo.' => [
                    'bar' => 'barChanged',
                ],
                'baz' => 'foo1',
                'baz.' => [
                    'bar' => 'bar1',
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $bar1Node = new ChildNode('bar');
        $bar1Node->setValue('bar1');
        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo1');
        $fooNode->addChild($bar1Node);
        $bar2Node = new ChildNode('bar');
        $bar2Node->setValue('barChanged');
        $bazNode = new ChildNode('baz');
        $bazNode->setValue('foo1');
        $bazNode->addChild($bar2Node);
        $expectedAst->addChild($fooNode);
        $expectedAst->addChild($bazNode);
        yield 'copy operator copies tree and dereferences child when changing target tree' => [
            "foo = foo1\n" .
            "foo.bar = bar1\n" .
            "baz < foo\n" .
            'baz.bar = barChanged',
            $expectedAst,
            [
                'foo' => 'foo1',
                'foo.' => [
                    'bar' => 'bar1',
                ],
                'baz' => 'foo1',
                'baz.' => [
                    'bar' => 'barChanged',
                ],
            ],
        ];

        $level1FirstNode = new ChildNode('level1First');
        $level1FirstNode->setValue('level1FirstValue');
        $level2FirstNode = new ChildNode('level2First');
        $level2FirstNode->setValue('level2FirstValue');
        $level1FirstNode->addChild($level2FirstNode);
        $level3FirstNode = new ChildNode('level3First');
        $level3FirstNode->setValue('level2FirstValue');
        $level2FirstNode->addChild($level3FirstNode);
        $expectedAst = new RootNode();
        $expectedAst->addChild($level1FirstNode);
        yield 'copy operator with nested sub copy' => [
            "level1First = level1FirstValue\n" .
            "level1First.level2First = level2FirstValue\n" .
            'level1First.level2First.level3First < level1First.level2First',
            $expectedAst,
            [
                'level1First' => 'level1FirstValue',
                'level1First.' => [
                    'level2First' => 'level2FirstValue',
                    'level2First.' => [
                        'level3First' => 'level2FirstValue',
                    ],
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $sourceNode = new ChildNode('source');
        $sourceNode->setValue('sourceValue');
        $expectedAst->addChild($sourceNode);
        $level1FirstNode = new ChildNode('level1First');
        $expectedAst->addChild($level1FirstNode);
        $level2FirstNode = new ChildNode('level2First');
        $level1FirstNode->addChild($level2FirstNode);
        $level3FirstNode = new ChildNode('level3First');
        $level3FirstNode->setValue('sourceValue');
        $level2FirstNode->addChild($level3FirstNode);
        yield 'copy operator with nested sub copy and blocks with sub identifier' => [
            "source = sourceValue\n" .
            "level1First {\n" .
            "  level2First.level3First < source\n" .
            "}\n",
            $expectedAst,
            [
                'source' => 'sourceValue',
                'level1First.' => [
                    'level2First.' => [
                        'level3First' => 'sourceValue',
                    ],
                ],
            ],
        ];

        $level1FirstNode = new ChildNode('level1First');
        $level1FirstNode->setValue('level1FirstValue');
        $level2FirstNode = new ChildNode('level2First');
        $level2FirstNode->setValue('level2FirstValue');
        $level1FirstNode->addChild($level2FirstNode);
        $level3FirstNode = new ChildNode('level3First');
        $level3FirstNode->setValue('level2FirstValue');
        $level2FirstNode->addChild($level3FirstNode);
        $level1SecondNode = new ChildNode('level1Second');
        $level2SecondNode = new ChildNode('level2Second');
        $level2SecondNode->setValue('level2FirstValue');
        $level1SecondNode->addChild($level2SecondNode);
        $level3SecondNode = new ChildNode('level3First');
        $level3SecondNode->setValue('level2FirstValue');
        $level2SecondNode->addChild($level3SecondNode);
        $expectedAst = new RootNode();
        $expectedAst->addChild($level1FirstNode);
        $expectedAst->addChild($level1SecondNode);
        yield 'copy operator with nested sub copy and blocks' => [
            "level1First = level1FirstValue\n" .
            "level1First {\n" .
            "  level2First = level2FirstValue\n" .
            "  level2First.level3First < level1First.level2First\n" .
            "}\n" .
            "level1Second {\n" .
            "  level2Second < level1First.level2First\n" .
            "}\n",
            $expectedAst,
            [
                'level1First' => 'level1FirstValue',
                'level1First.' => [
                    'level2First' => 'level2FirstValue',
                    'level2First.' => [
                        'level3First' => 'level2FirstValue',
                    ],
                ],
                'level1Second.' => [
                    'level2Second' => 'level2FirstValue',
                    'level2Second.' => [
                        'level3First' => 'level2FirstValue',
                    ],
                ],
            ],
        ];

        $fooNode = new ChildNode('foo');
        $barNode = new ChildNode('bar');
        $barNode->setValue('bar1');
        $fooNode->addChild($barNode);
        $bazNode = new ChildNode('baz');
        $bazNode->setValue('bar1');
        $fooNode->addChild($bazNode);
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'copy operator with relative assignment' => [
            "foo {\n" .
            "  bar = bar1\n" .
            "  baz < .bar\n" .
            '}',
            $expectedAst,
            [
                'foo.' => [
                    'bar' => 'bar1',
                    'baz' => 'bar1',
                ],
            ],
        ];

        $fooNode = new ChildNode('foo');
        $barNode = new ChildNode('bar');
        $fooNode->addChild($barNode);
        $bazNode = new ChildNode('baz');
        $bazNode->setValue('baz1');
        $barNode->addChild($bazNode);
        $foobarNode = new ChildNode('foobar');
        $foobarNode->setValue('baz1');
        $fooNode->addChild($foobarNode);
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'copy operator with relative nested assignment' => [
            "foo {\n" .
            "  bar.baz = baz1\n" .
            "  foobar < .bar.baz\n" .
            '}',
            $expectedAst,
            [
                'foo.' => [
                    'bar.' => [
                        'baz' => 'baz1',
                    ],
                    'foobar' => 'baz1',
                ],
            ],
        ];

        $fooNode = new ChildNode('foo');
        $barNode = new ChildNode('bar');
        $barNode->setValue('aValue');
        $fooNode->addChild($barNode);
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'copy operator with broken relative assignment to top level' => [
            "foo.bar = aValue\n" .
            'foo.baz < .bar',
            $expectedAst,
            [
                'foo.' => [
                    'bar' => 'aValue',
                ],
            ],
        ];

        $fooNode = new ChildNode('foo');
        $barNode = new ChildNode('bar');
        $barNode->setValue('aValue');
        $fooNode->addChild($barNode);
        $bazNode = new ChildNode('baz');
        $bazSubNode = new ChildNode('bar');
        $bazSubNode->setValue('aValue');
        $bazNode->addChild($bazSubNode);
        $fooNode->addChild($bazNode);
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'copy operator with relative assignment to top level ' => [
            "foo.bar = aValue\n" .
            'foo.baz < .foo',
            $expectedAst,
            [
                'foo.' => [
                    'bar' => 'aValue',
                    'baz.' => [
                        'bar' => 'aValue',
                    ] ,
                ],
            ],
        ];

        $fooNode = new ChildNode('foo');
        $barNode = new ChildNode('bar');
        $fooNode->addChild($barNode);
        $foobarNode = new ChildNode('foobar');
        $foobarNode->setValue('aValue');
        $barNode->addChild($foobarNode);
        $bazNode = new ChildNode('baz');
        $bazSubNode = new ChildNode('foobar');
        $bazSubNode->setValue('aValue');
        $bazNode->addChild($bazSubNode);
        $barNode->addChild($bazNode);
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'copy operator with relative assignment to sub level ' => [
             "foo {\n" .
             "  bar.foobar = aValue\n" .
             "  bar.baz < .bar\n" .
             '}',
            $expectedAst,
            [
                'foo.' => [
                    'bar.' => [
                        'foobar' => 'aValue',
                        'baz.' => [
                            'foobar' => 'aValue',
                        ],
                    ],
                ],
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo1');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier empty' => [
            "foo = foo1\n" .
            'foo :=',
            $expectedAst,
            [
                'foo' => 'foo1',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier prependString empty' => [
            "foo = foo\n" .
            'foo := prependString()',
            $expectedAst,
            [
                'foo' => 'foo',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('!abc');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier prependString with value' => [
            "foo = abc\n" .
            'foo := prependString(!)',
            $expectedAst,
            [
                'foo' => '!abc',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('abc');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier appendString empty' => [
            "foo = abc\n" .
            'foo := appendString()',
            $expectedAst,
            [
                'foo' => 'abc',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('abc!');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier appendString with value' => [
            "foo = abc\n" .
            'foo := appendString(!)',
            $expectedAst,
            [
                'foo' => 'abc!',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('adef');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier removeString removes simple string' => [
            "foo = abcdef\n" .
            'foo := removeString(bc)',
            $expectedAst,
            [
                'foo' => 'adef',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('abcdef');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier removeString removes nothing if no match' => [
            "foo = abcdef\n" .
            'foo := removeString(foo)',
            $expectedAst,
            [
                'foo' => 'abcdef',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('Bar');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier removeString removes multiple matches' => [
            "foo = FooBarFoo\n" .
            'foo := removeString(Foo)',
            $expectedAst,
            [
                'foo' => 'Bar',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('a123def');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier replaceString replaces simple match' => [
            "foo = abcdef\n" .
            'foo := replaceString(bc|123)',
            $expectedAst,
            [
                'foo' => 'a123def',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('adef');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier replaceString replaces simple match with nothing' => [
            "foo = abcdef\n" .
            'foo := replaceString(bc)',
            $expectedAst,
            [
                'foo' => 'adef',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('BarBarBar');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier replaceString replaces multiple matches' => [
            "foo = FooBarFoo\n" .
            'foo := replaceString(Foo|Bar)',
            $expectedAst,
            [
                'foo' => 'BarBarBar',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('123,456,789');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier addToList adds at end of existing list' => [
            "foo = 123,456\n" .
            'foo := addToList(789)',
            $expectedAst,
            [
                'foo' => '123,456,789',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('123,456, 789 , 32 , 12 ');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier addToList adds at end of existing list including white-spaces' => [
            "foo = 123,456\n" .
            'foo := addToList( 789 , 32 , 12 )',
            $expectedAst,
            [
                'foo' => '123,456, 789 , 32 , 12 ',
            ],
        ];

        $fooNode = new ChildNode('foo');
        // @todo: This result is probably not what we want (appended comma)
        $fooNode->setValue('123,456,');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier addToList adds nothing' => [
            "foo = 123,456\n" .
            'foo := addToList()',
            $expectedAst,
            [
                'foo' => '123,456,',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier addToList adds to empty list' => [
            'foo := addToList(foo)',
            $expectedAst,
            [
                'foo' => 'foo',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('123,789,abc');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier removeFromList removes value from list' => [
            "foo = 123,456,789,abc\n" .
            'foo := removeFromList(456)',
            $expectedAst,
            [
                'foo' => '123,789,abc',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('456,abc');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier removeFromList removes value at beginning of list' => [
            "foo = 123,456,abc\n" .
            'foo := removeFromList(123)',
            $expectedAst,
            [
                'foo' => '456,abc',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('123,456');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier removeFromList removes value at end of list' => [
            "foo = 123,456,abc\n" .
            'foo := removeFromList(abc)',
            $expectedAst,
            [
                'foo' => '123,456',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo,bar');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier removeFromList removes multiple values from list' => [
            "foo = foo,123,bar,123\n" .
            'foo := removeFromList(123)',
            $expectedAst,
            [
                'foo' => 'foo,bar',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo,bar');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier removeFromList removes empty values' => [
            "foo = foo,,bar\n" .
            'foo := removeFromList()',
            $expectedAst,
            [
                'foo' => 'foo,bar',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('123,456,abc');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier uniqueList removes duplicates' => [
            "foo = 123,456,abc,456,456\n" .
            'foo := uniqueList()',
            $expectedAst,
            [
                'foo' => '123,456,abc',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('123,,456,abc');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier uniqueList removes duplicate empty list values' => [
            "foo = 123,,456,,abc\n" .
            'foo := uniqueList()',
            $expectedAst,
            [
                'foo' => '123,,456,abc',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('456,abc,456,123');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier reverseList returns list reversed' => [
            "foo = 123,456,abc,456\n" .
            'foo := reverseList()',
            $expectedAst,
            [
                'foo' => '456,abc,456,123',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('456,,abc,456,,123,');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier reverseList keeps empty values' => [
            "foo = ,123,,456,abc,,456\n" .
            'foo := reverseList()',
            $expectedAst,
            [
                'foo' => '456,,abc,456,,123,',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('123');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier reverseList does not change single element' => [
            "foo = 123\n" .
            'foo := reverseList()',
            $expectedAst,
            [
                'foo' => '123',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('0,10,20,100,abc');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier sortList sorts a list' => [
            "foo = 10,100,0,20,abc\n" .
            'foo := sortList()',
            $expectedAst,
            [
                'foo' => '0,10,20,100,abc',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('-20,0,10,100');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier sortList sorts a list numeric' => [
            "foo = 10,0,100,-20\n" .
            'foo := sortList(numeric)',
            $expectedAst,
            [
                'foo' => '-20,0,10,100',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('abc,100,20,10,0,-20');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier sortList sorts a list descending' => [
            "foo = 10,100,0,20,abc,-20\n" .
            'foo := sortList(descending)',
            $expectedAst,
            [
                'foo' => 'abc,100,20,10,0,-20',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('100,20,10,0,-20');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier sortList sorts a list numeric descending' => [
            "foo = 10,100,0,20,-20\n" .
            'foo := sortList(descending,numeric)',
            $expectedAst,
            [
                'foo' => '100,20,10,0,-20',
            ],
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('100,20,10');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'value modifier sortList ignores invalid modifier arguments' => [
            "foo = 10,100,20\n" .
            'foo := sortList(foo,descending,bar)',
            $expectedAst,
            [
                'foo' => '100,20,10',
            ],
        ];
    }

    /**
     * @test
     * @dataProvider buildDataProvider
     */
    public function build(string $source, RootNode $expectedAst): void
    {
        $noopEventDispatcher = new NoopEventDispatcher();
        $tokens = (new LosslessTokenizer())->tokenize($source);
        $ast = (new AstBuilder($noopEventDispatcher))->build($tokens, new RootNode());
        self::assertEquals($expectedAst, $ast);
    }

    /**
     * @test
     * @dataProvider buildDataProvider
     */
    public function buildCompatArray(string $source, RootNode $_, array $expectedArray): void
    {
        $noopEventDispatcher = new NoopEventDispatcher();
        $tokens = (new LosslessTokenizer())->tokenize($source);
        $ast = (new AstBuilder($noopEventDispatcher))->build($tokens, new RootNode());
        self::assertEquals($expectedArray, $ast->toArray());
    }

    public function buildReferenceDataProvider(): \Generator
    {
        $expectedAst = new RootNode();
        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo1');
        $barNode = new ReferenceChildNode('bar');
        $barNode->setReferenceSourceStream(
            (new IdentifierTokenStream())
                ->append((new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 1, 7)))
        );
        $expectedAst->addChild($fooNode);
        $expectedAst->addChild($barNode);
        yield 'reference operator creates reference node' => [
            "foo = foo1\n" .
            'bar =< foo',
            $expectedAst,
            [
                'foo'  => 'foo1',
                'bar' => '< foo',
            ],
        ];

        $expectedAst = new RootNode();
        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo1');
        $expectedAst->addChild($fooNode);
        $barNode = new ReferenceChildNode('bar');
        $barNode->setReferenceSourceStream(
            (new IdentifierTokenStream())
                ->append((new IdentifierToken(TokenType::T_IDENTIFIER, 'baz', 1, 7)))
        );
        $expectedAst->addChild($barNode);
        yield 'reference operator when source does not exist still adds node' => [
            "foo = foo1\n" .
            "bar =< baz\n",
            $expectedAst,
            [
                'foo' => 'foo1',
                'bar' => '< baz',
            ],
        ];

        $expectedAst = new RootNode();
        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo1');
        $expectedAst->addChild($fooNode);
        $bar1Node = new ReferenceChildNode('bar');
        $fooNode->addChild($bar1Node);
        $bar1Node->setReferenceSourceStream(
            (new IdentifierTokenStream())
                ->append((new IdentifierToken(TokenType::T_IDENTIFIER, 'foo', 1, 11)))
        );
        yield 'reference operator creates reference node without sub nodes for sub node' => [
            "foo = foo1\n" .
            "foo.bar =< foo\n",
            $expectedAst,
            [
                'foo' => 'foo1',
                'foo.' => [
                    'bar' => '< foo',
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $sourceNode = new ChildNode('source');
        $sourceNode->setValue('sourceValue');
        $expectedAst->addChild($sourceNode);
        $level1FirstNode = new ChildNode('level1First');
        $expectedAst->addChild($level1FirstNode);
        $level2FirstNode = new ChildNode('level2First');
        $level1FirstNode->addChild($level2FirstNode);
        $level3FirstNode = new ReferenceChildNode('level3First');
        $level2FirstNode->addChild($level3FirstNode);
        $level3FirstNode->setReferenceSourceStream(
            (new IdentifierTokenStream())
                ->append((new IdentifierToken(TokenType::T_IDENTIFIER, 'source', 2, 29)))
        );
        yield 'reference operator with nested sub reference is absolute' => [
            "source = sourceValue\n" .
            "level1First {\n" .
            "  level2First.level3First =< source\n" .
            "}\n",
            $expectedAst,
            [
                'source' => 'sourceValue',
                'level1First.' => [
                    'level2First.' => [
                        'level3First' => '< source',
                    ],
                ],
            ],
        ];

        $expectedAst = new RootNode();
        $sourceNode = new ChildNode('source');
        $expectedAst->addChild($sourceNode);
        $sourceNode->setValue('sourceValue');
        $sourceBarNode = new ChildNode('bar');
        $sourceNode->addChild($sourceBarNode);
        $sourceBarNode->setValue('sourceBarValue');
        $level1FirstNode = new ChildNode('level1First');
        $expectedAst->addChild($level1FirstNode);
        $level2FirstNode = new ChildNode('level2First');
        $level1FirstNode->addChild($level2FirstNode);
        $level3FirstNode = new ReferenceChildNode('level3First');
        $level2FirstNode->addChild($level3FirstNode);
        $level3FirstNode->setValue('newSource1Value');
        $level3SecondNode = new ChildNode('level3Second');
        $level2FirstNode->addChild($level3SecondNode);
        $level3SecondNode->setValue('newSource2Value');
        $level3FirstNode->setReferenceSourceStream(
            (new IdentifierTokenStream())
                ->append((new IdentifierToken(TokenType::T_IDENTIFIER, 'source', 3, 29)))
        );
        yield 'reference operator with nested sub reference sets override value and sub tree' => [
            "source = sourceValue\n" .
            "source.bar = sourceBarValue\n" .
            "level1First {\n" .
            "  level2First.level3First =< source\n" .
            "}\n" .
            "level1First.level2First.level3First = newSource1Value\n" .
            "level1First.level2First.level3Second = newSource2Value\n",
            $expectedAst,
            [
                'source' => 'sourceValue',
                'source.' => [
                    'bar' => 'sourceBarValue',
                ],
                'level1First.' => [
                    'level2First.' => [
                        'level3First' => '< source',
                        'level3Second' => 'newSource2Value',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider buildReferenceDataProvider
     */
    public function buildReference(string $source, RootNode $expectedAst): void
    {
        $noopEventDispatcher = new NoopEventDispatcher();
        $tokens = (new LosslessTokenizer())->tokenize($source);
        $ast = (new AstBuilder($noopEventDispatcher))->build($tokens, new RootNode());
        self::assertEquals($expectedAst, $ast);
    }

    /**
     * @test
     * @dataProvider buildReferenceDataProvider
     */
    public function buildReferenceArray(string $source, RootNode $_, array $expectedArray): void
    {
        $noopEventDispatcher = new NoopEventDispatcher();
        $tokens = (new LosslessTokenizer())->tokenize($source);
        $ast = (new AstBuilder($noopEventDispatcher))->build($tokens, new RootNode());
        self::assertEquals($expectedArray, $ast->toArray());
    }

    public function buildConstantDataProvider(): \Generator
    {
        $expectedAst = new RootNode();
        $objectNode = new ChildNode('foo');
        $objectNode->setValue('{$bar}');
        $objectNode->setOriginalValueTokenStream(
            (new ConstantAwareTokenStream())
                ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
        );
        $expectedAst->addChild($objectNode);
        yield 'assignment with non existing constant is kept as string literal' => [
            'foo = {$bar}',
            [],
            $expectedAst,
            [
                'foo' => '{$bar}',
            ],
        ];

        $expectedAst = new RootNode();
        $objectNode = new ChildNode('foo');
        $objectNode->setValue('barValue');
        $objectNode->setOriginalValueTokenStream(
            (new ConstantAwareTokenStream())
                ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
        );
        $expectedAst->addChild($objectNode);
        yield 'assignment with existing constant' => [
            'foo = {$bar}',
            ['bar' => 'barValue'],
            $expectedAst,
            [
                'foo' => 'barValue',
            ],
        ];

        $expectedAst = new RootNode();
        $objectNode = new ChildNode('foo');
        $objectNode->setValue('');
        $objectNode->setOriginalValueTokenStream(
            (new ConstantAwareTokenStream())
                ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
        );
        $expectedAst->addChild($objectNode);
        yield 'assignment with existing constant set to empty string' => [
            'foo = {$bar}',
            ['bar' => ''],
            $expectedAst,
            [
                'foo' => '',
            ],
        ];

        $expectedAst = new RootNode();
        $objectNode = new ChildNode('foo');
        $objectNode->setValue('0');
        $objectNode->setOriginalValueTokenStream(
            (new ConstantAwareTokenStream())
                ->append(new Token(TokenType::T_CONSTANT, '{$bar}', 0, 6))
        );
        $expectedAst->addChild($objectNode);
        yield 'assignment with existing constant set to zero' => [
            'foo = {$bar}',
            ['bar' => '0'],
            $expectedAst,
            [
                'foo' => '0',
            ],
        ];

        $expectedAst = new RootNode();
        $objectNode = new ChildNode('foo');
        $objectNode->setValue('bar1Valuebazbar2Value');
        $objectNode->setOriginalValueTokenStream(
            (new ConstantAwareTokenStream())
                ->append(new Token(TokenType::T_CONSTANT, '{$bar1}', 0, 6))
                ->append(new Token(TokenType::T_VALUE, 'baz', 0, 13))
                ->append(new Token(TokenType::T_CONSTANT, '{$bar2}', 0, 16))
        );
        $expectedAst->addChild($objectNode);
        yield 'assignment with multiple existing constants' => [
            'foo = {$bar1}baz{$bar2}',
            [
                'bar1' => 'bar1Value',
                'bar2' => 'bar2Value',
            ],
            $expectedAst,
            [
                'foo' => 'bar1Valuebazbar2Value',
            ],
        ];

        $expectedAst = new RootNode();
        $objectNode = new ChildNode('foo');
        $objectNode->setValue("  bar1Valuebaz\n  bar2Value");
        $objectNode->setOriginalValueTokenStream(
            (new ConstantAwareTokenStream())
                ->append(new Token(TokenType::T_VALUE, '  ', 1, 0))
                ->append(new Token(TokenType::T_CONSTANT, '{$bar1}', 1, 2))
                ->append(new Token(TokenType::T_VALUE, 'baz', 1, 9))
                ->append(new Token(TokenType::T_NEWLINE, "\n", 1, 12))
                ->append(new Token(TokenType::T_VALUE, '  ', 2, 0))
                ->append(new Token(TokenType::T_CONSTANT, '{$bar2}', 2, 2))
        );
        $expectedAst->addChild($objectNode);
        yield 'assignment multiline with multiple existing constants' => [
            "foo (\n" .
            "  {\$bar1}baz\n" .
            "  {\$bar2}\n" .
            ')',
            [
                'bar1' => 'bar1Value',
                'bar2' => 'bar2Value',
            ],
            $expectedAst,
            [
                'foo' => "  bar1Valuebaz\n  bar2Value",
            ],
        ];
    }

    /**
     * @test
     * @dataProvider buildConstantDataProvider
     */
    public function buildConstant(string $source, array $constants, RootNode $expectedAst): void
    {
        $noopEventDispatcher = new NoopEventDispatcher();
        $tokens = (new LosslessTokenizer())->tokenize($source);
        $ast = (new AstBuilder($noopEventDispatcher))->build($tokens, new RootNode(), $constants);
        self::assertEquals($expectedAst, $ast);
    }

    /**
     * @test
     * @dataProvider buildConstantDataProvider
     */
    public function buildConstantCompatArray(string $source, array $constants, RootNode $_, array $expectedArray): void
    {
        $noopEventDispatcher = new NoopEventDispatcher();
        $tokens = (new LosslessTokenizer())->tokenize($source);
        $ast = (new AstBuilder($noopEventDispatcher))->build($tokens, new RootNode(), $constants);
        self::assertEquals($expectedArray, $ast->toArray());
    }

    /**
     * @test
     */
    public function buildExtendsGivenAst(): void
    {
        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo1');
        $inputAst = new RootNode();
        $inputAst->addChild($fooNode);

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('foo1');
        $barNode = new ChildNode('bar');
        $barNode->setValue('bar1');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        $expectedAst->addChild($barNode);

        $expectedArray = [
            'foo' => 'foo1',
            'bar' => 'bar1',
        ];

        $noopEventDispatcher = new NoopEventDispatcher();
        $tokens = (new LosslessTokenizer())->tokenize('bar = bar1');
        $resultAst = (new AstBuilder($noopEventDispatcher))->build($tokens, $inputAst, []);
        self::assertEquals($expectedAst, $resultAst);
        self::assertEquals($expectedArray, $resultAst->toArray());
    }

    public function functionSortListThrowsSortingNonNumericListNumericDataProvider(): \Generator
    {
        yield 'non-numeric list numeric' => [
            "foo = 10,0,100,-20,abc\n" .
            'foo := sortList(numeric)',
        ];

        yield 'non-numeric list numeric descending' => [
            "foo = 10,0,100,-20,abc\n" .
            'foo := sortList(descending,numeric)',
        ];
    }

    /**
     * @test
     * @dataProvider functionSortListThrowsSortingNonNumericListNumericDataProvider
     */
    public function functionSortListThrowsSortingNonNumericListNumeric(string $source): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1650893781);
        $noopEventDispatcher = new NoopEventDispatcher();
        $tokens = (new LosslessTokenizer())->tokenize($source);
        (new AstBuilder($noopEventDispatcher))->build($tokens, new RootNode());
    }

    public function functionGetEnvDataProvider(): \Generator
    {
        $fooNode = new ChildNode('foo');
        $fooNode->setValue('bar');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'environment variable not set' => [
            null, // env var name
            null, // env var value
            "foo = bar\n" .
            'foo := getEnv(FOO)',
            $expectedAst,
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'empty environment variable' => [
            'FOO',
            '',
            "foo = bar\n" .
            'foo := getEnv(FOO)',
            $expectedAst,
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('baz');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'empty current value' => [
            'FOO',
            'baz',
            'foo := getEnv(FOO)',
            $expectedAst,
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('baz');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'environment variable and current value set' => [
            'FOO',
            'baz',
            "foo = bar\n" .
            'foo := getEnv(FOO)',
            $expectedAst,
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'neither environment variable nor current value set' => [
            null,
            null,
            'foo := getEnv(FOO)',
            $expectedAst,
        ];

        $fooNode = new ChildNode('foo');
        $fooNode->setValue('');
        $expectedAst = new RootNode();
        $expectedAst->addChild($fooNode);
        yield 'empty environment variable name' => [
            'FOO',
            'baz',
            'foo := getEnv()',
            $expectedAst,
        ];
    }

    /**
     * @test
     * @dataProvider functionGetEnvDataProvider
     */
    public function functionGetEnv(?string $envVarName, ?string $envVarValue, $source, RootNode $expectedAst): void
    {
        if ($envVarName) {
            putenv($envVarName . '=' . $envVarValue);
        }
        $noopEventDispatcher = new NoopEventDispatcher();
        $tokens = (new LosslessTokenizer())->tokenize($source);
        $ast = (new AstBuilder($noopEventDispatcher))->build($tokens, new RootNode());
        self::assertEquals($expectedAst, $ast);
        if ($envVarName) {
            putenv($envVarName);
        }
    }

    public function flattenDataProvider(): \Generator
    {
        $noopEventDispatcher = new NoopEventDispatcher();

        yield 'empty' => [
            new RootNode(),
            [],
        ];

        $typoscript =
            "first = firstValue\n" .
            "second = secondValue\n";
        $expected = [
            'first' => 'firstValue',
            'second' => 'secondValue',
        ];
        yield 'one level' => [
            (new AstBuilder($noopEventDispatcher))->build((new LosslessTokenizer())->tokenize($typoscript), new RootNode()),
            $expected,
        ];

        $typoscript =
            "first = \n";
        $expected = [
            'first' => '',
        ];
        yield 'empty string as value is kept' => [
            (new AstBuilder($noopEventDispatcher))->build((new LosslessTokenizer())->tokenize($typoscript), new RootNode()),
            $expected,
        ];

        $typoscript =
            "first = 0\n";
        $expected = [
            'first' => '0',
        ];
        yield 'zero as value is kept' => [
            (new AstBuilder($noopEventDispatcher))->build((new LosslessTokenizer())->tokenize($typoscript), new RootNode()),
            $expected,
        ];

        $typoscript =
            "first.firstSub = firstSubValue\n" .
            "second.secondSub = secondSubValue\n";
        $expected = [
            'first.firstSub' => 'firstSubValue',
            'second.secondSub' => 'secondSubValue',
        ];
        yield 'two levels' => [
            (new AstBuilder($noopEventDispatcher))->build((new LosslessTokenizer())->tokenize($typoscript), new RootNode()),
            $expected,
        ];

        $typoscript =
            "first = firstValue\n" .
            "first.firstSub = firstSubValue\n" .
            "second = secondValue\n" .
            "second.secondSub = secondSubValue\n";
        $expected = [
            'first' => 'firstValue',
            'first.firstSub' => 'firstSubValue',
            'second' => 'secondValue',
            'second.secondSub' => 'secondSubValue',
        ];
        yield 'two levels with values on first level' => [
            (new AstBuilder($noopEventDispatcher))->build((new LosslessTokenizer())->tokenize($typoscript), new RootNode()),
            $expected,
        ];

        $typoscript =
            "first.firstSub.firstSubSub = firstSubSubValue\n" .
            "second = secondValue\n" .
            "second.secondSub = secondSubValue\n" .
            "second.secondSub.secondSubSub = secondSubSubValue\n";
        $expected = [
            'first.firstSub.firstSubSub' => 'firstSubSubValue',
            'second' => 'secondValue',
            'second.secondSub' => 'secondSubValue',
            'second.secondSub.secondSubSub' => 'secondSubSubValue',
        ];
        yield 'three levels, partially with values' => [
            (new AstBuilder($noopEventDispatcher))->build((new LosslessTokenizer())->tokenize($typoscript), new RootNode()),
            $expected,
        ];

        $typoscript =
            'first.firstSub\.firstSubSub = firstSubSubValue';
        $expected = [
            'first.firstSub\.firstSubSub' => 'firstSubSubValue',
        ];
        yield 'two levels with quoted dote' => [
            (new AstBuilder($noopEventDispatcher))->build((new LosslessTokenizer())->tokenize($typoscript), new RootNode()),
            $expected,
        ];
    }

    /**
     * @test
     * @dataProvider flattenDataProvider
     */
    public function flatten(RootNode $ast, array $expected)
    {
        self::assertSame($expected, $ast->flatten());
    }
}
