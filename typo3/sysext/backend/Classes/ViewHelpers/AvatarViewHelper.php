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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Render the avatar img tag for a given backend user
 */
class AvatarViewHelper extends AbstractViewHelper
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
        $this->registerArgument('backendUser', 'int', 'uid of the backend user', false, 0);
        $this->registerArgument('size', 'int', 'width and height of the image', false, 32);
        $this->registerArgument('showIcon', 'bool', 'show the record icon as well', false, false);
    }

    /**
     * Resolve user avatar from a given backend user id.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        if ($arguments['backendUser'] > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
            $queryBuilder->getRestrictions()->removeAll();
            $backendUser = $queryBuilder
                ->select('*')
                ->from('be_users')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($arguments['backendUser'], \PDO::PARAM_INT)
                    )
                )
                ->execute()
                ->fetch();
        } else {
            $backendUser = $GLOBALS['BE_USER']->user;
        }
        if ($backendUser === false) {
            // no BE user can be retrieved from DB, probably deleted
            return '';
        }
        $avatar = GeneralUtility::makeInstance(Avatar::class);
        return $avatar->render($backendUser, $arguments['size'], $arguments['showIcon']);
    }
}
