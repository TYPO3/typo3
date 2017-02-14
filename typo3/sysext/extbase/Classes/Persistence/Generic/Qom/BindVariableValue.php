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
 * Evaluates to the value of a bind variable.
 */
class BindVariableValue implements \TYPO3\CMS\Extbase\Persistence\Generic\Qom\BindVariableValueInterface
{
    /**
     * @var string
     */
    protected $variableName;

    /**
     * Constructs this BindVariableValue instance
     *
     * @param string $variableName
     */
    public function __construct($variableName)
    {
        $this->variableName = $variableName;
    }

    /**
     * Fills an array with the names of all bound variables in the operand
     *
     * @param array &$boundVariables
     */
    public function collectBoundVariableNames(&$boundVariables)
    {
        $boundVariables[$this->variableName] = null;
    }

    /**
     * Gets the name of the bind variable.
     *
     * @return string the bind variable name; non-null
     */
    public function getBindVariableName()
    {
        return $this->variableName;
    }
}
