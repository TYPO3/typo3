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

use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\IdentifierTokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class IdentifierTokenStreamTest extends UnitTestCase
{
    /**
     * @test
     */
    public function appendThrowsExceptionIfTokenIsNotOfTypeIdentifier(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1655138907);
        $token = new Token(TokenType::T_NONE, '', 0, 0);
        (new IdentifierTokenStream())->append($token);
    }

    /**
     * @test
     */
    public function canAppendIdentifierToken(): void
    {
        $token = new Token(TokenType::T_IDENTIFIER, '', 0, 0);
        (new IdentifierTokenStream())->append($token);
    }

    /**
     * @test
     */
    public function nonRelativeStreamIsNotRelative(): void
    {
        self::assertFalse((new IdentifierTokenStream())->isRelative());
    }

    /**
     * @test
     */
    public function relativeStreamIsRelative(): void
    {
        $tokenStream = (new IdentifierTokenStream());
        $tokenStream->setRelative();
        self::assertTrue($tokenStream->isRelative());
    }
}
