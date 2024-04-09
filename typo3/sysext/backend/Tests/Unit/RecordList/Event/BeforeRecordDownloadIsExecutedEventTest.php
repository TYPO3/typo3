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

namespace TYPO3\CMS\Backend\Tests\Unit\RecordList\Event;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\RecordList\Event\BeforeRecordDownloadIsExecutedEvent;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BeforeRecordDownloadIsExecutedEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $request = (new ServerRequest('https://example.com', 'POST'));

        $headerRow = [
            'uid',
            'title',
        ];

        $records = [
            [
                'uid' => 1,
                'title' => 'test',
            ],
            [
                'uid' => 2,
                'title' => 'test',
            ],
        ];

        $table = 'pages';
        $format = 'csv';
        $filename = 'pages_130524-1537.csv';
        $id = 1701;
        $modTSconfig = ['empty'];
        $columnsToRender = ['uid', 'title'];
        $hideTranslations = false;

        $event = new BeforeRecordDownloadIsExecutedEvent(
            $headerRow,
            $records,
            $request,
            $table,
            $format,
            $filename,
            $id,
            $modTSconfig,
            $columnsToRender,
            $hideTranslations,
        );

        self::assertEquals($headerRow, $event->getHeaderRow());
        self::assertEquals($records, $event->getRecords());
        self::assertEquals($request, $event->getRequest());
        self::assertEquals($table, $event->getTable());
        self::assertEquals($format, $event->getFormat());
        self::assertEquals($filename, $event->getFilename());
        self::assertEquals($id, $event->getId());
        self::assertEquals($modTSconfig, $event->getModTSconfig());
        self::assertEquals($columnsToRender, $event->getColumnsToRender());
        self::assertEquals($hideTranslations, $event->isHideTranslations());

        $modifiedHeaderRow = $headerRow;
        $modifiedRecords = $records;

        $modifiedHeaderRow['title'] = 'Modified title';
        $modifiedHeaderRow['additional'] = 'additional';

        $modifiedRecords[0]['title'] .= ' (modified)';
        $modifiedRecords[] = [
            'title' => 'New',
            'uid' => 3,
            'additional' => 'foobarbaz',
        ];

        $event->setHeaderRow($modifiedHeaderRow);
        $event->setRecords($modifiedRecords);

        self::assertEquals($modifiedHeaderRow, $event->getHeaderRow());
        self::assertEquals($modifiedRecords, $event->getRecords());
    }
}
