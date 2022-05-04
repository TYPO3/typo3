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

namespace TYPO3\CMS\Core\Tests\Unit\Utility\String;

use TYPO3\CMS\Core\Utility\String\StringFragment;
use TYPO3\CMS\Core\Utility\String\StringFragmentPattern;
use TYPO3\CMS\Core\Utility\String\StringFragmentSplitter;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class StringFragmentSplitterTest extends UnitTestCase
{
    public static function stringIsSplitDataProvider(): \Generator
    {
        $expressionPattern = new StringFragmentPattern(StringFragmentSplitter::TYPE_EXPRESSION, '\\$[^$]+\\$');
        $otherPattern = new StringFragmentPattern(StringFragmentSplitter::TYPE_EXPRESSION, '![^!]+!');

        yield 'Hello World! (unmatched with `raw` only)' => [
            'Hello World!',
            [$expressionPattern, $otherPattern],
            0,
            [
                StringFragment::raw('Hello World!'),
            ],
        ];
        yield 'Hello World! (unmatched as null)' => [
            'Hello World!',
            [$expressionPattern, $otherPattern],
            StringFragmentSplitter::FLAG_UNMATCHED_AS_NULL,
            null,
        ];
        yield 'Hello$expr$World!' => [
            'Hello$expr$World!',
            [$expressionPattern, $otherPattern],
            0,
            [
                StringFragment::raw('Hello'),
                StringFragment::expression('$expr$'),
                StringFragment::raw('World!'),
            ],
        ];
        yield 'Hello $expr$ World!' => [
            'Hello $expr$ World!',
            [$expressionPattern, $otherPattern],
            0,
            [
                StringFragment::raw('Hello '),
                StringFragment::expression('$expr$'),
                StringFragment::raw(' World!'),
            ],
        ];
        yield '$expr$ combined with !other!' => [
            '$expr$ combined with !other!',
            [$expressionPattern, $otherPattern],
            0,
            [
                StringFragment::expression('$expr$'),
                StringFragment::raw(' combined with '),
                StringFragment::expression('!other!'),
            ],
        ];
    }

    /**
     * @param list<StringFragmentPattern> $patterns
     * @param list<StringFragment> $expectations
     *
     * @test
     * @dataProvider stringIsSplitDataProvider
     */
    public function stringIsSplit(string $value, array $patterns, int $flags, ?array $expectations): void
    {
        $splitter = new StringFragmentSplitter(...$patterns);
        $collection = $splitter->split($value, $flags);

        if ($expectations === null) {
            self::assertNull($collection);
        } else {
            self::assertEquals($expectations, $collection->getFragments());
        }
    }
}
