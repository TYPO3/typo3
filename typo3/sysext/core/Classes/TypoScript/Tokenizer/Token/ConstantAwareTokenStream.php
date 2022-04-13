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
    public function setFlatConstants(?array $flatConstants = [])
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
                $tokenValue = ltrim(ltrim(rtrim($token->getValue(), '}'), '{'), '$');
                if (is_array($this->flatConstants) && array_key_exists($tokenValue, $this->flatConstants)) {
                    $source .= $this->flatConstants[$tokenValue];
                    continue;
                }
            }
            $source .= $token;
        }
        $this->reset();
        return $source;
    }
}
