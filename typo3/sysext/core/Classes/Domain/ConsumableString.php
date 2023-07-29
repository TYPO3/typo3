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

namespace TYPO3\CMS\Core\Domain;

/**
 * String wrapper that keeps track of how often the value was consumed.
 * This can be used to make decisions during runtime, depending on whether
 * a provided value actually has been used (e.g. in rendered content).
 */
class ConsumableString implements \Countable, \Stringable
{
    /**
     * @internal use the `consume()` method instead
     */
    public readonly string $value;
    private int $counter = 0;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function __toString(): string
    {
        return $this->consume();
    }

    public function count(): int
    {
        return $this->counter;
    }

    public function consume(): string
    {
        $this->counter++;
        return $this->value;
    }
}
