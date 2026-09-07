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

namespace TYPO3\CMS\Core\Tests\Unit\Context;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class VisibilityAspectTest extends UnitTestCase
{
    #[Test]
    public function getterReturnsProperDefaultValues(): void
    {
        $subject = new VisibilityAspect();
        self::assertFalse($subject->includeHiddenPages());
        self::assertFalse($subject->includeHiddenContent());
        self::assertFalse($subject->includeDeletedRecords());
        self::assertFalse($subject->includeScheduledRecords());
    }

    #[Test]
    public function getterReturnsProperValues(): void
    {
        $subject = new VisibilityAspect(true, true, true, true);
        self::assertTrue($subject->includeHiddenPages());
        self::assertTrue($subject->includeHiddenContent());
        self::assertTrue($subject->includeDeletedRecords());
        self::assertTrue($subject->includeScheduledRecords());
    }

    #[Test]
    public function getReturnsProperValues(): void
    {
        $subject = new VisibilityAspect(true, true, true, true);
        self::assertTrue($subject->get('includeHiddenPages'));
        self::assertTrue($subject->get('includeHiddenContent'));
        self::assertTrue($subject->get('includeDeletedRecords'));
        self::assertTrue($subject->get('includeScheduledRecords'));
    }

    #[Test]
    public function getThrowsExceptionOnInvalidArgument(): void
    {
        $this->expectException(AspectPropertyNotFoundException::class);
        $this->expectExceptionCode(1527780439);
        $subject = new VisibilityAspect();
        $subject->get('football');
    }

    public static function withMethodDataProvider(): array
    {
        return [
            'enable hidden pages' => ['withIncludeHiddenPages', true, [false, true, false, true], [true, true, false, true]],
            'disable hidden pages' => ['withIncludeHiddenPages', false, [true, false, true, false], [false, false, true, false]],
            'enable hidden content' => ['withIncludeHiddenContent', true, [true, false, true, false], [true, true, true, false]],
            'disable hidden content' => ['withIncludeHiddenContent', false, [false, true, false, true], [false, false, false, true]],
            'enable deleted records' => ['withIncludeDeletedRecords', true, [false, true, false, true], [false, true, true, true]],
            'disable deleted records' => ['withIncludeDeletedRecords', false, [true, false, true, false], [true, false, false, false]],
            'enable scheduled records' => ['withIncludeScheduledRecords', true, [true, false, true, false], [true, false, true, true]],
            'disable scheduled records' => ['withIncludeScheduledRecords', false, [false, true, false, true], [false, true, false, false]],
        ];
    }

    #[Test]
    #[DataProvider('withMethodDataProvider')]
    public function withMethodReturnsNewInstanceWithOnlyRequestedFlagChanged(string $method, bool $value, array $initialValues, array $expectedValues): void
    {
        $subject = new VisibilityAspect(...$initialValues);

        $result = $subject->$method($value);

        self::assertNotSame($subject, $result);
        self::assertEquals(new VisibilityAspect(...$expectedValues), $result);
        self::assertEquals(new VisibilityAspect(...$initialValues), $subject);
    }
}
