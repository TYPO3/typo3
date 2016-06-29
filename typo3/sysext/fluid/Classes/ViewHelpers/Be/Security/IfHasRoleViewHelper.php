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
 * This view helper implements an ifHasRole/else condition for BE users/groups.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <f:be.security.ifHasRole role="Administrator">
 * This is being shown in case the current BE user belongs to a BE usergroup (aka role) titled "Administrator" (case sensitive)
 * </f:be.security.ifHasRole>
 * </code>
 * <output>
 * Everything inside the <f:ifHasRole> tag is being displayed if the logged in BE user belongs to the specified role.
 * </output>
 *
 * <code title="Using the usergroup uid as role identifier">
 * <f:be.security.ifHasRole role="1">
 * This is being shown in case the current BE user belongs to a BE usergroup (aka role) with the uid "1"
 * </f:be.security.ifHasRole>
 * </code>
 * <output>
 * Everything inside the <f:ifHasRole> tag is being displayed if the logged in BE user belongs to the specified role.
 * </output>
 *
 * <code title="IfRole / then / else">
 * <f:be.security.ifHasRole role="Administrator">
 * <f:then>
 * This is being shown in case you have the role.
 * </f:then>
 * <f:else>
 * This is being displayed in case you do not have the role.
 * </f:else>
 * </f:be.security.ifHasRole>
 * </code>
 * <output>
 * Everything inside the "then" tag is displayed if the logged in BE user belongs to the specified role.
 * Otherwise, everything inside the "else"-tag is displayed.
 * </output>
 *
 * @api
 */
class IfHasRoleViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper implements \TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface
{
    /**
     * Initializes the "role" argument.
     * Renders <f:then> child if the current logged in BE user belongs to the specified role (aka usergroup)
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
        if (!is_array($GLOBALS['BE_USER']->userGroups)) {
            return false;
        }
        if (is_numeric($role)) {
            foreach ($GLOBALS['BE_USER']->userGroups as $userGroup) {
                if ((int)$userGroup['uid'] === (int)$role) {
                    return true;
                }
            }
        } else {
            foreach ($GLOBALS['BE_USER']->userGroups as $userGroup) {
                if ($userGroup['title'] === $role) {
                    return true;
                }
            }
        }
        return false;
    }
}
