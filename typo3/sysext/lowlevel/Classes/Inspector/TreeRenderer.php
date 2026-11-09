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
 * Renders normalized data as an indented tree.
 *
 * Without a fixed depth the first level is always complete and the tree fits into a
 * budget of lines below it: branches are expanded breadth first while their whole next
 * level fits, the rest is shown inline (complete JSON on one line) or as a truncated
 * preview. If previews remain, small flat branches are folded inline to make room for them.
 *
 * @internal
 */
final readonly class TreeRenderer
{
    public const int BUDGET = 200;
    private const int MAX_FOLDABLE_VALUES = 5;

    public function __construct(
        private ValueFormatter $formatter,
    ) {}

    /**
     * @param int|null $depth null for the adaptive budget, 0 for everything
     * @return list<StyledText>
     */
    public function render(mixed $data, int $width, ?int $depth): array
    {
        if ($this->formatter->isLeaf($data)) {
            return [$this->appendComment($this->formatter->value($data), $this->labelComment($data), PHP_INT_MAX)];
        }
        $nodes = [];
        foreach ($data as $key => $value) {
            $nodes[] = TreeNode::create($key, $value, 1);
        }
        if ($depth === null) {
            $this->layoutWithBudget($nodes, $width);
        } else {
            $this->expandToDepth($nodes, $depth);
        }
        $lines = [];
        $counts = ['collapsed' => 0, 'shortened' => 0];
        $this->renderNodes($nodes, $width, $lines, $counts);
        $hint = $this->hint($counts);
        if ($hint !== null) {
            $lines[] = $hint;
        }
        return $lines;
    }

    /**
     * @param list<TreeNode> $nodes
     */
    private function layoutWithBudget(array $nodes, int $width): void
    {
        $used = count($nodes);
        $queue = array_values(array_filter($nodes, static fn(TreeNode $node): bool => $node->isBranch()));
        while ($queue !== []) {
            $nextLevel = [];
            foreach ($queue as $node) {
                $children = $node->children();
                if ($used + count($children) > self::BUDGET) {
                    continue;
                }
                $node->expanded = true;
                $used += count($children);
                foreach ($children as $child) {
                    if ($child->isBranch()) {
                        $nextLevel[] = $child;
                    }
                }
            }
            $queue = $nextLevel;
        }
        $this->useFoldedLines($nodes, $width, $used);
    }

    /**
     * Second pass: fold small flat branches inline to expand previews, shallowest first,
     * as long as the whole next level of a preview fits.
     *
     * @param list<TreeNode> $nodes
     */
    private function useFoldedLines(array $nodes, int $width, int $used): void
    {
        do {
            $changed = false;
            foreach ($this->previews($nodes, $width) as $target) {
                $cost = count($target->children());
                $free = self::BUDGET - $used;
                $folds = [];
                foreach ($cost > $free ? $this->foldCandidates($nodes, $width) : [] as $candidate) {
                    if ($free >= $cost) {
                        break;
                    }
                    $folds[] = $candidate;
                    $free += count($candidate->children());
                }
                if ($free < $cost) {
                    continue;
                }
                foreach ($folds as $candidate) {
                    $candidate->expanded = false;
                    $used -= count($candidate->children());
                }
                $target->expanded = true;
                $used += $cost;
                $changed = true;
                break;
            }
        } while ($changed);
    }

    /**
     * Branches shown as truncated preview, ordered by level and position.
     *
     * @param list<TreeNode> $nodes
     * @return list<TreeNode>
     */
    private function previews(array $nodes, int $width): array
    {
        $previews = [];
        $this->walkVisible($nodes, function (TreeNode $node) use (&$previews, $width): void {
            if ($node->isBranch() && !$node->expanded && !$this->fitsInline($node, $width)) {
                $previews[] = $node;
            }
        });
        usort($previews, static fn(TreeNode $a, TreeNode $b): int => $a->level <=> $b->level);
        return $previews;
    }

    /**
     * Expanded branches holding at most five scalar values that fit on one line, most lines first.
     *
     * @param list<TreeNode> $nodes
     * @return list<TreeNode>
     */
    private function foldCandidates(array $nodes, int $width): array
    {
        $candidates = [];
        $this->walkVisible($nodes, function (TreeNode $node) use (&$candidates, $width): void {
            if ($node->expanded
                && count($node->value) <= self::MAX_FOLDABLE_VALUES
                && array_filter($node->value, is_array(...)) === []
                && $this->fitsInline($node, $width)
            ) {
                $candidates[] = $node;
            }
        });
        usort($candidates, static fn(TreeNode $a, TreeNode $b): int => count($b->value) <=> count($a->value));
        return $candidates;
    }

    /**
     * @param list<TreeNode> $nodes
     */
    private function walkVisible(array $nodes, \Closure $callback): void
    {
        foreach ($nodes as $node) {
            $callback($node);
            if ($node->expanded) {
                $this->walkVisible($node->children(), $callback);
            }
        }
    }

    /**
     * @param list<TreeNode> $nodes
     */
    private function expandToDepth(array $nodes, int $depth): void
    {
        foreach ($nodes as $node) {
            if ($node->isBranch() && ($depth === 0 || $node->level < $depth)) {
                $node->expanded = true;
                $this->expandToDepth($node->children(), $depth);
            }
        }
    }

    private function fitsInline(TreeNode $node, int $width): bool
    {
        $available = $this->availableWidth($node, $width);
        return $this->formatter->inline($node->value, $available)->width() <= $available;
    }

    private function availableWidth(TreeNode $node, int $width): int
    {
        return $width - $this->prefix($node)->width() - 3;
    }

    private function prefix(TreeNode $node): StyledText
    {
        return new StyledText()
            ->append(str_repeat('  ', $node->level - 1))
            ->concat($this->formatter->keys($node->keys));
    }

    /**
     * @param list<TreeNode> $nodes
     * @param list<StyledText> $lines
     * @param array{collapsed: int, shortened: int} $counts
     */
    private function renderNodes(array $nodes, int $width, array &$lines, array &$counts): void
    {
        foreach ($nodes as $node) {
            $prefix = $this->prefix($node);
            if ($node->expanded) {
                $lines[] = $prefix;
                $this->renderNodes($node->children(), $width, $lines, $counts);
                continue;
            }
            $available = $this->availableWidth($node, $width);
            if ($node->isBranch()) {
                $text = $this->formatter->inline($node->value, $available);
                $labels = $this->formatter->countResolvedLabels($node->value);
                $comment = $labels > 0 ? $labels . ($labels === 1 ? ' label' : ' labels') . ' resolved' : null;
                $countKey = 'collapsed';
            } else {
                $text = $this->formatter->value($node->value);
                $comment = $this->labelComment($node->value);
                $countKey = 'shortened';
            }
            if ($text->width() > $available) {
                $counts[$countKey]++;
                $text = $text->truncate(max(1, $available))->withStyle(StyledText::STYLE_STRING, StyledText::STYLE_DIM);
            }
            $lines[] = $prefix
                ->append(' = ', StyledText::STYLE_DIM)
                ->concat($this->appendComment($text, $comment, $available));
        }
    }

    private function labelComment(mixed $value): ?string
    {
        return $value instanceof ResolvedLabel ? $value->reference : null;
    }

    /**
     * Appends "  # comment" if it fits, shortened if needed, and leaves it out otherwise.
     */
    private function appendComment(StyledText $text, ?string $comment, int $available): StyledText
    {
        if ($comment === null) {
            return $text;
        }
        $commentText = new StyledText()->append('  # ' . $this->formatter->escapeUnsafeCharacters($comment), StyledText::STYLE_DIM);
        $remaining = $available - $text->width();
        if ($commentText->width() <= $remaining) {
            return $text->concat($commentText);
        }
        if ($remaining >= 6) {
            return $text->concat($commentText->truncate($remaining)->withStyle(StyledText::STYLE_ELLIPSIS, StyledText::STYLE_DIM));
        }
        return $text;
    }

    /**
     * @param array{collapsed: int, shortened: int} $counts
     */
    private function hint(array $counts): ?StyledText
    {
        $parts = [];
        if ($counts['collapsed'] > 0) {
            $parts[] = $counts['collapsed'] . ($counts['collapsed'] === 1 ? ' branch' : ' branches') . ' collapsed';
        }
        if ($counts['shortened'] > 0) {
            $parts[] = $counts['shortened'] . ($counts['shortened'] === 1 ? ' value' : ' values') . ' shortened';
        }
        if ($parts === []) {
            return null;
        }
        return new StyledText()->append(
            '→ ' . implode(', ', $parts) . '. Add segments, pass --depth=0, or use --flat.',
            StyledText::STYLE_DIM
        );
    }
}
