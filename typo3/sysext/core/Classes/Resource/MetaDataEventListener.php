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

namespace TYPO3\CMS\Core\Resource;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Event\AfterFileMetaDataUpdatedEvent;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal Marked as internal for now, methods in this class may change any time.
 */
final class MetaDataEventListener
{
    private const TABLE_NAME = 'sys_file_metadata';

    /**
     * @param AfterFileMetaDataUpdatedEvent $event
     */
    public function afterFileMetaDataUpdated(AfterFileMetaDataUpdatedEvent $event): void
    {
        $record = $event->getRecord();

        if (($record['width'] ?? 0) <= 0 || ($record['height'] ?? 0) <= 0) {
            return;
        }

        $metaData = [
            'width' => (int)$record['width'],
            'height' => (int)$record['height'],
        ];

        // Update translated meta data records
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(self::TABLE_NAME);
        $connection->update(
            self::TABLE_NAME,
            $metaData,
            [
                'file' => $event->getFileUid(),
                'l10n_parent' => $event->getMetaDataUid(),
            ]
        );
    }
}
