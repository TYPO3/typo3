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

use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\IdentifierTokenStream;

/**
 * A block open line: "foo.bar {".
 *
 * $identifierTokenStream is a stream of tokens on the left side, "foo"
 * and "bar" token in the example above. That stream must not be empty.
 *
 * @internal: Internal tokenizer structure.
 */
final class IdentifierBlockOpenLine extends AbstractLine
{
    private IdentifierTokenStream $identifierTokenStream;

    public function setIdentifierTokenStream(IdentifierTokenStream $tokenStream): static
    {
        if ($tokenStream->isEmpty()) {
            throw new \LogicException('Identifier token stream must not be empty', 1655824621);
        }
        $this->identifierTokenStream = $tokenStream;
        return $this;
    }

    public function getIdentifierTokenStream(): IdentifierTokenStream
    {
        return $this->identifierTokenStream;
    }
}
