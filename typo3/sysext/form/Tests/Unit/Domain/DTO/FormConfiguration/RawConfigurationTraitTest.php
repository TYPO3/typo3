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

namespace TYPO3\CMS\Form\Tests\Unit\Domain\DTO\FormConfiguration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Form\Domain\DTO\FormConfiguration\RawConfigurationTrait;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RawConfigurationTraitTest extends UnitTestCase
{
    /**
     * @param array<string, mixed> $raw
     */
    private function createSubject(array $raw): object
    {
        return new readonly class ($raw) {
            use RawConfigurationTrait;

            /**
             * @param array<string, mixed> $raw
             */
            public function __construct(public array $raw) {}
        };
    }

    #[Test]
    public function getRawReturnsCompleteConfiguration(): void
    {
        $raw = ['a' => 1, 'b' => ['c' => 2]];
        self::assertSame($raw, $this->createSubject($raw)->getRaw());
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: mixed, 3: mixed}>
     */
    public static function getReturnsValueDataProvider(): array
    {
        return [
            'top level value' => [['a' => 'value'], 'a', null, 'value'],
            'nested value' => [['a' => ['b' => 'value']], 'a.b', null, 'value'],
            'deeply nested value' => [['a' => ['b' => ['c' => 'value']]], 'a.b.c', null, 'value'],
            'missing top level returns default' => [['a' => 'value'], 'x', 'fallback', 'fallback'],
            'missing nested returns default' => [['a' => ['b' => 'value']], 'a.x', 'fallback', 'fallback'],
            'path into scalar returns default' => [['a' => 'value'], 'a.b', 'fallback', 'fallback'],
            'null default when omitted' => [['a' => 'value'], 'x', null, null],
            'zero value is returned' => [['a' => 0], 'a', 'fallback', 0],
            'null value is returned' => [['a' => null], 'a', 'fallback', null],
        ];
    }

    /**
     * @param array<string, mixed> $raw
     */
    #[Test]
    #[DataProvider('getReturnsValueDataProvider')]
    public function getReturnsValue(array $raw, string $path, mixed $default, mixed $expected): void
    {
        self::assertSame($expected, $this->createSubject($raw)->get($path, $default));
    }

    /**
     * @return array<string, array{0: array<string, mixed>, 1: string, 2: bool}>
     */
    public static function hasReturnsExpectedResultDataProvider(): array
    {
        return [
            'top level exists' => [['a' => 'value'], 'a', true],
            'nested exists' => [['a' => ['b' => 'value']], 'a.b', true],
            'null value still exists' => [['a' => null], 'a', true],
            'top level missing' => [['a' => 'value'], 'x', false],
            'nested missing' => [['a' => ['b' => 'value']], 'a.x', false],
            'path into scalar missing' => [['a' => 'value'], 'a.b', false],
        ];
    }

    /**
     * @param array<string, mixed> $raw
     */
    #[Test]
    #[DataProvider('hasReturnsExpectedResultDataProvider')]
    public function hasReturnsExpectedResult(array $raw, string $path, bool $expected): void
    {
        self::assertSame($expected, $this->createSubject($raw)->has($path));
    }
}
