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

use TYPO3\CMS\Core\Page\PageLayoutResolver;
use TYPO3\CMS\Frontend\Content\ContentSlideMode;
use TYPO3\CMS\Frontend\Content\RecordCollector;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\DataProcessorInterface;

/**
 * All-in-one data processor that loads all tt_content records from the current page layout into
 * the template with a given identifier for each colPos, also respecting slideMode or
 * collect options based on the page layouts content columns.
 */
readonly class PageContentFetchingProcessor implements DataProcessorInterface
{
    public function __construct(
        protected RecordCollector $recordCollector,
        protected PageLayoutResolver $pageLayoutResolver,
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
        $pageInformation = $cObj->getRequest()->getAttribute('frontend.page.information');
        $pageLayout = $pageInformation->getPageLayout();

        $targetVariableName = $cObj->stdWrapValue('as', $processorConfiguration, 'content');
        foreach ($pageLayout?->getContentAreas() ?? [] as $contentAreaData) {
            if (!isset($contentAreaData['colPos'])) {
                continue;
            }
            if (!isset($contentAreaData['identifier'])) {
                continue;
            }
            $records = $this->recordCollector->collect(
                'tt_content',
                [
                    'where' => '{#colPos}=' . (int)$contentAreaData['colPos'],
                    'orderBy' => 'sorting',
                ],
                ContentSlideMode::tryFrom($contentAreaData['slideMode'] ?? null),
                $cObj,
            );
            $contentAreaData['records'] = $records;
            $contentAreaName = $contentAreaData['identifier'];
            $processedData[$targetVariableName][$contentAreaName] = $contentAreaData;
        }
        return $processedData;
    }
}
