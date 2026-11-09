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

namespace TYPO3\CMS\Lowlevel\Inspector;

/**
 * One line of the tree. Keys with exactly one child are joined into a chain,
 * so a node holds the keys of its whole chain.
 *
 * @internal
 */
final class TreeNode
{
    /**
     * @var list<TreeNode>|null
     */
    private ?array $children = null;
    public bool $expanded = false;

    /**
     * @param list<string|int> $keys
     */
    private function __construct(
        public readonly array $keys,
        public readonly mixed $value,
        public readonly int $level,
    ) {}

    public static function create(string|int $key, mixed $value, int $level): self
    {
        $keys = [$key];
        while (is_array($value) && count($value) === 1) {
            $key = array_key_first($value);
            $keys[] = $key;
            $value = $value[$key];
        }
        return new self($keys, $value, $level);
    }

    public function isBranch(): bool
    {
        return is_array($this->value) && $this->value !== [];
    }

    /**
     * @return list<TreeNode>
     */
    public function children(): array
    {
        if ($this->children === null) {
            $this->children = [];
            foreach ($this->isBranch() ? $this->value : [] as $key => $item) {
                $this->children[] = self::create($key, $item, $this->level + 1);
            }
        }
        return $this->children;
    }
}
