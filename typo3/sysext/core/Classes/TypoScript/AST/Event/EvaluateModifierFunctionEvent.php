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

namespace TYPO3\CMS\Core\TypoScript\AST\Event;

/**
 * Listeners to this event are able to implement own ":=" TypoScript modifier functions, example:
 *
 * foo = myOriginalValue
 * foo := myNewFunction(myFunctionArgument)
 *
 * Listeners should take care function names can not overlap with function names
 * from other extensions and should thus namespace, example naming: "extNewsSortFunction()"
 */
final class EvaluateModifierFunctionEvent
{
    private ?string $value = null;

    public function __construct(
        private readonly string $functionName,
        private readonly ?string $functionArgument,
        private readonly ?string $originalValue,
    ) {}

    /**
     * The function name, for example "extNewsSortFunction" when using "foo := extNewsSortFunction()"
     */
    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    /**
     * Optional function argument, for example "myArgument" when using "foo := extNewsSortFunction(myArgument)"
     */
    public function getFunctionArgument(): ?string
    {
        return $this->functionArgument;
    }

    /**
     * Original / current value, for example "fooValue" when using:
     * foo = fooValue
     * foo := extNewsSortFunction(myArgument)
     */
    public function getOriginalValue(): ?string
    {
        return $this->originalValue;
    }

    /**
     * Set the updated value calculated by a listener.
     * Note you can not set to null to "unset", since getValue() falls back to
     * originalValue in this case. Set to empty string instead for this edge case.
     */
    public function setValue(string $value): void
    {
        $this->value = $value;
    }

    /**
     * Used by AstBuilder to fetch the updated value, falls back to given original value.
     * Can be used by Listeners to see if a previous listener changed the value already
     * by comparing with getOriginalValue().
     */
    public function getValue(): ?string
    {
        return $this->value;
    }
}
