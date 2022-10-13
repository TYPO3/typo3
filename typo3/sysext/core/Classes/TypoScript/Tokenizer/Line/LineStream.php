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

namespace TYPO3\CMS\Core\TypoScript\Tokenizer\Line;

/**
 * Each TypoScript snippet is turned by the tokenizers into a
 * stream of lines. Tokenizers return instances of this class.
 *
 * Iterate line streams in a foreach loop using getNextLine().
 *
 * @internal: Internal tokenizer structure.
 */
final class LineStream
{
    /**
     * @var LineInterface[]
     */
    private array $lines = [];
    protected int $currentIndex = -1;

    /**
     * Create a source string from given token lines. This is used in backend
     * to turn the "full" token streams of lines into strings for output.
     */
    public function __toString(): string
    {
        $source = '';
        foreach ($this->getNextLine() as $line) {
            // We do *not* implement __toString() on lines since this is a
            // backend thing only, and we do not want to accidentally stringify
            // lines based on the full stream anywhere.
            $source .= $line->getTokenStream()->reset();
        }
        return $source;
    }

    /**
     * When storing to cache, we only store FE relevant properties and skip
     * irrelevant things. In particular, $currentIndex should always initialize
     * to -1 and does not need to be stored.
     */
    final public function __serialize(): array
    {
        return [
            'lines' => $this->lines,
        ];
    }

    /**
     * Stream creation.
     */
    public function append(LineInterface $line): self
    {
        $this->lines[] = $line;
        return $this;
    }

    /**
     * We sometimes create a line stream but don't add lines.
     * This method returns true if lines have been added.
     */
    public function isEmpty(): bool
    {
        return empty($this->lines);
    }

    /**
     * @return iterable<LineInterface>
     */
    public function getNextLine(): iterable
    {
        foreach ($this->lines as $child) {
            yield $child;
        }
    }

    /**
     * Reset current pointer. Typically, call this before iterating with getNext().
     */
    public function reset(): self
    {
        $this->currentIndex = -1;
        return $this;
    }

    /**
     * Get next line and raise pointer.
     *
     * Methods getNext(), peekNext() and reset() are an alternative to
     * getNextLine() which allow peek of the next line, which getNextLine()
     * does not. The disadvantage is that these methods create internal
     * state in $this->currentIndex, which getNextLine() does not. Use
     * getNext() iteration only if peekNext() is needed to avoid creating
     * useless state.
     */
    public function getNext(): ?LineInterface
    {
        $this->currentIndex ++;
        return $this->lines[$this->currentIndex] ?? null;
    }

    /**
     * Get next line but do not raise pointer.
     */
    public function peekNext(): ?LineInterface
    {
        return $this->lines[$this->currentIndex + 1] ?? null;
    }
}
