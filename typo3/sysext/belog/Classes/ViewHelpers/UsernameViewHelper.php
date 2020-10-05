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

namespace TYPO3\CMS\Belog\ViewHelpers;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Get username from backend user id
 * @internal
 */
class UsernameViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * First level cache of user names
     *
     * @var array
     */
    protected static $usernameRuntimeCache = [];

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('uid', 'int', 'Uid of the user', true);
    }

    /**
     * Resolve user name from backend user id.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string Username or an empty string if there is no user with that UID
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $uid = $arguments['uid'];
        if (isset(static::$usernameRuntimeCache[$uid])) {
            return static::$usernameRuntimeCache[$uid];
        }

        $user = BackendUtility::getRecord('be_users', $uid);
        static::$usernameRuntimeCache[$uid] = $user['username'] ?? '';
        return static::$usernameRuntimeCache[$uid];
    }
}
