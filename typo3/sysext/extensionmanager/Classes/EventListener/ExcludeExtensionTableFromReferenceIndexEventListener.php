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

namespace TYPO3\CMS\Extensionmanager\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\DataHandling\Event\IsTableExcludedFromReferenceIndexEvent;

/**
 * Exclude tx_extensionmanager_domain_model_extension from being handled
 * in ReferenceIndex.
 *
 * @todo: This can be removed when TCA of that table is dropped: When
 *        Domain/Repository/ExtensionRepository no longer uses extbase.
 * @internal
 */
final readonly class ExcludeExtensionTableFromReferenceIndexEventListener
{
    #[AsEventListener('typo3-extensionmanager/exclude-extension-table-from-reference-index')]
    public function __invoke(IsTableExcludedFromReferenceIndexEvent $event): void
    {
        if ($event->getTable() === 'tx_extensionmanager_domain_model_extension') {
            $event->markAsExcluded();
        }
    }
}
