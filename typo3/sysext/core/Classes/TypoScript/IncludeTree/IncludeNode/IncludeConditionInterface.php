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

/**
 * Source streams that contain conditions are split smaller parts
 * and each condition creates a Condition node.
 *
 * This interface is implemented by all conditions nodes. It allows
 * "parking" the main condition token to be evaluated during AST building.
 *
 * @internal: Internal tree structure.
 */
interface IncludeConditionInterface
{
    /**
     * Set and get the condition token: "[foo = bar]"
     */
    public function setConditionToken(Token $token): void;
    public function getConditionToken(): Token;

    /**
     * Conditions may use constants: "[foo = {$bar}]". This getter/setter
     * allows storing the original condition token string.
     * This is set in backend only in case a constant substitution has taken
     * place. Otherwise, the "vanilla" condition token is identical,
     * getOriginalConditionToken() returns null and the condition token should
     * be fetched from getConditionToken().
     */
    public function setOriginalConditionToken(Token $token): void;
    public function getOriginalConditionToken(): ?Token;

    /**
     * True for ConditionElseInclude: The [ELSE] node of a condition.
     */
    public function isConditionNegated(): bool;

    /**
     * When a condition is evaluated, this is set to true of false
     * depending on the condition result.
     */
    public function setConditionVerdict(bool $verdict): void;
    public function getConditionVerdict(): bool;
}
