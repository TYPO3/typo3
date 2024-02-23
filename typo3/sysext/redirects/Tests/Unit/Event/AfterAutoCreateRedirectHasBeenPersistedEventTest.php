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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Redirects\Event\AfterAutoCreateRedirectHasBeenPersistedEvent;
use TYPO3\CMS\Redirects\RedirectUpdate\PlainSlugReplacementRedirectSource;
use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceCollection;
use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItem;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AfterAutoCreateRedirectHasBeenPersistedEventTest extends UnitTestCase
{
    #[Test]
    public function afterAutoCreateRedirectHasBeenPersistedGettersReturnsCreationValues(): void
    {
        $source = new PlainSlugReplacementRedirectSource(
            host: '*',
            path: '/some-path',
            targetLinkParameters: []
        );
        $changeItem = new SlugRedirectChangeItem(
            defaultLanguagePageId: 1,
            pageId: 1,
            site: $this->createMock(Site::class),
            siteLanguage: $this->createMock(SiteLanguage::class),
            original: ['original'],
            sourcesCollection: new RedirectSourceCollection($source),
            changed: ['changed'],
        );
        $redirectRecord = ['redirect-record'];

        $event = new AfterAutoCreateRedirectHasBeenPersistedEvent(
            slugRedirectChangeItem: $changeItem,
            source: $source,
            redirectRecord: $redirectRecord,
        );

        self::assertSame($source, $event->getSource());
        self::assertSame($changeItem, $event->getSlugRedirectChangeItem());
        self::assertSame($redirectRecord, $event->getRedirectRecord());
    }
}
