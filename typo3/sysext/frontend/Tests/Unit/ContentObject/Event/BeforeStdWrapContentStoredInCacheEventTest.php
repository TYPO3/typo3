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

namespace TYPO3\CMS\Frontend\Tests\Unit\ContentObject\Event;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\ContentObject\Event\BeforeStdWrapContentStoredInCacheEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BeforeStdWrapContentStoredInCacheEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $content = 'some content';
        $tags = ['foo', 'bar'];
        $key = 'cache-key';
        $lifetime = 1234;
        $configuration = ['cache.' => [$key]];
        $contentObjectRenderer = $this->createMock(ContentObjectRenderer::class);

        $event = new BeforeStdWrapContentStoredInCacheEvent(
            content: $content,
            tags: $tags,
            key: $key,
            lifetime: $lifetime,
            configuration: $configuration,
            contentObjectRenderer: $contentObjectRenderer
        );

        self::assertSame($content, $event->getContent());
        self::assertSame($tags, $event->getTags());
        self::assertSame($key, $event->getKey());
        self::assertSame($lifetime, $event->getLifetime());
        self::assertSame($configuration, $event->getConfiguration());
        self::assertSame($contentObjectRenderer, $event->getContentObjectRenderer());
    }

    #[Test]
    public function setterOverwriteInitializedData(): void
    {
        $content = 'some content';
        $tags = ['foo', 'bar'];
        $key = 'cache-key';
        $lifetime = 1234;
        $configuration = ['cache.' => [$key]];
        $contentObjectRenderer = $this->createMock(ContentObjectRenderer::class);

        $event = new BeforeStdWrapContentStoredInCacheEvent(
            content: $content,
            tags: $tags,
            key: $key,
            lifetime: $lifetime,
            configuration: $configuration,
            contentObjectRenderer: $contentObjectRenderer
        );

        self::assertSame($content, $event->getContent());
        self::assertSame($tags, $event->getTags());
        self::assertSame($key, $event->getKey());
        self::assertSame($lifetime, $event->getLifetime());
        self::assertSame($configuration, $event->getConfiguration());
        self::assertSame($contentObjectRenderer, $event->getContentObjectRenderer());

        $newContent = 'new content';
        $newTags = ['baz'];
        $newKey = 'new-cache-key';
        $newLifetime = 5678;

        $event->setContent($newContent);
        $event->setTags($newTags);
        $event->setKey($newKey);
        $event->setLifetime($newLifetime);

        self::assertSame($newContent, $event->getContent());
        self::assertSame($newTags, $event->getTags());
        self::assertSame($newKey, $event->getKey());
        self::assertSame($newLifetime, $event->getLifetime());

        // unset lifetime
        $event->setLifetime(null);

        self::assertNull($event->getLifetime());
    }
}
