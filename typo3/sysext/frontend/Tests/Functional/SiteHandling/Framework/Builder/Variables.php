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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\Framework\Builder;

class Variables extends \ArrayObject
{
    public static function create(array $items = []): self
    {
        return new static($items);
    }

    public function keys(): array
    {
        return array_keys($this->getArrayCopy());
    }

    public function values(): array
    {
        return array_values($this->getArrayCopy());
    }

    public function define(array $items): self
    {
        $this->exchangeArray(array_merge(
            $items,
            $this->getArrayCopy()
        ));
        return $this;
    }

    public function merge(array $items): self
    {
        $this->exchangeArray(array_merge(
            $this->getArrayCopy(),
            $items
        ));
        return $this;
    }

    public function withDefined(?Variables $other): self
    {
        if ($other === null || $other === $this) {
            return $this;
        }
        $target = clone $this;
        $target->define($other->getArrayCopy());
        return $target;
    }

    public function withMerged(?Variables $other): self
    {
        if ($other === null || $other === $this) {
            return $this;
        }
        $target = clone $this;
        $target->merge($other->getArrayCopy());
        return $target;
    }
}
