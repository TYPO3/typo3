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

namespace TYPO3\CMS\Frontend\Content;

use TYPO3\CMS\Core\Domain\Persistence\RecordIdentityMap;
use TYPO3\CMS\Core\Domain\RecordFactory;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Executes a SQL query, and retrieves TCA-based records for Frontend rendering.
 */
readonly class RecordCollector
{
    public function __construct(
        protected RecordFactory $recordFactory
    ) {}

    public function collect(
        string $table,
        array $select,
        ContentSlideMode $slideMode,
        ContentObjectRenderer $contentObjectRenderer,
        ?RecordIdentityMap $recordIdentityMap = null,
    ): array {
        $slideCollectReverse = false;
        $collect = false;
        switch ($slideMode) {
            case ContentSlideMode::Slide:
                $slide = true;
                break;
            case ContentSlideMode::Collect:
                $slide = true;
                $collect = true;
                break;
            case ContentSlideMode::CollectReverse:
                $slide = true;
                $collect = true;
                $slideCollectReverse = true;
                break;
            default:
                $slide = false;
        }
        $again = false;
        $totalRecords = [];

        do {
            $recordsOnPid = $contentObjectRenderer->getRecords($table, $select);
            $recordsOnPid = array_map(
                fn(array $record): RecordInterface => $this->recordFactory->createResolvedRecordFromDatabaseRow($table, $record, null, $recordIdentityMap),
                $recordsOnPid
            );

            if ($slideCollectReverse) {
                $totalRecords = array_merge($totalRecords, $recordsOnPid);
            } else {
                $totalRecords = array_merge($recordsOnPid, $totalRecords);
            }
            if ($slide) {
                $select['pidInList'] = $contentObjectRenderer->getSlidePids($select['pidInList'] ?? '', $select['pidInList.'] ?? []);
                if (isset($select['pidInList.'])) {
                    unset($select['pidInList.']);
                }
                $again = $select['pidInList'] !== '';
            }
        } while ($again && $slide && ($recordsOnPid === [] || $collect));
        return $totalRecords;
    }
}
