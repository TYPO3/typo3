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
        $request = $cObj->getRequest();
        $pageInformation = $request->getAttribute('frontend.page.information');
        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'content');
        $contentAreas = $pageInformation->getPageLayout()?->getContentAreas();
        $groupedContent = $this->eventDispatcher->dispatch(
            new AfterContentHasBeenFetchedEvent($contentAreas->getGroupedRecords(), $request)
        )->groupedContent;
        $processedData[$targetVariableName] = $contentAreas->withUpdatedRecords($groupedContent);
        return $processedData;
    }
}
