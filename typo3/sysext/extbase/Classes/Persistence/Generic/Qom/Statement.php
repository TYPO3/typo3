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
 * A statement acting as a constraint.
 */
class Statement implements ConstraintInterface
{
    /**
     * @var string|\TYPO3\CMS\Core\Database\PreparedStatement
     */
    protected $statement;

    /**
     * @var array
     */
    protected $boundVariables = [];

    /**
     * Constructs the Statement instance
     *
     * @param string|\TYPO3\CMS\Core\Database\PreparedStatement $statement The statement as sql string or TYPO3\CMS\Core\Database\PreparedStatement
     * @param array $boundVariables An array of variables to bind to the statement, only to be used with preparedStatement
     */
    public function __construct($statement, array $boundVariables = [])
    {
        $this->statement = $statement;
        $this->boundVariables = $boundVariables;
    }

    /**
     * Gets the statement.
     *
     * @return string|\TYPO3\CMS\Core\Database\PreparedStatement the statement; non-null
     */
    public function getStatement()
    {
        return $this->statement;
    }

    /**
     * Gets the bound variables
     *
     * @return array $boundVariables
     */
    public function getBoundVariables()
    {
        return $this->boundVariables;
    }

    /**
     * Fills an array with the names of all bound variables in the constraints
     *
     * @param array &$boundVariables
     * @return void
     */
    public function collectBoundVariableNames(&$boundVariables)
    {
    }
}
