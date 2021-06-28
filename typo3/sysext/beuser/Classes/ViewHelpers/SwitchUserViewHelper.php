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

namespace TYPO3\CMS\Beuser\ViewHelpers;

use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Displays 'SwitchUser' button to change current backend user to target backend user
 * @internal
 */
class SwitchUserViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * Initializes the arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('backendUser', BackendUser::class, 'Target backendUser to switch active session to', true);
    }

    /**
     * Render link with sprite icon to change current backend user to target
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $targetUser = $arguments['backendUser'];
        $currentUser = self::getBackendUserAuthentication();
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        if ((int)$targetUser->getUid() === (int)($currentUser->user[$currentUser->userid_column] ?? 0)
            || !$targetUser->isActive()
            || !$currentUser->isAdmin()
            || $currentUser->getOriginalUserIdWhenInSwitchUserMode() !== null
        ) {
            return '<span class="btn btn-default disabled">' . $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        }

        return '
            <typo3-backend-switch-user targetUser="' . htmlspecialchars((string)$targetUser->getUid()) . '">
                <button type="button" class="btn btn-default" title="' . htmlspecialchars(LocalizationUtility::translate('switchBackMode', 'beuser') ?? '') . '">'
                    . $iconFactory->getIcon('actions-system-backend-user-switch', Icon::SIZE_SMALL)->render() .
                '</button>
            </typo3-switch-user-button>';
    }

    protected static function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
