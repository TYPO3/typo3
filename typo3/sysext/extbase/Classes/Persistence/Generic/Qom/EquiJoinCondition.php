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

namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

/**
 * Tests whether the value of a property in a first selector is equal to the value of a
 * property in a second selector.
 * A node-tuple satisfies the constraint only if: the selector1Name node has a property named property1Name, and
 * the selector2Name node has a property named property2Name, and
 * the value of property property1Name is equal to the value of property property2Name.
 *
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
final readonly class EquiJoinCondition implements EquiJoinConditionInterface
{
    public function __construct(
        protected string $selector1Name,
        protected string $property1Name,
        protected string $selector2Name,
        protected string $property2Name
    ) {
        // @todo Test for selector1Name = selector2Name -> exception
    }

    public function getSelector1Name(): string
    {
        return $this->selector1Name;
    }

    public function getProperty1Name(): string
    {
        return $this->property1Name;
    }

    public function getSelector2Name(): string
    {
        return $this->selector2Name;
    }

    public function getProperty2Name(): string
    {
        return $this->property2Name;
    }

    public function getChildSelectorName(): string
    {
        return '';
    }

    public function getParentSelectorName(): string
    {
        return '';
    }
}
