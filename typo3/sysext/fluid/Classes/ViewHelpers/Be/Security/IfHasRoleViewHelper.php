<?php

declare(strict_types=1);

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

use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * ViewHelper implementing an ifHasRole/else condition for backend users
 * and backend groups ("Role").
 *
 * ```
 *   <f:be.security.ifHasRole role="Administrator">
 *        <f:then>
 *           This is being shown in case you have the role.
 *        </f:then>
 *        <f:else>
 *           This is being displayed in case you do not have the role.
 *        </f:else>
 *   </f:be.security.ifHasRole>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-be-security-ifhasrole
 */
final class IfHasRoleViewHelper extends AbstractConditionViewHelper
{
    /**
     * Initializes the "role" argument.
     * Renders <f:then> child if the current logged in BE user belongs to the specified role (aka usergroup)
     * otherwise renders <f:else> child.
     */
    public function initializeArguments(): void
    {
        parent::initializeArguments();
        $this->registerArgument('role', 'string', 'The usergroup (either the usergroup uid or its title).');
    }

    public static function verdict(array $arguments, RenderingContextInterface $renderingContext): bool
    {
        $role = $arguments['role'];
        if (!is_array($GLOBALS['BE_USER']->userGroups) || $arguments['role'] === null) {
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
