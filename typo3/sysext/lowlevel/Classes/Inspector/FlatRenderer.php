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
 * Renders normalized data as one line per value: the shell-quoted path relative to
 * the selection, " = ", and the value as JSON. A selected single value is the value only.
 *
 * @internal
 */
final readonly class FlatRenderer
{
    public function __construct(
        private ValueFormatter $formatter,
    ) {}

    /**
     * @return list<StyledText>
     */
    public function render(mixed $data): array
    {
        $lines = [];
        $this->renderValue([], $data, $lines);
        return $lines;
    }

    /**
     * @param list<string|int> $path
     * @param list<StyledText> $lines
     */
    private function renderValue(array $path, mixed $value, array &$lines): void
    {
        if (!$this->formatter->isLeaf($value)) {
            foreach ($value as $key => $item) {
                $this->renderValue([...$path, $key], $item, $lines);
            }
            return;
        }
        $lines[] = $path === []
            ? $this->formatter->value($value)
            : $this->formatter->keys($path)->append(' = ', StyledText::STYLE_DIM)->concat($this->formatter->value($value));
    }
}
