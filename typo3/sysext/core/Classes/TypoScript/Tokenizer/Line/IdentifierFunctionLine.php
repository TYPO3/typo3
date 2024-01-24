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
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenStreamInterface;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;

/**
 * A line with a function assignment using the ":=" operator: "foo.bar := addToList(42)".
 *
 * Contains $identifierTokenStream for the left side ("foo" and "bar" token), a single
 * token for the function name ("addToList"), and an optional token for the value ("42").
 * Note the value token is optional since there are functions without values (eg. "uniqueList()").
 *
 * @internal: Internal tokenizer structure.
 */
final class IdentifierFunctionLine extends AbstractLine
{
    private ?IdentifierTokenStream $identifierTokenStream = null;
    private ?Token $functionNameToken = null;
    private ?TokenStreamInterface $functionValueTokenStream = null;

    public function setIdentifierTokenStream(IdentifierTokenStream $tokenStream): IdentifierFunctionLine
    {
        if ($tokenStream->isEmpty()) {
            throw new \LogicException('Identifier token stream must not be empty', 1655825120);
        }
        $this->identifierTokenStream = $tokenStream;
        return $this;
    }

    public function getIdentifierTokenStream(): IdentifierTokenStream
    {
        if ($this->identifierTokenStream === null) {
            throw new \RuntimeException('Identifier token stream has not been set', 1717495444);
        }
        return $this->identifierTokenStream;
    }

    public function setFunctionNameToken(Token $token): IdentifierFunctionLine
    {
        if ($token->getType() !== TokenType::T_FUNCTION_NAME) {
            throw new \LogicException('Function name token must be of type T_FUNCTION_NAME', 1655825121);
        }
        $this->functionNameToken = $token;
        return $this;
    }

    public function getFunctionNameToken(): Token
    {
        if ($this->functionNameToken === null) {
            throw new \RuntimeException('Function name token has not been set', 1717495576);
        }
        return $this->functionNameToken;
    }

    public function setFunctionValueTokenStream(TokenStreamInterface $tokenStream): IdentifierFunctionLine
    {
        $this->functionValueTokenStream = $tokenStream;
        return $this;
    }

    public function getFunctionValueTokenStream(): TokenStreamInterface
    {
        if ($this->functionValueTokenStream === null) {
            throw new \RuntimeException('Function value token stream has not been set', 1717495996);
        }
        return $this->functionValueTokenStream;
    }
}
