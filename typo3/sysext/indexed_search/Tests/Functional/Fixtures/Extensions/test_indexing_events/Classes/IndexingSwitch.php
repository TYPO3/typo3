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

namespace TYPO3Tests\TestIndexingEvents;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\IndexedSearch\Event\EnableIndexingEvent;

final class IndexingSwitch
{
    #[AsEventListener('test-indexing-enable')]
    public function enable(EnableIndexingEvent $event): void
    {
        if ($event->getRequest()->getHeaderLine('X-Test-Enable-Indexing') === '1') {
            $event->enableIndexing();
        }
    }

    #[AsEventListener('test-indexing-disable', after: 'test-indexing-enable')]
    public function disable(EnableIndexingEvent $event): void
    {
        if ($event->getRequest()->getHeaderLine('X-Test-Disable-Indexing') === '1') {
            $event->disableIndexing();
        }
    }
}
