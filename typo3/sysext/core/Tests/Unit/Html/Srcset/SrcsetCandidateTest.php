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

namespace TYPO3\CMS\Core\Tests\Unit\Html\Srcset;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Html\Srcset\DensitySrcsetCandidate;
use TYPO3\CMS\Core\Html\Srcset\SrcsetCandidate;
use TYPO3\CMS\Core\Html\Srcset\WidthSrcsetCandidate;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SrcsetCandidateTest extends UnitTestCase
{
    #[Test]
    public function uriGetsSanitized(): void
    {
        $subject = new WidthSrcsetCandidate(10);
        $subject->setUri('path/to/image 1,test.jpg');
        self::assertEquals('path/to/image 1,test.jpg', $subject->getUri());
        self::assertEquals('path/to/image%201%2Ctest.jpg', $subject->getSanitizedUri());
    }

    public static function calculateWidthDataProvider(): array
    {
        return [
            [new WidthSrcsetCandidate(10), 10],
            [new WidthSrcsetCandidate(10)->setWidth(20), 20],
            [new DensitySrcsetCandidate(2.0, 100), 200],
            [new DensitySrcsetCandidate(2.0, 100)->setDensity(1.0), 100],
            [new DensitySrcsetCandidate(2.0, 100)->setReferenceWidth(200), 400],
        ];
    }

    #[Test]
    #[DataProvider('calculateWidthDataProvider')]
    public function calculateWidth(SrcsetCandidate $subject, int $expectedWidth): void
    {
        self::assertEquals($expectedWidth, $subject->getCalculatedWidth());
    }

    #[Test]
    public function calculateWidthFailsForDensityWithoutReferenceWidth(): void
    {
        self::expectExceptionCode(1697743145);
        $this->expectExceptionMessage('Reference width needs to be specified if pixel density descriptors (e. g. 2x) are used in srcset: 2x');
        new DensitySrcsetCandidate(2.0)->getCalculatedWidth();
    }

    public static function generateDescriptorDataProvider(): array
    {
        return [
            [new WidthSrcsetCandidate(10), '10w'],
            [new DensitySrcsetCandidate(2.0, 100), '2x'],
            [new DensitySrcsetCandidate(1.5, 100), '1.5x'],
        ];
    }

    #[Test]
    #[DataProvider('generateDescriptorDataProvider')]
    public function generateDescriptor(SrcsetCandidate $subject, string $expectedDescriptor): void
    {
        self::assertEquals($expectedDescriptor, $subject->getDescriptor());
    }

    #[Test]
    public function generateSrcset(): void
    {
        $candidate = new WidthSrcsetCandidate(100)->setUri('path/to/image 1.jpg');
        self::assertEquals('path/to/image%201.jpg 100w', $candidate->generateSrcset());
        self::assertEquals('path/to/image%201.jpg 100w', (string)$candidate);
    }

    public static function createFromDescriptorDataProvider(): array
    {
        return [
            ['100w', null, '100w'],
            ['2x', null, '2x'],
        ];
    }

    #[Test]
    #[DataProvider('createFromDescriptorDataProvider')]
    public function createFromDescriptor(string $descriptor, ?int $referenceWidth, string $expectedDescriptor): void
    {
        $candidate = SrcsetCandidate::createFromDescriptor($descriptor, $referenceWidth);
        self::assertEquals($expectedDescriptor, $candidate->getDescriptor());
    }

    public static function createFromInvalidDescriptorThrowsExceptionDataProvider(): array
    {
        return [
            ['100'],
            ['foo'],
            ['100px'],
        ];
    }

    #[Test]
    #[DataProvider('createFromInvalidDescriptorThrowsExceptionDataProvider')]
    public function createFromInvalidDescriptorThrowsException(string $descriptor): void
    {
        self::expectExceptionCode(1774527269);
        SrcsetCandidate::createFromDescriptor($descriptor);
    }
}
