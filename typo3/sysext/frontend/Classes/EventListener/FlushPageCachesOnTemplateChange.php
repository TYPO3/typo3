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

namespace TYPO3\CMS\Frontend\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\Event\BeforeCacheFlushEvent;

/**
 * A changed TypoScript record affects the pages below its own page, but also
 * the pages of every other template that includes it via "basedOn", so all
 * page caches are flushed.
 *
 * @internal
 */
final readonly class FlushPageCachesOnTemplateChange
{
    #[AsEventListener('typo3/cms-frontend/flush-page-caches-on-template-change')]
    public function __invoke(BeforeCacheFlushEvent $event): void
    {
        if ($event->plan->hasRecordOfTable('sys_template')) {
            $event->plan->addGroup('pages');
        }
    }
}
