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

namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\IncludeTree\IncludeNode;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\ConditionIncludeTyposcriptInclude;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @deprecated: Remove together with related code in v14, search for keyword INCLUDE_TYPOSCRIPT
 */
final class ConditionIncludeTyposcriptIncludeTest extends UnitTestCase
{
    #[Test]
    public function setConditionTokenThrowsIfTokenIsNotOfTypeValue(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1655977210);
        (new ConditionIncludeTyposcriptInclude())->setConditionToken(new Token(TokenType::T_BLANK, ''));
    }

    #[Test]
    public function setOriginalConditionTokenThrowsIfTokenIsNotOfTypeValue(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1655977211);
        (new ConditionIncludeTyposcriptInclude())->setOriginalConditionToken(new Token(TokenType::T_BLANK, ''));
    }
}
