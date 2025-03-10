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

namespace TYPO3\CMS\Fluid\ViewHelpers\Security;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * ViewHelper implementing an ifHasRole/else condition for frontend groups.
 *
 * ```
 *   <f:security.ifHasRole role="Administrator">
 *        <f:then>
 *           This is being shown in case you have the role.
 *        </f:then>
 *        <f:else>
 *           This is being displayed in case you do not have the role.
 *        </f:else>
 *   </f:security.ifHasRole>
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-security-ifhasrole
 */
final class IfHasRoleViewHelper extends AbstractConditionViewHelper
{
    /**
     * Initializes the "role" argument.
     * Renders <f:then> child if the current logged in FE user belongs to the specified role (aka usergroup)
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
