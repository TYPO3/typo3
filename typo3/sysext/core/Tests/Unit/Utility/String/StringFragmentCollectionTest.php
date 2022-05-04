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
use TYPO3\CMS\Core\Utility\String\StringFragmentCollection;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class StringFragmentCollectionTest extends UnitTestCase
{
    /**
     * @test
     */
    public function collectionReflectsFragments(): void
    {
        $a = StringFragment::raw('aa');
        $b = StringFragment::raw('bb');
        $collection = new StringFragmentCollection($a, $b);
        self::assertSame(2, count($collection));
        self::assertSame([$a, $b], $collection->getFragments());
        self::assertSame(4, $collection->getLength());
        self::assertSame('aabb', (string)$collection);
    }

    public static function differencesAreResolvedDataProvider(): \Generator
    {
        $a = StringFragment::raw('a');
        $b = StringFragment::raw('b');
        $c = StringFragment::raw('c');

        yield [
            [$a, $b, $c],
            [],
            [$a, $b, $c],
        ];
        yield [
            [$a, $b, $c],
            [$a, $b, $c],
            [],
        ];
        yield [
            [$a, $b, $c],
            [$a],
            [$b, $c],
        ];
    }

    /**
     * @param list<StringFragment> $first
     * @param list<StringFragment> $second
     * @param list<StringFragment> $expectations
     *
     * @test
     * @dataProvider differencesAreResolvedDataProvider
     */
    public function differencesAreResolved(array $first, array $second, array $expectations): void
    {
        $firstCollection = new StringFragmentCollection(...$first);
        $secondCollection = new StringFragmentCollection(...$second);
        $collection = $firstCollection->diff($secondCollection);
        self::assertEquals($expectations, $collection->getFragments());
    }

    public static function intersectionsAreResolvedDataProvider(): \Generator
    {
        $a = StringFragment::raw('a');
        $b = StringFragment::raw('b');
        $c = StringFragment::raw('c');

        yield [
            [$a, $b, $c],
            [],
            [],
        ];
        yield [
            [$a, $b, $c],
            [$a, $b, $c],
            [$a, $b, $c],
        ];
        yield [
            [$a, $b, $c],
            [$a, $c],
            [$a, $c],
        ];
    }

    /**
     * @param list<StringFragment> $first
     * @param list<StringFragment> $second
     * @param list<StringFragment> $expectations
     *
     * @test
     * @dataProvider intersectionsAreResolvedDataProvider
     */
    public function intersectionsAreResolved(array $first, array $second, array $expectations): void
    {
        $firstCollection = new StringFragmentCollection(...$first);
        $secondCollection = new StringFragmentCollection(...$second);
        $collection = $firstCollection->intersect($secondCollection);
        self::assertEquals($expectations, $collection->getFragments());
    }
}
