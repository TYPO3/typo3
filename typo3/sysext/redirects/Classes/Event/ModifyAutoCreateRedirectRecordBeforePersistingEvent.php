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

namespace TYPO3\CMS\Redirects\Event;

use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceInterface;
use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItem;

/**
 * This event is fired in the \TYPO3\CMS\Redirects\Service\SlugService before
 * a redirect record is persisted for changed page slug.
 *
 * It can be used to modify the redirect record before persisting it. This
 * gives extension developers the ability to apply defaults or add custom
 * values to the record.
 */
final class ModifyAutoCreateRedirectRecordBeforePersistingEvent
{
    public function __construct(
        private readonly SlugRedirectChangeItem $slugRedirectChangeItem,
        private readonly RedirectSourceInterface $source,
        private array $redirectRecord,
    ) {}

    public function getSlugRedirectChangeItem(): SlugRedirectChangeItem
    {
        return $this->slugRedirectChangeItem;
    }

    public function getSource(): RedirectSourceInterface
    {
        return $this->source;
    }

    public function getRedirectRecord(): array
    {
        return $this->redirectRecord;
    }

    public function setRedirectRecord(array $redirectRecord): void
    {
        $this->redirectRecord = $redirectRecord;
    }
}
