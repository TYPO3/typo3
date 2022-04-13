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

namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\Tokenizer\Line;

use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\IdentifierFunctionLine;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\IdentifierTokenStream;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class IdentifierFunctionLineTest extends UnitTestCase
{
    /**
     * @test
     */
    public function setIdentifierTokenStreamThrowsIfStreamIsEmpty(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1655825120);
        (new IdentifierFunctionLine())->setIdentifierTokenStream(new IdentifierTokenStream());
    }

    /**
     * @test
     */
    public function setFunctionNameTokenThrowsIfTokenIsNotOfTypeFunction(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1655825121);
        (new IdentifierFunctionLine())->setFunctionNameToken(new Token(TokenType::T_BLANK, ''));
    }

    /**
     * @test
     */
    public function setFunctionValueTokenThrowsIfTokenIsNotOfTypeValue(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1655825122);
        (new IdentifierFunctionLine())->setFunctionValueToken(new Token(TokenType::T_BLANK, ''));
    }
}
