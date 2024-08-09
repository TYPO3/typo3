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

namespace TYPO3\CMS\Core\Tests\Unit\Versioning;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class VersionStateTest extends UnitTestCase
{
    #[Test]
    public function canBeCreated(): void
    {
        $versionState = VersionState::MOVE_POINTER;
        self::assertSame(4, $versionState->value);
    }

    public static function canBeCastedDataProvider(): \Generator
    {
        yield [4, 4];
        yield ['4', 4];
        yield [VersionState::MOVE_POINTER, 4];
        yield ['zero-casted', 0];
        yield [12345, null];
    }

    #[DataProvider('canBeCastedDataProvider')]
    #[Test]
    #[IgnoreDeprecations]
    public function canBeCasted(mixed $value, ?int $expectation): void
    {
        self::assertSame($expectation, VersionState::cast($value)?->value);
    }

    public static function canBeComparedDataProvider(): \Generator
    {
        yield [0, false];
        yield ['0', false];
        yield [VersionState::DEFAULT_STATE, false];

        yield [4, true];
        yield ['4', true];
        yield [VersionState::MOVE_POINTER, true];
    }

    #[DataProvider('canBeComparedDataProvider')]
    #[Test]
    #[IgnoreDeprecations]
    public function canBeCompared(mixed $value, bool $expectation): void
    {
        self::assertSame($expectation, VersionState::MOVE_POINTER->equals($value));
    }
}
