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

namespace TYPO3\CMS\Redirects\EventListener;

use TYPO3\CMS\Backend\Form\Event\ModifyEditFormUserAccessEvent;
use TYPO3\CMS\Redirects\Security\RedirectPermissionGuard;

/**
 * @internal
 */
final class RedirectEditPermissionGuard
{
    public function __construct(
        private readonly RedirectPermissionGuard $redirectPermissionGuard,
    ) {}

    public function __invoke(ModifyEditFormUserAccessEvent $event): void
    {
        if ($event->getTableName() !== 'sys_redirect' || $event->getCommand() === 'new') {
            return;
        }

        if (!$this->redirectPermissionGuard->isAllowedRedirect($event->getDatabaseRow())) {
            $event->denyUserAccess();
        }
    }
}
