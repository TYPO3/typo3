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

namespace TYPO3\CMS\Core\Tests\Unit\DataHandling\Model;

use TYPO3\CMS\Core\DataHandling\Model\CorrelationId;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CorrelationIdTest extends UnitTestCase
{
    public function canBeParsedDataProvider(): array
    {
        return [
            [
                '0400$subject',
                ['scope' => null, 'subject' => 'subject', 'aspects' => []],
            ],
            [
                '0400$scope:subject',
                ['scope' => 'scope', 'subject' => 'subject', 'aspects' => []],
            ],
            [
                '0400$scope:subject/aspect-a',
                ['scope' => 'scope', 'subject' => 'subject', 'aspects' => ['aspect-a']],
            ],
            [
                '0400$scope:subject/aspect-a/aspect-b',
                ['scope' => 'scope', 'subject' => 'subject', 'aspects' => ['aspect-a', 'aspect-b']],
            ],
        ];
    }

    /**
     * @param string $string
     * @param array $expectations
     *
     * @test
     * @dataProvider canBeParsedDataProvider
     */
    public function canBeParsed(string $string, array $expectations): void
    {
        $correlationId = CorrelationId::fromString($string);
        foreach ($expectations as $propertyName => $propertyValue) {
            self::assertSame(
                $propertyValue,
                $correlationId->{'get' . ucfirst($propertyName)}()
            );
        }
    }

    /**
     * @test
     */
    public function subjectIsConsidered(): void
    {
        $correlationId = CorrelationId::forSubject('subject')
            ->withAspects('aspect-a');
        self::assertSame('0400$subject/aspect-a', (string)$correlationId);
    }

    /**
     * @test
     */
    public function scopeIsConsidered(): void
    {
        $correlationId = CorrelationId::forScope('scope')
            ->withSubject('subject')
            ->withAspects('aspect-a');
        self::assertSame('0400$scope:subject/aspect-a', (string)$correlationId);
    }

    /**
     * @test
     */
    public function doesNotVary(): void
    {
        $correlationId = '0400$scope:subject/aspect-a/aspect-b';
        self::assertSame(
            $correlationId,
            (string)CorrelationId::fromString($correlationId)
        );
    }
}
