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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A list of single T_VALUE, T_NEWLINE and T_CONSTANT tokens. This is only created for
 * LineIdentifierAssignment lines if there is at least one T_CONSTANT token
 * in the assignment that needs to be evaluated when string'ified by the
 * AST-builder.
 *
 * @internal: Internal tokenizer structure.
 */
final class ConstantAwareTokenStream extends AbstractTokenStream
{
    private ?array $flatConstants = null;

    /**
     * Set by the AstBuilder to resolve constant values. Never cached.
     */
    public function setFlatConstants(array $flatConstants): void
    {
        $this->flatConstants = $flatConstants;
    }

    /**
     * Create a source string from given tokens.
     * This resolves T_CONSTANT tokens to their value if they exist in $this->flatConstants.
     */
    public function __toString(): string
    {
        $source = '';
        $this->reset();
        while ($token = $this->getNext()) {
            if ($token->getType() === TokenType::T_CONSTANT) {
                $token = $this->getConstantValue($this->parseConstantExpression($token->getValue())) ?? $token;
            }
            $source .= $token;
        }
        $this->reset();
        return $source;
    }

    private function getConstantValue(?array $constantNames): ?string
    {
        if ($this->flatConstants === null || $constantNames === null) {
            return null;
        }
        foreach ($constantNames as $constantName) {
            $value = $this->flatConstants[$constantName] ?? null;
            if ($value !== null) {
                return (string)$value;
            }
        }
        return null;
    }

    /**
     * Parse constant expression, including null coalescing operator into an
     * array of constant names to look up in order.
     *
     * @todo: The tokenization of this constant expression should ideally be moved
     *        into the TypoScript Tokenizer in order to produce a list of multiple tokens
     *        instead of just a T_CONSTANT for the entire body.
     *        This would allow early static syntax analysis of the construct and maybe
     *        detection of invalid and fallback to T_CONSTANT_INVALID that is treated
     *        like T_VALUE and can be detected. Maybe something like this:
     *          TokenType::T_CONSTANT_START "{"
     *          TokenType::T_CONSTANT_END "}"
     *          TokenType::T_CONSTANT_NAME "$foo.bar"
     *          TokenType::T_CONSTANT_OPERATOR_NULL_COALESCE " ?? "
     *          TokenType::T_CONSTANT_INVALID "{$foo ?? bar}" (missing $ before bar)
     */
    private function parseConstantExpression(string $constantExpression): ?array
    {
        $innerExpression = ltrim(rtrim($constantExpression, '}'), '{');
        $tokenValues = GeneralUtility::trimExplode(' ?? ', $innerExpression, true);
        if ($tokenValues === []) {
            return null;
        }
        $tokenValueNames = [];
        foreach ($tokenValues as $tokenValue) {
            if (!str_starts_with($tokenValue, '$')) {
                return null;
            }
            $tokenValueNames[] = substr($tokenValue, 1);
        }
        return $tokenValueNames;
    }
}
