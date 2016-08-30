<?php
namespace TYPO3\CMS\Beuser\ViewHelpers;

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

use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Displays 'SwitchUser' link with sprite icon to change current backend user to target (non-admin) backendUser
 * @internal
 */
class SwitchUserViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Render link with sprite icon to change current backend user to target
     *
     * @param BackendUser $backendUser Target backendUser to switch active session to
     * @return string
     */
    public function render(BackendUser $backendUser)
    {
        return static::renderStatic(
            [
                'backendUser' => $backendUser
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }
    /**
     * @param array $arguments
     * @param callable $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     *
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $backendUser = $arguments['backendUser'];
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        if ($backendUser->getUid() == $GLOBALS['BE_USER']->user['uid'] || !$backendUser->isActive() || $GLOBALS['BE_USER']->user['ses_backuserid']) {
            return '<span class="btn btn-default disabled">' . $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        }
        $title = LocalizationUtility::translate('switchBackMode', 'beuser');
        return '<a class="btn btn-default" href="' .
            htmlspecialchars(GeneralUtility::linkThisScript(['SwitchUser' => $backendUser->getUid()])) .
            '" target="_top" title="' . htmlspecialchars($title) . '">' .
            $iconFactory->getIcon('actions-system-backend-user-switch', Icon::SIZE_SMALL)->render() . '</a>';
    }
}
