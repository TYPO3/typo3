<?php
namespace TYPO3\CMS\Extbase\Persistence\Generic\Qom;

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

/**
 * Evaluates to the lower-case string value (or values, if multi-valued) of
 * operand.
 *
 * If operand does not evaluate to a string value, its value is first converted
 * to a string.
 *
 * If operand evaluates to null, the LowerCase operand also evaluates to null.
 */
class LowerCase implements LowerCaseInterface
{
    /**
     * @var PropertyValueInterface
     */
    protected $operand;

    /**
     * Constructs this LowerCase instance
     *
     * @param PropertyValueInterface $operand
     */
    public function __construct(PropertyValueInterface $operand)
    {
        $this->operand = $operand;
    }

    /**
     * Gets the operand whose value is converted to a lower-case string.
     *
     * @return PropertyValueInterface the operand; non-null
     */
    public function getOperand()
    {
        return $this->operand;
    }

    /**
     * Gets the name of the selector against which to evaluate this operand.
     *
     * @return string the selector name; non-null
     */
    public function getSelectorName()
    {
        return $this->operand->getSelectorName();
    }

    /**
     * Gets the name of the property.
     *
     * @return string the property name; non-null
     */
    public function getPropertyName()
    {
        return 'LOWER' . $this->operand->getPropertyName();
    }
}
