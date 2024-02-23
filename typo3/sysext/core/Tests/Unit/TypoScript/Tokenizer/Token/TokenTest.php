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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TokenTest extends UnitTestCase
{
    #[Test]
    public function getTypeReturnsType(): void
    {
        self::assertSame(TokenType::T_VALUE, (new Token(TokenType::T_VALUE, '', 0, 0))->getType());
    }

    #[Test]
    public function getValueReturnsValue(): void
    {
        self::assertSame('foo', (new Token(TokenType::T_VALUE, 'foo', 0, 0))->getValue());
    }

    #[Test]
    public function getLineReturnsLine(): void
    {
        self::assertSame(42, (new Token(TokenType::T_VALUE, '', 42, 0))->getLine());
    }

    #[Test]
    public function getColumnReturnsColumn(): void
    {
        self::assertSame(42, (new Token(TokenType::T_VALUE, '', 0, 42))->getColumn());
    }

    #[Test]
    public function stringCastReturnsValue(): void
    {
        self::assertSame('foo', (string)(new Token(TokenType::T_VALUE, 'foo', 0, 0)));
    }
}
