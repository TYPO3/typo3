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

namespace TYPO3\CMS\Backend\Template\Components;

final class ComponentGroup
{
    /**
     * @param array<string, ComponentInterface> $items
     */
    private array $items = [];

    public function __construct(
        public readonly string $identifier,
    ) {}

    public function get(string $identifier): ?ComponentInterface
    {
        return $this->items[$identifier] ?? null;
    }

    public function remove(string $identifier): true
    {
        if (isset($this->items[$identifier])) {
            unset($this->items[$identifier]);
        }
        return true;
    }

    /**
     * @return array<string, ComponentInterface>
     */
    public function getItems(?ComponentInterface $ifEmptyUseThis = null): array
    {
        $result = [];
        foreach ($this->items as $key => $item) {
            $result[$key] = $item ?? $ifEmptyUseThis;
        }
        return array_filter($result);
    }

    /**
     * @param array<string, ?ComponentInterface> $items
     */
    public function setItems(array $items): void
    {
        $this->items = [];
        foreach ($items as $identifier => $item) {
            $this->add($identifier, $item);
        }
    }

    /**
     * $before and $after references the string keys used for $identifier
     */
    public function add(string $identifier, ?ComponentInterface $item, string $before = '', string $after = ''): void
    {
        if ($before !== '' && $this->has($before)) {
            $end = array_splice($this->items, (int)(array_search($before, array_keys($this->items), true)));
            $this->items = array_merge($this->items, [$identifier => $item], $end);
        } elseif ($after !== '' && $this->has($after)) {
            $end = array_splice($this->items, (int)(array_search($after, array_keys($this->items), true)) + 1);
            $this->items = array_merge($this->items, [$identifier => $item], $end);
        } else {
            $this->items[$identifier] = $item;
        }
    }

    public function has(string $identifier): bool
    {
        return array_key_exists($identifier, $this->items);
    }
}
