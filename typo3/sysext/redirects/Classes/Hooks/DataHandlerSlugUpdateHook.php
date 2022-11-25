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

namespace TYPO3\CMS\Redirects\Hooks;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItem;
use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItemFactory;
use TYPO3\CMS\Redirects\Service\SlugService;

/**
 * @internal This class is a specific TYPO3 hook implementation and is not part of the Public TYPO3 API.
 */
class DataHandlerSlugUpdateHook
{
    /**
     * Persisted slug values per record UID
     * e.g. `[13 => SlugRedirectChangeItem( $original = ['slug' => 'slug-a'] ), 14 => SlugRedirectChangeItem( $original = ['slug' => 'slug-x/example'] )`
     *
     * @var array<int, SlugRedirectChangeItem>
     */
    protected $persistedChangedItems;

    public function __construct(
        protected SlugService $slugService,
        protected SlugRedirectChangeItemFactory $slugRedirectChangeItemFactory,
    ) {
    }

    /**
     * Collects slugs of persisted records before having been updated.
     *
     * @param string|int $id (id could be string, for this reason no type hint)
     */
    public function processDatamap_preProcessFieldArray(array $incomingFieldArray, string $table, $id, DataHandler $dataHandler): void
    {
        if (
            $table !== 'pages'
            || empty($incomingFieldArray['slug'])
            || $this->isNestedHookInvocation($dataHandler)
            || !MathUtility::canBeInterpretedAsInteger($id)
            || !$dataHandler->checkRecordUpdateAccess($table, $id, $incomingFieldArray)
        ) {
            return;
        }

        $this->persistedChangedItems[(int)$id] = $this->slugRedirectChangeItemFactory->create((int)$id);
    }

    /**
     * Acts on potential slug changes.
     *
     * Hook `processDatamap_postProcessFieldArray` is executed after `DataHandler::fillInFields` which
     * ensure access to pages.slug field and applies possible evaluations (`eval => 'trim,...`).
     */
    public function processDatamap_postProcessFieldArray(string $status, string $table, $id, array $fieldArray, DataHandler $dataHandler): void
    {
        $persistedChangedItem = $this->persistedChangedItems[(int)$id] ?? null;

        if (
            $table !== 'pages'
            || $status !== 'update'
            || empty($fieldArray['slug'])
            || !($persistedChangedItem instanceof SlugRedirectChangeItem)
            || $persistedChangedItem->getOriginal()['slug'] === $fieldArray['slug']
            || $this->isNestedHookInvocation($dataHandler)
        ) {
            return;
        }

        $this->slugService->rebuildSlugsForSlugChange($id, $persistedChangedItem, $dataHandler->getCorrelationId());
    }

    /**
     * Determines whether our identifier is part of correlation id aspects.
     * In that case it would be a nested call which has to be ignored.
     */
    protected function isNestedHookInvocation(DataHandler $dataHandler): bool
    {
        $correlationId = $dataHandler->getCorrelationId();
        $correlationIdAspects = $correlationId ? $correlationId->getAspects() : [];
        return in_array(SlugService::CORRELATION_ID_IDENTIFIER, $correlationIdAspects, true);
    }
}
