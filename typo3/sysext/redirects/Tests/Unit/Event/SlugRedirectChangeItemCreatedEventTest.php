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

namespace TYPO3\CMS\Redirects\Tests\Unit\Event;

use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Redirects\Event\SlugRedirectChangeItemCreatedEvent;
use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceCollection;
use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItem;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SlugRedirectChangeItemCreatedEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function slugRedirectChangeItemGetterReturnsChangeItemUsedForInstantiation(): void
    {
        $changeItem = new SlugRedirectChangeItem(
            defaultLanguagePageId: 0,
            pageId: 0,
            site: $this->createMock(Site::class),
            siteLanguage: $this->createMock(SiteLanguage::class),
            original: ['slug' => '/origianl'],
            sourcesCollection: new RedirectSourceCollection(),
            changed: ['slug' => '/changed'],
        );
        $event = new SlugRedirectChangeItemCreatedEvent(
            slugRedirectChangeItem: $changeItem
        );
        self::assertSame($changeItem, $event->getSlugRedirectChangeItem());
    }

    /**
     * @test
     */
    public function slugRedirectChangeItemGetterReturnsChangedItemSetBySetter(): void
    {
        $changeItem = new SlugRedirectChangeItem(
            defaultLanguagePageId: 0,
            pageId: 0,
            site: $this->createMock(Site::class),
            siteLanguage: $this->createMock(SiteLanguage::class),
            original: ['slug' => '/origianl'],
            sourcesCollection: new RedirectSourceCollection(),
            changed: ['slug' => '/changed'],
        );
        $changeItemChanged = new SlugRedirectChangeItem(
            defaultLanguagePageId: 0,
            pageId: 0,
            site: $this->createMock(Site::class),
            siteLanguage: $this->createMock(SiteLanguage::class),
            original: ['slug' => '/origianl'],
            sourcesCollection: new RedirectSourceCollection(),
            changed: ['slug' => '/override'],
        );
        $event = new SlugRedirectChangeItemCreatedEvent(
            slugRedirectChangeItem: $changeItem
        );
        $event->setSlugRedirectChangeItem($changeItemChanged);
        self::assertSame($changeItemChanged, $event->getSlugRedirectChangeItem());
    }
}
