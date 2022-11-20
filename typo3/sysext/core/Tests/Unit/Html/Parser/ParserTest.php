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

namespace TYPO3\CMS\Core\Tests\Unit\Html\Parser;

use TYPO3\CMS\Core\Html\SimpleNode;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ParserTest extends UnitTestCase
{
    private const TYPES = [
        SimpleNode::TYPE_ELEMENT => 'ELEMENT',
        SimpleNode::TYPE_TEXT => 'TEXT',
        SimpleNode::TYPE_CDATA => 'CDATA',
        SimpleNode::TYPE_COMMENT => 'COMMENT',
    ];

    /**
     * @return string[]
     */
    public function nodesAreResolvedDataProvider(): array
    {
        return [
            [
                'text',
                ['[TEXT]: text'],
            ],
            [
                '<element>',
                ['[ELEMENT]: <element>'],
            ],
            [
                '</element>',
                ['[ELEMENT]: </element>'],
            ],
            [
                '<!-- comment -->',
                ['[COMMENT]: <!-- comment -->'],
            ],
            [
                '<![CDATA[ cdata ]]>',
                ['[CDATA]: <![CDATA[ cdata ]]>'],
            ],
            [
                '<![CDATA[ <!-- comment --> ]]>',
                ['[CDATA]: <![CDATA[ <!-- comment --> ]]>'],
            ],
            [
                '<!-- <![CDATA[ cdata ]]> -->',
                ['[COMMENT]: <!-- <![CDATA[ cdata ]]> -->'],
            ],
            [
                '<text',
                [], // invalid element start, therefore ignored
            ],
            [
                '< text',
                ['[TEXT]: < text'],
            ],
            [
                'x < y',
                ['[TEXT]: x < y'],
            ],
            [
                'text>',
                ['[TEXT]: text>'],
            ],
            [
                'text >',
                ['[TEXT]: text >'],
            ],
            [
                'x > y',
                ['[TEXT]: x > y'],
            ],
            [
                'x < y > z',
                ['[TEXT]: x < y > z'],
            ],
            [
                'x <= y >= z',
                ['[TEXT]: x <= y >= z'],
            ],
            [
                'x =<y>= z',
                ['[TEXT]: x =', '[ELEMENT]: <y>', '[TEXT]: = z'],
            ],
        ];
    }

    /**
     * @param string[] $expectation
     * @test
     * @dataProvider nodesAreResolvedDataProvider
     */
    public function nodesAreResolved(string $html, array $expectation): void
    {
        $parser = \TYPO3\CMS\Core\Html\SimpleParser::fromString($html);
        $nodes = array_map(
            static function (SimpleNode $node) {
                return sprintf('[%s]: %s', self::TYPES[$node->getType()], $node);
            },
            $parser->getNodes()
        );
        self::assertSame($expectation, $nodes);
    }
}
