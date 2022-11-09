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

namespace TYPO3\CMS\Backend\Search\EventListener;

use TYPO3\CMS\Backend\Search\Event\BeforeSearchInDatabaseRecordProviderEvent;

/**
 * Event listener to exclude the "pages" table from the table lookup of the database record provider
 *
 * @internal
 */
final class ExcludePagesFromSearchFieldsLookup
{
    public function __invoke(BeforeSearchInDatabaseRecordProviderEvent $event): void
    {
        $event->ignoreTable('pages');
    }
}
