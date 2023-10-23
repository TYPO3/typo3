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
 * This event is fired in the \TYPO3\CMS\Redirects\Service\SlugService after
 * a redirect record has been automatically created and persisted after page
 * slug change. It's mainly a pure notification event.
 *
 * It can be used to update redirects external in a load-balancer directly for
 * example, or doing some kind of synchronization.
 */
final class AfterAutoCreateRedirectHasBeenPersistedEvent
{
    public function __construct(
        private readonly SlugRedirectChangeItem $slugRedirectChangeItem,
        private readonly RedirectSourceInterface $source,
        private readonly array $redirectRecord,
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
}
