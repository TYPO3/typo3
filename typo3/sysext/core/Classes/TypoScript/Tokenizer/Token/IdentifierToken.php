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
 * A special token if this token is a T_IDENTIFIER token:
 * With a line like "foo = bar", "foo" is created as TokenIdentifier TokenInterface
 * (as opposed to Token) having a TokenType::T_IDENTIFIER token.
 * The only difference to all other tokens is that TokenIdentifier tokens
 * quote any "." (dots) in their value with a backslash when output. This is
 * mostly used in backend when rendering source of TokenLine's.
 *
 * Note we do *not* explicitly check if TokenType::T_IDENTIFIER is given in
 * __construct() at the moment for performance reasons and inheritance considerations.
 *
 * @internal: Internal tokenizer structure.
 */
final class IdentifierToken extends AbstractToken
{
    public function __toString(): string
    {
        return str_replace('.', '\.', $this->value);
    }
}
