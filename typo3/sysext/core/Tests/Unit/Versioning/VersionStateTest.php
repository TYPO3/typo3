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
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class VersionStateTest extends UnitTestCase
{
    public static function canIndicatesPlaceholderDataProvider(): \Generator
    {
        yield [VersionState::DEFAULT_STATE, false];
        yield [VersionState::NEW_PLACEHOLDER, false];
        yield [VersionState::DELETE_PLACEHOLDER, true];
        yield [VersionState::MOVE_POINTER, true];
    }

    #[DataProvider('canIndicatesPlaceholderDataProvider')]
    #[Test]
    public function canIndicatesPlaceholder(int $state, bool $expectation): void
    {
        self::assertSame($expectation, VersionState::cast($state)->indicatesPlaceholder());
    }
}
