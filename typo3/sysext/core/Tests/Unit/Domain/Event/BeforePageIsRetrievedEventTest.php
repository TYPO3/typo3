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

namespace TYPO3\CMS\Core\Tests\Unit\Domain\Event;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\Event\BeforePageIsRetrievedEvent;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BeforePageIsRetrievedEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnInitializedObjects(): void
    {
        $pageId = 2004;
        $skipGroupAccessCheck = false;
        $context = new Context();

        $event = new BeforePageIsRetrievedEvent($pageId, $skipGroupAccessCheck, $context);

        self::assertNull($event->getPage());
        self::assertFalse($event->hasPage());
        self::assertEquals($pageId, $event->getPageId());
        self::assertEquals($context, $event->getContext());
        self::assertEquals($skipGroupAccessCheck, $event->isGroupAccessCheckSkipped());
    }

    #[Test]
    public function modifyPageAndPageId(): void
    {
        $pageId = 2004;
        $page = new Page(['uid' => $pageId]);
        $skipGroupAccessCheck = false;
        $context = new Context();

        $event = new BeforePageIsRetrievedEvent(0, $skipGroupAccessCheck, $context);

        self::assertNull($event->getPage());
        self::assertFalse($event->hasPage());
        self::assertEquals(0, $event->getPageId());

        $event->setPage($page);
        $event->setPageId($pageId);
        self::assertEquals($page, $event->getPage());
        self::assertTrue($event->hasPage());
        self::assertEquals($pageId, $event->getPage()->getPageId());
        self::assertEquals($pageId, $event->getPageId());
    }
}
