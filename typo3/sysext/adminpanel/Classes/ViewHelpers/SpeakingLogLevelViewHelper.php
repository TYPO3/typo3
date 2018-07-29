<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\ViewHelpers;

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

use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Get speaking log level from constant
 *
 * @internal
 */
class SpeakingLogLevelViewHelper extends AbstractViewHelper
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
    public function initializeArguments(): void
    {
        $this->registerArgument('level', 'int', 'Log Level', true);
    }

    /**
     * Resolve user name from backend user id.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string Username or an empty string if there is no user with that UID
     */
    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ): string {
        return LogLevel::getName($arguments['level']);
    }
}
