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

namespace TYPO3\CMS\Backend\ViewHelpers;

use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper to render the avatar markup (including the `<img>` tag) for a given backend user.
 * If the given backend user hasn't added a custom avatar yet, a default one is used.
 *
 * ```
 *    <be:avatar backendUser="{user.uid}" size="32" showIcon="true" />
 * ```
 *
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-backend-avatar
 */
final class AvatarViewHelper extends AbstractViewHelper
{
    /**
     * As this ViewHelper renders HTML, the output must not be escaped.
     *
     * @var bool
     */
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('backendUser', 'int', 'uid of the backend user', false, 0);
        $this->registerArgument('size', 'int', 'width and height of the image', false, 32);
        $this->registerArgument('showIcon', 'bool', 'show the record icon as well', false, false);
    }

    /**
     * Resolve user avatar from a given backend user id.
     */
    public function render(): string
    {
        if ($this->arguments['backendUser'] > 0) {
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
            $queryBuilder->getRestrictions()->removeAll();
            $backendUser = $queryBuilder
                ->select('*')
                ->from('be_users')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($this->arguments['backendUser'], Connection::PARAM_INT)
                    )
                )
                ->executeQuery()
                ->fetchAssociative();
        } else {
            $backendUser = $GLOBALS['BE_USER']->user;
        }
        if ($backendUser === false) {
            // no BE user can be retrieved from DB, probably deleted
            return '';
        }
        $avatar = GeneralUtility::makeInstance(Avatar::class);
        return $avatar->render($backendUser, $this->arguments['size'], $this->arguments['showIcon']);
    }
}
