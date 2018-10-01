<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Security;

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

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * This view helper implements an ifAuthenticated/else condition for BE users/groups.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <f:be.security.ifAuthenticated>
 * This is being shown whenever a BE user is logged in
 * </f:be.security.ifAuthenticated>
 * </code>
 * <output>
 * Everything inside the <f:be.ifAuthenticated> tag is being displayed if you are authenticated with any BE user account.
 * </output>
 *
 * <code title="IfAuthenticated / then / else">
 * <f:be.security.ifAuthenticated>
 * <f:then>
 * This is being shown in case you have access.
 * </f:then>
 * <f:else>
 * This is being displayed in case you do not have access.
 * </f:else>
 * </f:be.security.ifAuthenticated>
 * </code>
 * <output>
 * Everything inside the "then" tag is displayed if you have access.
 * Otherwise, everything inside the "else"-tag is displayed.
 * </output>
 */
class IfAuthenticatedViewHelper extends AbstractConditionViewHelper
{
    /**
     * This method decides if the condition is TRUE or FALSE. It can be overridden in extending viewhelpers to adjust functionality.
     *
     * @param array $arguments ViewHelper arguments to evaluate the condition for this ViewHelper, allows for flexiblity in overriding this method.
     * @return bool
     */
    protected static function evaluateCondition($arguments = null)
    {
        return isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER']->user['uid'] > 0;
    }
}
