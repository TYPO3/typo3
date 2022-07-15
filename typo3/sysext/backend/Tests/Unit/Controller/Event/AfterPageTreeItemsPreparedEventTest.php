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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller\Event;

use TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AfterPageTreeItemsPreparedEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function gettersReturnInitializedObjects(): void
    {
        $request = new ServerRequest(new Uri('https://example.com'));
        $items = [
            [
                'identifier' => 1,
            ],
            [
                'identifier' => 2,
            ],
        ];

        $event = new AfterPageTreeItemsPreparedEvent($request, $items);

        self::assertEquals($request, $event->getRequest());
        self::assertEquals($items, $event->getItems());

        $items = $event->getItems();
        $items[] = ['identifier' => 3];
        $event->setItems($items);

        self::assertEquals($items, $event->getItems());
    }
}
