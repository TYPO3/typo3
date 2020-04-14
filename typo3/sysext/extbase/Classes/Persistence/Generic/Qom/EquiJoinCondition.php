<?php

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
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class EquiJoinCondition implements EquiJoinConditionInterface
{
    /**
     * @var string
     */
    protected $selector1Name;

    /**
     * @var string
     */
    protected $property1Name;

    /**
     * @var string
     */
    protected $selector2Name;

    /**
     * @var string
     */
    protected $property2Name;

    /**
     * Constructs this EquiJoinCondition instance
     *
     * @param string $selector1Name the name of the first selector; non-null
     * @param string $property1Name the property name in the first selector; non-null
     * @param string $selector2Name the name of the second selector; non-null
     * @param string $property2Name the property name in the second selector; non-null
     */
    public function __construct($selector1Name, $property1Name, $selector2Name, $property2Name)
    {
        // @todo Test for selector1Name = selector2Name -> exception
        $this->selector1Name = $selector1Name;
        $this->property1Name = $property1Name;
        $this->selector2Name = $selector2Name;
        $this->property2Name = $property2Name;
    }

    /**
     * Gets the name of the first selector.
     *
     * @return string the selector name; non-null
     */
    public function getSelector1Name()
    {
        return $this->selector1Name;
    }

    /**
     * Gets the name of the first property.
     *
     * @return string the property name; non-null
     */
    public function getProperty1Name()
    {
        return $this->property1Name;
    }

    /**
     * Gets the name of the second selector.
     *
     * @return string the selector name; non-null
     */
    public function getSelector2Name()
    {
        return $this->selector2Name;
    }

    /**
     * Gets the name of the second property.
     *
     * @return string the property name; non-null
     */
    public function getProperty2Name()
    {
        return $this->property2Name;
    }

    /**
     * Gets the name of the child selector.
     *
     * @return string the selector name; non-null
     */
    public function getChildSelectorName()
    {
        return '';
    }

    /**
     * Gets the name of the parent selector.
     *
     * @return string the selector name; non-null
     */
    public function getParentSelectorName()
    {
        return '';
    }
}
