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
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
interface BindVariableValueInterface extends \TYPO3\CMS\Extbase\Persistence\Generic\Qom\StaticOperandInterface
{
    /**
     * Gets the name of the bind variable.
     *
     * @return string the bind variable name; non-null
     */
    public function getBindVariableName();
}
