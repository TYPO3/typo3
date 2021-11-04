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
    private $tableName = 'sys_file_metadata';

    public function afterFileMetaDataUpdated(AfterFileMetaDataUpdatedEvent $event): void
    {
        $record = $event->getRecord();

        if ((int)$record['width'] <= 0 || (int)$record['height'] <= 0) {
            return;
        }

        $metaData = [
            'width' => (int)$record['width'],
            'height' => (int)$record['height'],
        ];

        // Fetch translated meta data records
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableName);
        $translations = $connection->select(
            ['uid'],
            $this->tableName,
            ['file' => $event->getFileUid(), 'l10n_parent' => $event->getMetaDataUid()]
        )->fetchFirstColumn();

        if (empty($translations)) {
            return;
        }

        // Update width and height of all translations
        foreach ($translations as $uid) {
            if ((int)$uid > 0) {
                $connection->update(
                    $this->tableName,
                    $metaData,
                    [
                        'uid' => (int)$uid,
                    ]
                );
            }
        }
    }
}
