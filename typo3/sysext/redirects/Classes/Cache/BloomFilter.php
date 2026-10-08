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

namespace TYPO3\CMS\Redirects\Cache;

/**
 * Space-efficient probabilistic set of strings: mightContain() is always true for an added
 * key, and false for a key which has not been added - except for a configurable rate of
 * false positives. It allows to rule out a database lookup without having all keys in memory.
 *
 * @internal
 */
final class BloomFilter
{
    private const MIN_BIT_COUNT = 64;

    private function __construct(
        private string $bits,
        private readonly int $bitCount,
        private readonly int $hashCount,
    ) {}

    /**
     * Creates an empty filter sized for the expected number of keys. Adding more keys
     * than expected is possible, but increases the false positive rate.
     */
    public static function create(int $expectedKeyCount, float $falsePositiveRate = 0.01): self
    {
        if ($falsePositiveRate <= 0.0 || $falsePositiveRate >= 1.0) {
            throw new \InvalidArgumentException('The false positive rate must be between 0 and 1.', 1791470082);
        }
        $expectedKeyCount = max(1, $expectedKeyCount);
        $bitCount = max(self::MIN_BIT_COUNT, (int)ceil(-$expectedKeyCount * log($falsePositiveRate) / (M_LN2 ** 2)));
        $hashCount = max(1, (int)round($bitCount / $expectedKeyCount * M_LN2));
        return new self(str_repeat("\0", intdiv($bitCount + 7, 8)), $bitCount, $hashCount);
    }

    /**
     * Restores a filter exported by toArray(), returns null for invalid data.
     */
    public static function fromArray(array $data): ?self
    {
        $bits = $data['bits'] ?? null;
        $bitCount = $data['bitCount'] ?? null;
        $hashCount = $data['hashCount'] ?? null;
        if (!is_string($bits) || !is_int($bitCount) || !is_int($hashCount)
            || $bitCount < self::MIN_BIT_COUNT || $hashCount < 1 || strlen($bits) !== intdiv($bitCount + 7, 8)
        ) {
            return null;
        }
        return new self($bits, $bitCount, $hashCount);
    }

    /**
     * @return array{bits: string, bitCount: int, hashCount: int}
     */
    public function toArray(): array
    {
        return ['bits' => $this->bits, 'bitCount' => $this->bitCount, 'hashCount' => $this->hashCount];
    }

    public function add(string $key): void
    {
        foreach ($this->getBitPositions($key) as $position) {
            $byte = $position >> 3;
            $this->bits[$byte] = chr(ord($this->bits[$byte]) | (1 << ($position & 7)));
        }
    }

    public function mightContain(string $key): bool
    {
        foreach ($this->getBitPositions($key) as $position) {
            if ((ord($this->bits[$position >> 3]) & (1 << ($position & 7))) === 0) {
                return false;
            }
        }
        return true;
    }

    /**
     * Derives all bit positions from two halves of a single hash (Kirsch-Mitzenmacher).
     *
     * @return list<int>
     */
    private function getBitPositions(string $key): array
    {
        [1 => $hash1, 2 => $hash2] = unpack('V2', hash('xxh3', $key, true));
        $hash2 |= 1;
        $positions = [];
        for ($i = 0; $i < $this->hashCount; $i++) {
            $positions[] = ($hash1 + $i * $hash2) % $this->bitCount;
        }
        return $positions;
    }
}
