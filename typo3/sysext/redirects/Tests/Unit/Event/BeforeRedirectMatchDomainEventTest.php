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
use TYPO3\CMS\Redirects\Event\BeforeRedirectMatchDomainEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class BeforeRedirectMatchDomainEventTest extends UnitTestCase
{
    #[Test]
    public function getterReturnsConstructorSetValues(): void
    {
        $event = new BeforeRedirectMatchDomainEvent('acme.com', '/some-path', '?param=value', '*');

        self::assertSame('acme.com', $event->getDomain());
        self::assertSame('/some-path', $event->getPath());
        self::assertSame('?param=value', $event->getQuery());
        self::assertSame('*', $event->getMatchDomainName());
        self::assertNull($event->getMatchedRedirect());
    }

    #[Test]
    public function matchedRedirectSetterReturnsSetMatchedRedirectAndCanBeSetToNull(): void
    {
        $redirect = ['some-redirect'];
        $event = new BeforeRedirectMatchDomainEvent('acme.com', '/some-path', '?param=value', '*');

        $event->setMatchedRedirect($redirect);
        self::assertSame($redirect, $event->getMatchedRedirect());

        $event->setMatchedRedirect(null);
        self::assertNull($event->getMatchedRedirect());
    }
}
