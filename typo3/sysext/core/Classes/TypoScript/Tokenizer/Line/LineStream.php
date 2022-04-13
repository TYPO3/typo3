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
}
