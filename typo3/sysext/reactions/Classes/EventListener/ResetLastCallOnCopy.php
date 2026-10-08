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

namespace TYPO3\CMS\Reactions\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\DataHandling\Event\BeforeRemoveNonCopyableFieldsEvent;

/**
 * A copy of a reaction has not been called yet.
 *
 * @internal
 */
#[AsEventListener('typo3/cms-reactions/reset-last-call-on-copy')]
final readonly class ResetLastCallOnCopy
{
    public function __invoke(BeforeRemoveNonCopyableFieldsEvent $event): void
    {
        if ($event->getTable() !== 'sys_reaction') {
            return;
        }
        $event->setNonCopyableFields([
            ...$event->getNonCopyableFields(),
            'last_called',
            'last_status',
            'last_failure',
        ]);
    }
}
