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

namespace TYPO3\CMS\Seo\Tests\Unit\Event;

use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Seo\Event\ModifyUrlForCanonicalTagEvent;
use TYPO3\CMS\Seo\Exception\CanonicalGenerationDisabledException;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ModifyUrlForCanonicalTagEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function gettersReturnInitializedObjects(): void
    {
        $request = (new ServerRequest(''));
        $page = new Page(['uid' => 123]);
        $url = 'https://example.com';
        $exception = new CanonicalGenerationDisabledException('disabled', 1706105185);
        $event = new ModifyUrlForCanonicalTagEvent($request, $page, $url, $exception);

        self::assertSame($url, $event->getUrl());
        self::assertSame($request, $event->getRequest());
        self::assertSame($page, $event->getPage());
        self::assertSame($exception, $event->getCanonicalGenerationDisabledException());
    }

    /**
     * @test
     */
    public function setOverwritesParameters(): void
    {
        $request = (new ServerRequest(''));
        $page = new Page(['uid' => 123]);
        $url = 'https://example.com';
        $exception = new CanonicalGenerationDisabledException('disabled', 1706105186);
        $event = new ModifyUrlForCanonicalTagEvent($request, $page, $url, $exception);

        self::assertSame($url, $event->getUrl());
        self::assertSame($request, $event->getRequest());
        self::assertSame($page, $event->getPage());
        self::assertSame($exception, $event->getCanonicalGenerationDisabledException());

        $newUrl = 'https://new-url.com';
        $event->setUrl($newUrl);

        self::assertSame($newUrl, $event->getUrl());
    }
}
