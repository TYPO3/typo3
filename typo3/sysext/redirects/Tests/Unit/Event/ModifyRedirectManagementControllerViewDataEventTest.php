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

use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Redirects\Event\ModifyRedirectManagementControllerViewDataEvent;
use TYPO3\CMS\Redirects\Repository\Demand;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ModifyRedirectManagementControllerViewDataEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function gettersReturnsSetValues(): void
    {
        $demand = $this->createMock(Demand::class);
        $redirects = [
            ['dummy' => 'value1'],
        ];
        $hosts = [
            ['dummy' => 'value1'],
        ];
        $statusCodes = [
            ['dummy' => 'value1'],
        ];
        $creationTypes = [
            ['dummy' => 'value1'],
        ];
        $showHitCounter = true;
        $view = $this->createMock(ViewInterface::class);
        $event = new ModifyRedirectManagementControllerViewDataEvent(
            $demand,
            $redirects,
            $hosts,
            $statusCodes,
            $creationTypes,
            $showHitCounter,
            $view,
            new ServerRequest(),
        );
        self::assertSame($demand, $event->getDemand());
        self::assertSame($redirects, $event->getRedirects());
        self::assertSame($hosts, $event->getHosts());
        self::assertSame($statusCodes, $event->getStatusCodes());
        self::assertSame($creationTypes, $event->getCreationTypes());
        self::assertSame($showHitCounter, $event->getShowHitCounter());
        self::assertSame($view, $event->getView());
    }
}
