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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode;

use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;

/**
 * Base implementation of condition nodes.
 *
 * @internal: Internal tree structure.
 */
abstract class AbstractConditionInclude extends AbstractInclude implements IncludeConditionInterface
{
    protected Token $conditionValueToken;
    protected ?Token $originalConditionValueToken = null;
    protected bool $verdict;

    /**
     * Add the condition token to cache when serialized. See __serialize() of AbstractInclude.
     */
    protected function serialize(): array
    {
        $result = parent::serialize();
        $result['conditionValueToken'] = $this->conditionValueToken;
        return $result;
    }

    public function setConditionToken(Token $token): void
    {
        if ($token->getType() !== TokenType::T_VALUE) {
            throw new \LogicException('Token must be of type T_VALUE', 1655977210);
        }
        $this->conditionValueToken = $token;
    }

    public function getConditionToken(): Token
    {
        return $this->conditionValueToken;
    }

    public function setOriginalConditionToken(Token $token): void
    {
        if ($token->getType() !== TokenType::T_VALUE) {
            throw new \LogicException('Token must be of type T_VALUE', 1655977211);
        }
        $this->originalConditionValueToken = $token;
    }

    public function getOriginalConditionToken(): ?Token
    {
        return $this->originalConditionValueToken;
    }

    public function isConditionNegated(): bool
    {
        return false;
    }

    public function setConditionVerdict(bool $verdict): void
    {
        $this->verdict = $verdict;
    }

    public function getConditionVerdict(): bool
    {
        return $this->verdict;
    }
}
