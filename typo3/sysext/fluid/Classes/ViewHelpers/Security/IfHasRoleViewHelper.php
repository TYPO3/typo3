<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Security;

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
 * This view helper implements an ifHasRole/else condition for FE users/groups.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <f:security.ifHasRole role="Administrator">
 * This is being shown in case the current FE user belongs to a FE usergroup (aka role) titled "Administrator" (case sensitive)
 * </f:security.ifHasRole>
 * </code>
 * <output>
 * Everything inside the <f:ifHasRole> tag is being displayed if the logged in FE user belongs to the specified role.
 * </output>
 *
 * <code title="Using the usergroup uid as role identifier">
 * <f:security.ifHasRole role="1">
 * This is being shown in case the current FE user belongs to a FE usergroup (aka role) with the uid "1"
 * </f:security.ifHasRole>
 * </code>
 * <output>
 * Everything inside the <f:ifHasRole> tag is being displayed if the logged in FE user belongs to the specified role.
 * </output>
 *
 * <code title="IfRole / then / else">
 * <f:security.ifHasRole role="Administrator">
 * <f:then>
 * This is being shown in case you have the role.
 * </f:then>
 * <f:else>
 * This is being displayed in case you do not have the role.
 * </f:else>
 * </f:security.ifHasRole>
 * </code>
 * <output>
 * Everything inside the "then" tag is displayed if the logged in FE user belongs to the specified role.
 * Otherwise, everything inside the "else"-tag is displayed.
 * </output>
 *
 * @api
 */
class IfHasRoleViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper implements \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface
{
    /**
     * Initializes the "role" argument.
     * Renders <f:then> child if the current logged in FE user belongs to the specified role (aka usergroup)
     * otherwise renders <f:else> child.
     *
     * @return void
     */
    public function initializeArguments()
    {
        $this->registerArgument('role', 'string', 'The usergroup (either the usergroup uid or its title).');
    }

    /**
     * This method decides if the condition is TRUE or FALSE. It can be overriden in extending viewhelpers to adjust functionality.
     *
     * @param array $arguments ViewHelper arguments to evaluate the condition for this ViewHelper, allows for flexiblity in overriding this method.
     * @return bool
     */
    protected static function evaluateCondition($arguments = null)
    {
        $role = $arguments['role'];
        if (!isset($GLOBALS['TSFE']) || !$GLOBALS['TSFE']->loginUser) {
            return false;
        }
        if (is_numeric($role)) {
            return is_array($GLOBALS['TSFE']->fe_user->groupData['uid']) && in_array($role, $GLOBALS['TSFE']->fe_user->groupData['uid']);
        } else {
            return is_array($GLOBALS['TSFE']->fe_user->groupData['title']) && in_array($role, $GLOBALS['TSFE']->fe_user->groupData['title']);
        }
    }
}
