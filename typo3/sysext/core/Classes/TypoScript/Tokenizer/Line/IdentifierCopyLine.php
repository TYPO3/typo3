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
 * A line using the copy operator: "foo.bar < lib.myLib".
 *
 * Contains a stream of tokens for the left side ("foo" and "bar" tokens) and
 * a stream of tokens for the right side ("lib" and "myLib"). None of these
 * token streams can be empty, it's an InvalidLine otherwise.
 *
 * Note the right side TokenStreamIdentifier can be relative: "foo.bar < .baz".
 * Flag $relative in TokenStreamIdentifier represents this start dot on the right side.
 *
 * None of the two streams can be empty.
 *
 * @internal: Internal tokenizer structure.
 */
final class IdentifierCopyLine extends AbstractLine
{
    private IdentifierTokenStream $identifierTokenStream;
    private IdentifierTokenStream $valueTokenStream;

    public function setIdentifierTokenStream(IdentifierTokenStream $tokenStream): static
    {
        if ($tokenStream->isEmpty()) {
            throw new \LogicException('Identifier token stream must not be empty', 1655824946);
        }
        $this->identifierTokenStream = $tokenStream;
        return $this;
    }

    public function getIdentifierTokenStream(): IdentifierTokenStream
    {
        return $this->identifierTokenStream;
    }

    public function setValueTokenStream(IdentifierTokenStream $tokenStream): static
    {
        if ($tokenStream->isEmpty()) {
            throw new \LogicException('Value token stream must not be empty', 1655824947);
        }
        $this->valueTokenStream = $tokenStream;
        return $this;
    }

    public function getValueTokenStream(): IdentifierTokenStream
    {
        return $this->valueTokenStream;
    }
}
