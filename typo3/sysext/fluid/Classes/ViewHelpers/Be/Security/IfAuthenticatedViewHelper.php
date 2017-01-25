<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Security;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */
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
 *
 * @api
 */
class IfAuthenticatedViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper implements \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface
{
    /**
     * This method decides if the condition is TRUE or FALSE. It can be overriden in extending viewhelpers to adjust functionality.
     *
     * @param array $arguments ViewHelper arguments to evaluate the condition for this ViewHelper, allows for flexiblity in overriding this method.
     * @return bool
     */
    protected static function evaluateCondition($arguments = null)
    {
        return isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER']->user['uid'] > 0;
    }
}
