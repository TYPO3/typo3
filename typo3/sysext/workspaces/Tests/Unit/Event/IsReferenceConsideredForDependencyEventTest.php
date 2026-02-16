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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Workspaces\Dependency\DependencyCollectionAction;
use TYPO3\CMS\Workspaces\Event\IsReferenceConsideredForDependencyEvent;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class IsReferenceConsideredForDependencyEventTest extends UnitTestCase
{
    #[Test]
    public function gettersReturnConstructorValues(): void
    {
        $event = new IsReferenceConsideredForDependencyEvent(
            'tt_content',
            42,
            'image',
            'sys_file_reference',
            7,
            DependencyCollectionAction::Publish,
            1,
        );

        self::assertSame('tt_content', $event->getTableName());
        self::assertSame(42, $event->getRecordId());
        self::assertSame('image', $event->getFieldName());
        self::assertSame('sys_file_reference', $event->getReferenceTable());
        self::assertSame(7, $event->getReferenceId());
        self::assertSame(DependencyCollectionAction::Publish, $event->getAction());
        self::assertSame(1, $event->getWorkspaceId());
    }

    #[Test]
    public function isDependencyDefaultsToFalse(): void
    {
        $event = new IsReferenceConsideredForDependencyEvent(
            'tt_content',
            1,
            'bodytext',
            'pages',
            2,
            DependencyCollectionAction::StageChange,
            1,
        );

        self::assertFalse($event->isDependency());
    }

    #[Test]
    public function setDependencyChangesIsDependency(): void
    {
        $event = new IsReferenceConsideredForDependencyEvent(
            'tt_content',
            1,
            'image',
            'sys_file_reference',
            3,
            DependencyCollectionAction::Discard,
            1,
        );

        $event->setDependency(true);
        self::assertTrue($event->isDependency());

        $event->setDependency(false);
        self::assertFalse($event->isDependency());
    }
}
