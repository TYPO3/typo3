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

namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\Tokenizer\Token;

use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TokenStreamTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getNextReturnsToken(): void
    {
        $subject = new TokenStream();
        $token = new Token(TokenType::T_BLANK, ' ', 0, 0);
        $subject->append($token);
        self::assertSame($subject->getNext(), $token);
    }

    /**
     * @test
     */
    public function getNextReturnsNullIfThereIsNoToken(): void
    {
        $subject = new TokenStream();
        $token = new Token(TokenType::T_BLANK, ' ', 0, 0);
        $subject->append($token);
        $subject->getNext();
        self::assertNull($subject->getNext());
    }

    /**
     * @test
     */
    public function peekNextReturnsTokenAndDoesNotRaisePointer(): void
    {
        $subject = new TokenStream();
        $token = new Token(TokenType::T_BLANK, ' ', 0, 0);
        $subject->append($token);
        $newLineToken = new Token(TokenType::T_NEWLINE, ' ', 0, 0);
        $subject->append($newLineToken);
        $subject->getNext();
        self::assertSame($subject->peekNext(), $newLineToken);
        self::assertSame($subject->peekNext(), $newLineToken);
    }

    /**
     * @test
     */
    public function peekNextReturnsNullIfThereIsNoNextToken(): void
    {
        $subject = new TokenStream();
        $token = new Token(TokenType::T_BLANK, ' ', 0, 0);
        $subject->append($token);
        $subject->getNext();
        self::assertNull($subject->peekNext());
    }
}
