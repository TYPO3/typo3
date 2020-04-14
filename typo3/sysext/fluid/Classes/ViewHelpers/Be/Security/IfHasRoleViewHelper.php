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

namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Security;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * This ViewHelper implements an ifHasRole/else condition for backend users
 * and backend groups.
 *
 * Role refers to backend user groups. The :html:`role` attribute can either be
 * the title of a group, or the uid.
 *
 * Examples
 * ========
 *
 * Basic usage::
 *
 *    <f:be.security.ifHasRole role="Administrator">
 *       This is being shown in case the current BE user belongs to a BE usergroup (aka role) titled "Administrator" (case sensitive)
 *    </f:be.security.ifHasRole>
 *
 * Everything inside the :html:`<f:ifHasRole>` tag is being displayed if the
 * logged in backend user belongs to the specified backend group.
 *
 * Using the usergroup uid as role identifier::
 *
 *    <f:be.security.ifHasRole role="1">
 *       This is being shown in case the current BE user belongs to a BE usergroup (aka role) with the uid "1"
 *    </f:be.security.ifHasRole>
 *
 * Everything inside the :html:`<f:ifHasRole>` tag is being displayed if the
 * logged in backend user belongs to the specified backend group.
 *
 * IfRole / then / else::
 *
 *    <f:be.security.ifHasRole role="Administrator">
 *       <f:then>
 *          This is being shown in case you have the role.
 *       </f:then>
 *       <f:else>
 *          This is being displayed in case you do not have the role.
 *       </f:else>
 *    </f:be.security.ifHasRole>
 *
 * Everything inside the :html:`<f:then></f:then>` tag is displayed if the
 * logged in backend user belongs to the specified backend group.
 * Otherwise, everything inside the :html:`<f:else></f:else>` tag is displayed.
 */
class IfHasRoleViewHelper extends AbstractConditionViewHelper
{
    /**
     * Initializes the "role" argument.
     * Renders <f:then> child if the current logged in BE user belongs to the specified role (aka usergroup)
     * otherwise renders <f:else> child.
     */
    public function initializeArguments()
    {
        parent::initializeArguments();

        $this->registerArgument('role', 'string', 'The usergroup (either the usergroup uid or its title).');
    }

    /**
     * This method decides if the condition is TRUE or FALSE. It can be overridden in extending viewhelpers to adjust functionality.
     *
     * @param array $arguments ViewHelper arguments to evaluate the condition for this ViewHelper, allows for flexibility in overriding this method.
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
