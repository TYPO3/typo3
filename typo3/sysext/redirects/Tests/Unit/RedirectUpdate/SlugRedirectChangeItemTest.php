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

namespace TYPO3\CMS\Redirects\Tests\Unit\RedirectUpdate;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceCollection;
use TYPO3\CMS\Redirects\RedirectUpdate\SlugRedirectChangeItem;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SlugRedirectChangeItemTest extends UnitTestCase
{
    #[Test]
    public function withChangedReturnsNewInstanceWithChangedSet(): void
    {
        $changeItem = $this->createInitialChangeItem();
        $extendedChangeItem = $changeItem->withChanged(['uid' => 1, 'sys_language_uid' => 0, 'slug' => '/changed']);

        self::assertSame('/changed', $extendedChangeItem->getChanged()['slug'] ?? null);
    }

    #[Test]
    public function withSourcesCollectionReturnsNewInstanceWithCorrectSourcesCollection(): void
    {
        $changeItem = $this->createInitialChangeItem();
        $extendedChangeItem = $changeItem->withSourcesCollection(new RedirectSourceCollection());

        self::assertNotSame($changeItem, $extendedChangeItem);
    }

    private function createInitialChangeItem(): SlugRedirectChangeItem
    {
        return new SlugRedirectChangeItem(
            defaultLanguagePageId: 1,
            pageId: 1,
            site: $this->createMock(Site::class),
            siteLanguage: $this->createMock(SiteLanguage::class),
            original: ['uid' => 1, 'sys_language_uid' => 0, 'slug' => '/initial'],
            sourcesCollection: new RedirectSourceCollection(),
            changed: null
        );
    }
}
