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

namespace TYPO3\CMS\Workspaces\Tests\Unit\Domain\Model;

use TYPO3\CMS\Core\Tests\Unit\Utility\Fixtures\GeneralUtilityFixture;
use TYPO3\CMS\Workspaces\Domain\Record\AbstractRecord;
use TYPO3\CMS\Workspaces\Domain\Record\WorkspaceRecord;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class WorkspaceRecordTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        GeneralUtilityFixture::resetFinalClassNameCache();
    }

    protected function tearDown(): void
    {
        GeneralUtilityFixture::resetFinalClassNameCache();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function isAbstractRecord(): void
    {
        $subject = new WorkspaceRecord([]);

        self::assertInstanceOf(AbstractRecord::class, $subject);
    }

    /**
     * @test
     */
    public function getReturnsWorkspaceRecordInstance(): void
    {
        $instance = WorkspaceRecord::get(1, ['title' => '']);

        self::assertInstanceOf(WorkspaceRecord::class, $instance);
    }

    /**
     * @test
     */
    public function getWithNonZeroUidAndNonEmptyDataReturnsInstanceWithTheProvidedData(): void
    {
        $title = 'some record title';

        $instance = WorkspaceRecord::get(1, ['title' => $title]);

        self::assertSame($title, $instance->getTitle());
    }

    /**
     * @test
     */
    public function getCalledTwoTimesWithTheSameUidAndDataDataReturnsDifferentInstancesForEachCall(): void
    {
        $uid = 1;
        $data = ['title' => ''];

        $instance1 = WorkspaceRecord::get($uid, $data);
        $instance2 = WorkspaceRecord::get($uid, $data);

        self::assertNotSame($instance1, $instance2);
    }

    /**
     * @test
     */
    public function getForConfiguredXclassReturnsInstanceOfXclass(): void
    {
        $xclassInstance = new class([]) extends WorkspaceRecord {
        };
        $xclassName = get_class($xclassInstance);
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][WorkspaceRecord::class] = ['className' => $xclassName];

        $instance = WorkspaceRecord::get(1, ['title' => '']);

        self::assertInstanceOf($xclassName, $instance);
    }
}
