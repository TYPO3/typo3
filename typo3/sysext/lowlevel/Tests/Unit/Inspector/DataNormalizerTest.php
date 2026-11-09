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

namespace TYPO3\CMS\Lowlevel\Tests\Unit\Inspector;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\DiffGranularity;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\CMS\Lowlevel\Inspector\DataNormalizer;
use TYPO3\CMS\Lowlevel\Inspector\Marker;
use TYPO3\CMS\Lowlevel\Tests\Unit\Inspector\Fixtures\ChildWithPrivateProperty;
use TYPO3\CMS\Lowlevel\Tests\Unit\Inspector\Fixtures\InvokableObject;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class DataNormalizerTest extends UnitTestCase
{
    #[Test]
    public function keepsScalarsAndArrays(): void
    {
        $data = ['a' => 'text', 'b' => 1, 'c' => 1.5, 'd' => true, 'e' => null, 'f' => [], 'g' => ['x']];
        self::assertSame($data, new DataNormalizer()->normalize($data));
    }

    #[Test]
    public function convertsEnumsToValueOrName(): void
    {
        $result = new DataNormalizer()->normalize([VersionState::DELETE_PLACEHOLDER, DiffGranularity::WORD]);
        self::assertSame([VersionState::DELETE_PLACEHOLDER->value, 'WORD'], $result);
    }

    #[Test]
    public function keepsStringsThatAreFunctionNames(): void
    {
        self::assertSame(['eval' => 'trim'], new DataNormalizer()->normalize(['eval' => 'trim']));
    }

    #[Test]
    public function convertsCallablesToMarkers(): void
    {
        $result = new DataNormalizer()->normalize([
            'closure' => static fn(): bool => true,
            'array' => [\DateTimeImmutable::class, 'createFromFormat'],
            'invokable' => new InvokableObject(),
        ]);
        self::assertInstanceOf(Marker::class, $result['closure']);
        self::assertStringStartsWith('{closure:' . self::class . '::convertsCallablesToMarkers()', $result['closure']->text);
        self::assertEquals(new Marker('DateTimeImmutable::createFromFormat'), $result['array']);
        self::assertEquals(new Marker(InvokableObject::class . '::__invoke'), $result['invokable']);
    }

    #[Test]
    public function keepsArraysOfNonExistingCallables(): void
    {
        $data = ['hook' => ['Vendor\\DoesNotExist', 'render']];
        self::assertSame($data, new DataNormalizer()->normalize($data));
    }

    #[Test]
    public function convertsObjectsToPropertiesWithoutNullBytePrefixes(): void
    {
        $result = new DataNormalizer()->normalize(new ChildWithPrivateProperty());
        self::assertSame(
            [
                'public' => 'public',
                'protected' => 'protected',
                ChildWithPrivateProperty::PARENT_CLASS . '::private' => 'parent',
                ChildWithPrivateProperty::class . '::private' => 'child',
            ],
            $result
        );
    }

    #[Test]
    public function convertsTraversablesToArrays(): void
    {
        self::assertSame(['a' => 1, 'b' => 2], new DataNormalizer()->normalize(new \ArrayIterator(['a' => 1, 'b' => 2])));
    }

    #[Test]
    public function marksCyclesButRepeatsSharedObjects(): void
    {
        $shared = new \stdClass();
        $shared->name = 'shared';
        $node = new \stdClass();
        $node->self = $node;
        $node->first = $shared;
        $node->second = $shared;
        $result = new DataNormalizer()->normalize($node);
        self::assertEquals(new Marker('*RECURSION*'), $result['self']);
        self::assertSame(['name' => 'shared'], $result['first']);
        self::assertSame(['name' => 'shared'], $result['second']);
    }

    #[Test]
    public function marksResourcesAndNonFiniteNumbers(): void
    {
        $resource = fopen('php://memory', 'r');
        $result = new DataNormalizer()->normalize(['resource' => $resource, 'inf' => INF, 'negative' => -INF, 'nan' => NAN]);
        fclose($resource);
        self::assertEquals(new Marker('Resource id #' . (int)$resource), $result['resource']);
        self::assertEquals(new Marker('INF'), $result['inf']);
        self::assertEquals(new Marker('-INF'), $result['negative']);
        self::assertEquals(new Marker('NAN'), $result['nan']);
    }

    #[Test]
    public function replacesInvalidUtf8(): void
    {
        self::assertSame("ab\u{FFFD}c", new DataNormalizer()->normalize("ab\xFFc"));
    }
}
