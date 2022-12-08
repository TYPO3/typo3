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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor;

use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeConditionInterface;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\IncludeInterface;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\Token;
use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenType;

/**
 * Handle constants within (TS setup) conditions:
 * When a conditional include is like this: '["{$foo.bar}" == "4711"]', this visitor looks
 * up 'foo.bar in given (flattened) constants and substitutes it with the constant value.
 * The 'include' object then contains the substituted condition token for 'getConditionToken()',
 * while the original token without the substitution is parked in 'getOriginalConditionToken()'.
 * The latter is done to have the original token available in the backend to show, it is irrelevant in frontend.
 *
 * @internal: Internal tree structure.
 */
final class IncludeTreeSetupConditionConstantSubstitutionVisitor implements IncludeTreeVisitorInterface
{
    /**
     * @var array<string, string>
     */
    private array $flattenedConstants;

    /**
     * Must be set when adding this visitor, to an empty array at least.
     * Will fatal otherwise, and that's fine, since if not setting this,
     * this visitor is useless and shouldn't be added at all.
     *
     * @param array<string, string> $flattenedConstants
     */
    public function setFlattenedConstants(array $flattenedConstants): void
    {
        $this->flattenedConstants = $flattenedConstants;
    }

    /**
     * Do the magic, see tests for details.
     * Implementation within 'visitBeforeChilden()' since this allows running *both* this
     * visitor first, and then TreeVisitorConditionMatcher directly afterwards in the same
     * traverser cycle!
     */
    public function visitBeforeChildren(IncludeInterface $include, int $currentDepth): void
    {
        if (!$include instanceof IncludeConditionInterface) {
            return;
        }
        $conditionToken = $include->getConditionToken();
        $conditionValue = $conditionToken->getValue();
        $flattenedConstants = $this->flattenedConstants;
        $hadSubstitution = false;
        $newConditionValue = preg_replace_callback(
            '/{\$(.[^}]*)}/',
            static function ($match) use ($flattenedConstants, &$hadSubstitution) {
                // Replace {$someConstant} if found, else leave unchanged
                if (array_key_exists($match[1], $flattenedConstants)) {
                    $hadSubstitution = true;
                    return $flattenedConstants[$match[1]];
                }
                return $match[0];
            },
            $conditionValue
        );
        if ($hadSubstitution) {
            $include->setOriginalConditionToken($conditionToken);
            $include->setConditionToken(new Token(TokenType::T_VALUE, $newConditionValue, $conditionToken->getLine(), $conditionToken->getColumn()));
        }
    }

    public function visit(IncludeInterface $include, int $currentDepth): void
    {
        // Noop, just implement interface
    }
}
