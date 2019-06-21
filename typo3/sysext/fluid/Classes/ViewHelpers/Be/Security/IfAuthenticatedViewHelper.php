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
 * This ViewHelper implements an ifAuthenticated/else condition for backend
 * users and backend groups.
 *
 * Examples
 * ========
 *
 * Basic usage::
 *
 *    <f:be.security.ifAuthenticated>
 *       This is being shown whenever a BE user is logged in
 *    </f:be.security.ifAuthenticated>
 *
 * Everything inside the :html:`<f:be.ifAuthenticated>` tag is being displayed
 * if the user is authenticated with any backend user account.
 *
 * IfAuthenticated / then / else::
 *
 *    <f:be.security.ifAuthenticated>
 *       <f:then>
 *          This is being shown in case you have access.
 *       </f:then>
 *       <f:else>
 *          This is being displayed in case you do not have access.
 *       </f:else>
 *    </f:be.security.ifAuthenticated>
 *
 * Everything inside the :html:`<f:then></f:then>` is displayed the backend user is logged in.
 * :html:`<f:else></f:else>` is displayed if no backend user is logged in.
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
