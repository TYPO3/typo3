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
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenStreamInterface;

/**
 * Simple "=" assignments and multiline "(" assignments: "foo.bar = barValue".
 *
 * Each line has two additional token streams: $identifierTokenStream for the
 * left side ("foo" and "bar" tokens) and $valueTokenStream for the right side
 * ("barValue" token). Right side is often a single token only, but can be many
 * tokens when constants and multiline assignments are involved.
 *
 * Neither the left, nor the right side streams can be empty: Even with "foo.bar ="
 * a T_VALUE token with empty value is created for the right side.
 *
 * @internal: Internal tokenizer structure.
 */
final class IdentifierAssignmentLine extends AbstractLine
{
    private IdentifierTokenStream $identifierTokenStream;
    private TokenStreamInterface $valueTokenStream;

    public function setIdentifierTokenStream(IdentifierTokenStream $tokenStream): static
    {
        if ($tokenStream->isEmpty()) {
            throw new \LogicException('Identifier token stream must not be empty', 1655824257);
        }
        $this->identifierTokenStream = $tokenStream;
        return $this;
    }

    public function getIdentifierTokenStream(): IdentifierTokenStream
    {
        return $this->identifierTokenStream;
    }

    public function setValueTokenStream(TokenStreamInterface $tokenStream): static
    {
        if ($tokenStream->isEmpty()) {
            throw new \LogicException('Value token stream must not be empty', 1655824258);
        }
        $this->valueTokenStream = $tokenStream;
        return $this;
    }

    public function getValueTokenStream(): TokenStreamInterface
    {
        return $this->valueTokenStream;
    }
}
