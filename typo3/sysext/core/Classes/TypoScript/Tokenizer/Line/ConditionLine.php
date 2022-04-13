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

use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;

/**
 * A condition line: "[foo == bar]".
 *
 * @internal: Internal tokenizer structure.
 */
final class ConditionLine extends AbstractLine
{
    private Token $valueToken;

    public function setValueToken(Token $token): static
    {
        if ($token->getType() !== TokenType::T_VALUE) {
            throw new \LogicException('Token must be of type T_VALUE', 1655823705);
        }
        $this->valueToken = $token;
        return $this;
    }

    public function getTokenValue(): Token
    {
        return $this->valueToken;
    }
}
