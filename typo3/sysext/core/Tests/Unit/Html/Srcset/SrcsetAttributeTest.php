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
use TYPO3\CMS\Core\Html\Srcset\SrcsetAttribute;
use TYPO3\CMS\Core\Html\Srcset\WidthSrcsetCandidate;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SrcsetAttributeTest extends UnitTestCase
{
    public static function createFromDescriptorsDataProvider(): array
    {
        return [
            'widths' => [['100w', '200w', '300w'], null, 'dummy.jpg 100w, dummy.jpg 200w, dummy.jpg 300w'],
            'densities' => [['1x', '2x'], 100, 'dummy.jpg 1x, dummy.jpg 2x'],
            'empty array' => [[], null, ''],
        ];
    }

    #[Test]
    #[DataProvider('createFromDescriptorsDataProvider')]
    public function createFromDescriptors($descriptors, ?int $referenceWidth, string $expectedSrcset): void
    {
        $subject = SrcsetAttribute::createFromDescriptors($descriptors, $referenceWidth);
        array_map(fn($candidate) => $candidate->setUri('dummy.jpg'), $subject->getCandidates());
        self::assertEquals($expectedSrcset, $subject->generateSrcset());
    }

    #[Test]
    public function generateSrcsetWithMixedCandidates(): void
    {
        static::expectExceptionCode(1697745459);
        $this->expectExceptionMessage('Invalid mix of w and x descriptors in srcset: dummy@100.jpg 100w, ..., dummy@2x.jpg 2x');
        new SrcsetAttribute()
            ->addCandidate(new WidthSrcsetCandidate(100)->setUri('dummy@100.jpg'))
            ->addCandidate(new WidthSrcsetCandidate(200)->setUri('dummy@200.jpg'))
            ->addCandidate(new DensitySrcsetCandidate(2.0)->setUri('dummy@2x.jpg'));
    }

    #[Test]
    public function generateSrcsetWithDuplicates(): void
    {
        $subject = new SrcsetAttribute()
            ->addCandidate(new WidthSrcsetCandidate(100)->setUri('dummy@100.jpg'))
            ->addCandidate(new WidthSrcsetCandidate(200)->setUri('dummy@200.jpg'))
            ->addCandidate(new WidthSrcsetCandidate(200)->setUri('dummy@200.jpg'));
        self::assertEquals('dummy@100.jpg 100w, dummy@200.jpg 200w', $subject->generateSrcset());
        self::assertEquals('dummy@100.jpg 100w, dummy@200.jpg 200w', (string)$subject);
    }
}
