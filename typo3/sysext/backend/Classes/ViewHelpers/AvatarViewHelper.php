<?php
namespace TYPO3\CMS\Backend\ViewHelpers;

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
use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Get avatar for backend user
 */
class AvatarViewHelper extends AbstractViewHelper implements CompilableInterface
{
    /**
     * Resolve user avatar from backend user id.
     *
     * @param int $backendUser Uid of the user
     * @param int $size width and height of the image
     * @param bool $showIcon show the record icon
     * @return string html image tag
     */
    public function render($backendUser = 0, $size = 32, $showIcon = false)
    {
        return static::renderStatic(
            [
                'backendUser' => $backendUser,
                'size' => $size,
                'showIcon' => $showIcon
            ],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * Resolve user avatar from backend user id.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        if ($arguments['backendUser'] > 0) {
            $backendUser = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('*', 'be_users', 'uid=' . (int)$arguments['backendUser']);
        } else {
            $backendUser = $GLOBALS['BE_USER']->user;
        }
        /** @var Avatar $avatar */
        $avatar = GeneralUtility::makeInstance(Avatar::class);
        return $avatar->render($backendUser, $arguments['size'], $arguments['showIcon']);
    }
}
