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

class VariableCompiler
{
    public const FLAG_MERGE_OVERRIDE = 1;
    public const FLAG_MERGE_RECURSIVE = 2;

    /**
     * @var array
     */
    private $items;

    /**
     * @var Variables
     */
    private $variables;

    /**
     * @var int|null
     */
    private $flags;

    /**
     * @var array
     */
    private $results;

    public static function create(array $items, Variables $variables, int $flags = self::FLAG_MERGE_RECURSIVE): self
    {
        return new static($items, $variables, $flags);
    }

    public function __construct(array $items, Variables $variables, int $flags = self::FLAG_MERGE_RECURSIVE)
    {
        $this->items = $items;
        $this->variables = $variables;
        $this->flags = $flags;
    }

    public function compile(): self
    {
        $this->results = $this->compileKeys($this->items);
        $this->results = $this->compileValues($this->results);
        return $this;
    }

    /**
     * @return array
     */
    public function getResults(): array
    {
        return $this->results;
    }

    private function compileKeys(array $array): array
    {
        $result = [];
        // applying VariableItems
        foreach ($array as $index => $item) {
            $result = $this->assignItems(
                $result,
                $item instanceof VariableItem ? $item->apply($this->variables) : [$index => $item]
            );
        }
        // traversing into nested array items (if any)
        foreach ($result as $index => $item) {
            if (is_array($item)) {
                $result[$index] = $this->compileKeys($item);
            }
        }
        return $result;
    }

    private function assignItems(array $array, array $items): array
    {
        if ($this->flags & self::FLAG_MERGE_OVERRIDE) {
            // keep possible numeric indexes
            return $array + $items;
        }
        return array_replace_recursive($array, $items);
    }

    private function compileValues(array $array): array
    {
        foreach ($array as &$item) {
            if (is_array($item)) {
                $item = $this->compileValues($item);
            } elseif ($item instanceof Variable) {
                $item = $item->apply($this->variables);
            } elseif ($item instanceof VariableValue) {
                $item = $item->apply($this->variables);
            }
        }
        return $array;
    }
}
