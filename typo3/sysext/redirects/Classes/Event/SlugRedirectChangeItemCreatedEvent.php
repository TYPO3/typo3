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

use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItem;

/**
 * This event is fired in the \TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItemFactory
 * factory if a new SlugRedirectChangeItem is created.
 *
 * It can be used to add additional sources, remove sources or completely remove the change item itself.
 * A source must implement the RedirectSourceInterface, and for each source a redirect record is created
 * later in the SlugService. If the SlugRedirectChangeItem is set to null, no further action is executed
 * for this slug change.
 */
final class SlugRedirectChangeItemCreatedEvent
{
    public function __construct(
        private SlugRedirectChangeItem $slugRedirectChangeItem
    ) {}

    public function getSlugRedirectChangeItem(): SlugRedirectChangeItem
    {
        return $this->slugRedirectChangeItem;
    }

    public function setSlugRedirectChangeItem(SlugRedirectChangeItem $slugRedirectChangeItem): void
    {
        $this->slugRedirectChangeItem = $slugRedirectChangeItem;
    }
}
