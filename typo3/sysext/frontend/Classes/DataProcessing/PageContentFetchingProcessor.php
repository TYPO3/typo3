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

namespace TYPO3\CMS\Frontend\DataProcessing;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Domain\Persistence\RecordIdentityMap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Content\ContentSlideMode;
use TYPO3\CMS\Frontend\Content\RecordCollector;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;
use TYPO3\CMS\Frontend\Event\AfterContentHasBeenFetchedEvent;

/**
 * All-in-one data processor that loads all tt_content records from the current page
 * layout into the template with a given identifier for each colPos, also respecting
 * slideMode or collect options based on the page layouts content columns.
 *
 * Use "as" for the target variable where the fetched content elements will be provided.
 * If empty, "content" is used.
 *
 * Example TypoScript configuration:
 *
 * page = PAGE
 * page {
 *     10 = PAGEVIEW
 *     10 {
 *         paths.10 = EXT:my_site_package/Resources/Private/Templates/
 *         dataProcessing {
 *             10 = page-content
 *             10.as = myContent
 *         }
 *     }
 * }
 *
 * which fetches all content elements for the current page and provides them as "myContent".
 */
readonly class PageContentFetchingProcessor implements DataProcessorInterface
{
    public function __construct(
        protected RecordCollector $recordCollector,
        protected EventDispatcherInterface $eventDispatcher,
    ) {}

    public function process(
        ContentObjectRenderer $cObj,
        array $contentObjectConfiguration,
        array $processorConfiguration,
        array $processedData
    ): array {
        if (isset($processorConfiguration['if.']) && !$cObj->checkIf($processorConfiguration['if.'])) {
            return $processedData;
        }
        $recordIdentityMap = GeneralUtility::makeInstance(RecordIdentityMap::class);
        $request = $cObj->getRequest();
        $pageInformation = $request->getAttribute('frontend.page.information');
        $pageLayout = $pageInformation->getPageLayout();

        $groupedContent = [];
        $contentAreasWithSlideMode = [];
        $contentAreasWithoutSlideMode = [];
        foreach ($pageLayout?->getContentAreas() ?? [] as $contentAreaData) {
            if (!isset($contentAreaData['colPos'])) {
                continue;
            }
            if (!isset($contentAreaData['identifier'])) {
                continue;
            }
            if (!isset($contentAreaData['slideMode'])) {
                $contentAreasWithoutSlideMode[(int)$contentAreaData['colPos']] = $contentAreaData;
            } else {
                $contentAreasWithSlideMode[] = $contentAreaData;
            }
            // Create the content for the $groupedContents array
            $contentAreaName = $contentAreaData['identifier'];
            $contentAreaData['records'] = [];
            $groupedContent[$contentAreaName] = $contentAreaData;
        }

        // 1. Content Areas without slide mode can be fetched with one SQL query, so let's do that first
        $allUsedColPositionsWithoutSlideMode = array_column($contentAreasWithoutSlideMode, 'colPos');
        if ($allUsedColPositionsWithoutSlideMode !== []) {
            // 1a. Make the SQL query for all colPos
            $flatRecords = $this->recordCollector->collect(
                'tt_content',
                [
                    'where' => sprintf(
                        '{#colPos} in (%s)',
                        implode(',', array_map(intval(...), $allUsedColPositionsWithoutSlideMode))
                    ),
                    'orderBy' => 'colPos, sorting',
                ],
                ContentSlideMode::None,
                $cObj,
                $recordIdentityMap
            );
            // 1b. Sort the records into the contentArea they belong to
            foreach ($flatRecords as $recordToSort) {
                $colPosOfRecord = (int)$recordToSort->get('colPos');
                $groupIdentifier = $contentAreasWithoutSlideMode[$colPosOfRecord]['identifier'];
                $groupedContent[$groupIdentifier]['records'][] = $recordToSort;
            }
        }

        // 2. Slide Mode elements need to be fetched one by one
        foreach ($contentAreasWithSlideMode as $contentAreaData) {
            $records = $this->recordCollector->collect(
                'tt_content',
                [
                    'where' => '{#colPos}=' . (int)$contentAreaData['colPos'],
                    'orderBy' => 'sorting',
                ],
                ContentSlideMode::tryFrom($contentAreaData['slideMode'] ?? null),
                $cObj,
                $recordIdentityMap
            );
            $contentAreaData['records'] = $records;
            $contentAreaName = $contentAreaData['identifier'];
            $groupedContent[$contentAreaName] = $contentAreaData;
        }

        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'content');
        $processedData[$targetVariableName] = $this->eventDispatcher->dispatch(
            new AfterContentHasBeenFetchedEvent($groupedContent, $request)
        )->groupedContent;
        return $processedData;
    }
}
