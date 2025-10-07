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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy;

use TYPO3\CMS\Core\Type\Map;
use TYPO3\CMS\Core\Utility\StringUtility;

final class ConsumableNonce implements \Countable, \Stringable
{
    private const MIN_BYTES = 40;

    /**
     * @internal use the more specific methods `consumeInline()` or `consumeStatic()` instead
     */
    public readonly string $value;

    /**
     * @var Map<mixed, int>
     */
    private Map $inlineCount;

    /**
     * @var Map<mixed, int>
     */
    private Map $staticCount;

    public function __construct(?string $value = null)
    {
        if ($value === null || strlen($value) < self::MIN_BYTES) {
            $value = random_bytes(self::MIN_BYTES);
            $value = StringUtility::base64urlEncode($value);
        }
        $this->value = $value;
        $this->inlineCount = new Map();
        $this->staticCount = new Map();
    }

    public function __toString(): string
    {
        return $this->consumeInline();
    }

    public function count(): int
    {
        return $this->countInline() + $this->countStatic();
    }

    public function countInline(mixed $aspect = null): int
    {
        if ($aspect === null) {
            return array_sum($this->inlineCount->values());
        }
        return $this->inlineCount[$aspect] ?? 0;
    }

    public function countStatic(mixed $aspect = null): int
    {
        if ($aspect === null) {
            return array_sum($this->staticCount->values());
        }
        return $this->staticCount[$aspect] ?? 0;
    }

    /**
     * @internal consider using the more specific methods `consumeInline()` or `consumeStatic()` instead
     */
    public function consume(): string
    {
        return $this->consumeInline();
    }

    public function consumeInline(mixed $aspect = 'default'): string
    {
        // `\TYPO3\CMS\Core\Type\Map::offsetGet would` have to be `&offsetGet` for increments to work
        $this->inlineCount[$aspect] = ($this->inlineCount[$aspect] ?? 0) + 1;
        return $this->value;
    }

    public function consumeStatic(mixed $aspect = 'default'): string
    {
        // `\TYPO3\CMS\Core\Type\Map::offsetGet would` have to be `&offsetGet` for increments to work
        $this->staticCount[$aspect] = ($this->staticCount[$aspect] ?? 0) + 1;
        return $this->value;
    }
}
