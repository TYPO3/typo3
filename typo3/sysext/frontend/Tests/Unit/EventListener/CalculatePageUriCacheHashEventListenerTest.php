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

namespace TYPO3\CMS\Frontend\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\Event\AfterPageUriGeneratedEvent;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\EventListener\CalculatePageUriCacheHashEventListener;
use TYPO3\CMS\Frontend\Page\CacheHashCalculator;
use TYPO3\CMS\Frontend\Page\CacheHashConfiguration;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[BackupGlobals(true)]
final class CalculatePageUriCacheHashEventListenerTest extends UnitTestCase
{
    private function createEvent(Uri $uri, PageArguments $pageArguments): AfterPageUriGeneratedEvent
    {
        $site = new Site('lotus-flower', 13, []);
        return new AfterPageUriGeneratedEvent(
            $uri,
            13,
            [],
            '',
            '0',
            $site->getDefaultLanguage(),
            $site,
            13,
            $pageArguments,
        );
    }

    #[Test]
    public function invokeAppendsCacheHashToUri(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 't3lib_cacheHashTest';
        $cacheHashCalculator = new CacheHashCalculator(new CacheHashConfiguration([]), new HashService());
        $subject = new CalculatePageUriCacheHashEventListener($cacheHashCalculator);

        $pageArguments = new PageArguments(13, '0', [], [], ['foo' => 'bar']);
        $uri = new Uri('https://example.com/mr-magpie/bloom?foo=bar');
        $event = $this->createEvent($uri, $pageArguments);
        $subject($event);

        $expectedCacheHash = $cacheHashCalculator->generateForParameters('foo=bar&id=13');
        self::assertSame(
            'https://example.com/mr-magpie/bloom?foo=bar&cHash=' . $expectedCacheHash,
            (string)$event->getUri()
        );
    }

    #[Test]
    public function invokeLeavesUriUnchangedWithoutDynamicArguments(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 't3lib_cacheHashTest';
        $cacheHashCalculator = new CacheHashCalculator(new CacheHashConfiguration([]), new HashService());
        $subject = new CalculatePageUriCacheHashEventListener($cacheHashCalculator);

        $pageArguments = new PageArguments(13, '0', []);
        $uri = new Uri('https://example.com/mr-magpie/bloom');
        $event = $this->createEvent($uri, $pageArguments);
        $subject($event);

        self::assertSame($uri, $event->getUri());
    }

    #[Test]
    public function invokeLeavesUriUntouchedWhenNoCacheHashIsRequired(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = 't3lib_cacheHashTest';
        $cacheHashCalculator = new CacheHashCalculator(new CacheHashConfiguration([]), new HashService());
        $subject = new CalculatePageUriCacheHashEventListener($cacheHashCalculator);

        // "id" and "type" are excluded as "core parameters", so no cHash is required, even
        // though dynamic arguments are not empty.
        $pageArguments = new PageArguments(13, '0', [], [], ['id' => '13', 'type' => '1']);
        $uri = new Uri('https://example.com/mr-magpie/bloom?id=13&type=1');
        $event = $this->createEvent($uri, $pageArguments);
        $subject($event);

        self::assertSame($uri, $event->getUri());
    }
}
