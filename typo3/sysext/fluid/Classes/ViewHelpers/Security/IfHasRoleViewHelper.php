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

namespace TYPO3\CMS\Fluid\ViewHelpers\Security;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * This ViewHelper implements an ifHasRole/else condition for frontend groups.
 *
 * Examples
 * ========
 *
 * Basic usage
 * -----------
 *
 * ::
 *
 *    <f:security.ifHasRole role="Administrator">
 *        This is being shown in case the current FE user belongs to a FE usergroup (aka role) titled "Administrator" (case sensitive)
 *    </f:security.ifHasRole>
 *
 * Everything inside the :html:`<f:security.ifHasRole>` tag is being displayed if the
 * logged in frontend user belongs to the specified frontend user group.
 * Comparison is done by comparing to title of the user groups.
 *
 * Using the usergroup uid as role identifier
 * ------------------------------------------
 *
 * ::
 *
 *    <f:security.ifHasRole role="1">
 *       This is being shown in case the current FE user belongs to a FE usergroup (aka role) with the uid "1"
 *    </f:security.ifHasRole>
 *
 * Everything inside the :html:`<f:security.ifHasRole>` tag is being displayed if the
 * logged in frontend user belongs to the specified role. Comparison is done
 * using the ``uid`` of frontend user groups.
 *
 * IfRole / then / else
 * --------------------
 *
 * ::
 *
 *    <f:security.ifHasRole role="Administrator">
 *       <f:then>
 *          This is being shown in case you have the role.
 *       </f:then>
 *       <f:else>
 *          This is being displayed in case you do not have the role.
 *       </f:else>
 *    </f:security.ifHasRole>
 *
 * Everything inside the :html:`<f:then></f:then>` tag is displayed if the logged in FE user belongs to the specified role.
 * Otherwise, everything inside the :html:`<f:else></f:else>` tag is displayed.
 */
class IfHasRoleViewHelper extends AbstractConditionViewHelper
{
    /**
     * Initializes the "role" argument.
     * Renders <f:then> child if the current logged in FE user belongs to the specified role (aka usergroup)
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
        /** @var UserAspect $userAspect */
        $userAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
        if (!$userAspect->isLoggedIn()) {
            return false;
        }
        if (is_numeric($role)) {
            $groupIds = $userAspect->getGroupIds();
            return in_array((int)$role, $groupIds, true);
        }
        return in_array($role, $userAspect->getGroupNames(), true);
    }
}
