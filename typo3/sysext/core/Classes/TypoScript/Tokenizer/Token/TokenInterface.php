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
 * A readonly token: Each line of TypoScript is split into a list of lines consisting of
 * tokens by the tokenizers.
 *
 * As example, a "foo.bar = baz" line creates a LineIdentifierAssignment line, having
 * TokenType::T_IDENTIFIER 'foo', plus TokenType::T_IDENTIFIER 'bar' as TokenStream for
 * LineIdentifierAssignment->getIdentifierTokenStream(), plus a TokenType::T_VALUE 'baz'
 * as LineIdentifierAssignment->getValueTokenStream().
 *
 * We have two different Token implementations: The casual "Token" class for everything, plus
 * the "TokenIdentifier" class for identifier tokens. Identifier tokens are those "left" of
 * for instance an assignment like "foo.bar = baz" ("foo" and "bar" are TokenIdentifier instances),
 * and also on the right side when using expression with "<" and "=<" operator: Example "foo.bar < baz":
 * "baz" is an instance of a TokenIdentifier ("foo" and "bar" as well).
 *
 * The reason to have two implementations is that TokenIdentifier needs to be handled slightly
 * different when cast to string: For identifiers, all "." (dots) within a single identifier token
 * need to be quoted with "\" (backslash), to not confuse the parser. The classic use-case is having dots in
 * FlexForm identifiers for PageTS:
 * "foo.bar\.baz.foobar = value" - three identifier tokens (not four!): "foo", "bar.baz" and "foobar".
 * So the difference between "TokenIdentifier" and "Token" is just that "TokenIdentifier" quotes dots
 * in its value when string'ified, while Token does not and __toString() on Token simply says ->getValue().
 *
 * Multiple tokens are encapsulated in TokenStreamInterface. TokenStreamInterface has a __toString()
 * method as well, which calls __toString() on all assigned tokens. This way, a TokenIdentifier will
 * do its quoting magic, and casual Token instances return their value.
 *
 * The idea is here that TokenStreams are cast to string quite often. For instance, an assignment line
 * like "foo = bar" creates a token stream of one token for the right side (things after "="):
 * A T_VALUE Token instance with value "bar". The AST builder then at some point needs to resolve this
 * TokenStream to string. This will directly call __toString on token "bar", and does not deal with quoting,
 * since its no TokenIdentifier and just a Token.
 *
 * Note on getLine() and getColumn(): These two represent the position of a token in the source file:
 * We start counting at 0 (zero): The first token on the first line is line 0, column 0.
 * Only the LosslessTokenizer sets these, it's too expensive and of no relevance for the LossyTokenizer
 * that is used for instance in FE TS tokenizing. That's why these two properties are optional
 * and 0 (zero) by default.
 *
 * @internal: Internal tokenizer structure.
 */
interface TokenInterface
{
    public function __toString(): string;
    public function getType(): TokenType;
    public function getValue(): string;
    public function getLine(): int;
    public function getColumn(): int;
}
