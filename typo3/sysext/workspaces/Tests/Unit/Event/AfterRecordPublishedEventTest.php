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

namespace TYPO3\CMS\Workspaces\Tests\Unit\Event;

use TYPO3\CMS\Workspaces\Event\AfterRecordPublishedEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AfterRecordPublishedEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function gettersReturnInitializedObjects(): void
    {
        $table = 'some_table';
        $recordId = 2004;
        $workspaceId = 13;

        $event = new AfterRecordPublishedEvent($table, $recordId, $workspaceId);

        self::assertEquals($table, $event->getTable());
        self::assertEquals($recordId, $event->getRecordId());
        self::assertEquals($workspaceId, $event->getWorkspaceId());
    }
}
