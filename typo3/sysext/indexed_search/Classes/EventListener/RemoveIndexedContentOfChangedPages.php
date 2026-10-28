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

namespace TYPO3\CMS\IndexedSearch\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Cache\Event\AfterCacheFlushedEvent;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\IndexedSearch\Domain\Repository\AdministrationRepository;

/**
 * Removes the indexed content of the pages of changed records, if the
 * extension setting "deleteFromIndexAfterEditing" is enabled.
 *
 * @internal
 */
final readonly class RemoveIndexedContentOfChangedPages
{
    public function __construct(
        private ExtensionConfiguration $extensionConfiguration,
        private AdministrationRepository $administrationRepository,
    ) {}

    #[AsEventListener('typo3/cms-indexed-search/remove-indexed-content-of-changed-pages')]
    public function __invoke(AfterCacheFlushedEvent $event): void
    {
        if (!$this->isEnabled()) {
            return;
        }
        foreach ($event->result->getFlushedRecords() as $record) {
            if ($record->pageId > 0) {
                $this->administrationRepository->removeIndexedPageContent($record->pageId);
            }
        }
    }

    private function isEnabled(): bool
    {
        try {
            return (bool)$this->extensionConfiguration->get('indexed_search', 'deleteFromIndexAfterEditing');
        } catch (\Exception) {
            return false;
        }
    }
}
