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

namespace TYPO3\CMS\Core\TypoScript\Tokenizer\Token;

/**
 * A list of single identifier (!) tokens: TokenType::T_IDENTIFIER, and only of those.
 *
 * This is used in TS lines that know certain parts have to be lists of identifier tokens only.
 * For instance a LineIdentifierAssignment "foo.bar = barValue" return this stream for getIdentifierTokenStream():
 * The left side of an assignment line is a list of identifier tokens.
 *
 * Identifiers can be "relative" on the right side for "<" (LineIdentifierCopy) and "=<" (LineIdentifierReference).
 * Examples are "foo.bar < .baz" and "foo.bar =< .baz". These are identified by having a "." (dot) at the beginning
 * on the right side. For these places, the toggle "relative" is set to true for the AST-builder to look for relative
 * copy and copy-reference. The generic example are "relative" references in TS menus: 'RO < .NO'
 *
 * For example, with "foo.bar < baz", the Tokenizer creates a LineIdentifierCopy line, having a TokenStreamIdentifier
 * list of the T_IDENTIFIER tokens for 'foo' and 'bar' for getIdentifierTokenStream(), plus a TokenStreamIdentifier list
 * of T_IDENTIFIER tokens for 'baz' for getValueTokenStream().
 *
 * Note identifier streams on the left side (foo.bar = ...) are never relative, this toggle is true for "<" and "=<" only.
 *
 * Lines that know they can only return TokenStreamIdentifier's - they are more specific than just TokenStream, are
 * type-hinted as such. For instance getIdentifierTokenStream() type hints TokenStreamIdentifier.
 *
 * @internal: Internal tokenizer structure.
 */
final class IdentifierTokenStream extends AbstractTokenStream
{
    private bool $relative = false;

    /**
     * When rendering a source string from multiple identifiers, dots between single identifiers need to be added again.
     * This is used in RootNode->toArray() to create that insane '< lib.whatever' as value when using the
     * reference operator: "foo =< lib.whatever". See ContentObjectRenderer cObjGetSingle() and mergeTSRef().
     */
    public function __toString(): string
    {
        $source = [];
        $this->reset();
        while ($token = $this->getNext()) {
            $source[] = (string)$token;
        }
        $source = implode('.', $source);
        if ($this->relative) {
            $source = '.' . $source;
        }
        return $source;
    }

    protected function serialize(): array
    {
        $result = parent::serialize();
        if ($this->isRelative()) {
            $result['relative'] = true;
        }
        return $result;
    }

    /**
     * Append a token to the stream.
     */
    public function append(TokenInterface $token): self
    {
        if ($token->getType() !== TokenType::T_IDENTIFIER) {
            throw new \LogicException(
                'Trying to add a token of type TokenType::' . $token->getType()->name . ' to class TokenStreamIdentifier, but only TokenType::T_IDENTIFIERS are allowed.',
                1655138907
            );
        }
        $this->tokens[] = $token;
        return $this;
    }

    /**
     * This identifier token stream is relative! There is a dot on the right side of something like "foo.bar < .baz"
     */
    public function setRelative(): self
    {
        $this->relative = true;
        return $this;
    }

    /**
     * True if this identifier stream is relative to given context.
     */
    public function isRelative(): bool
    {
        return $this->relative;
    }
}
