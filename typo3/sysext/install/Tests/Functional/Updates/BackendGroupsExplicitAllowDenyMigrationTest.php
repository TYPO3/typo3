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

namespace TYPO3\CMS\Install\Tests\Functional\Updates;

use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Install\Updates\BackendGroupsExplicitAllowDenyMigration;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendGroupsExplicitAllowDenyMigrationTest extends FunctionalTestCase
{
    /**
     * @test
     */
    public function backendGroupsRowsAreUpdated(): void
    {
        /** @var MockObject&OutputInterface $outputMock */
        $outputMock = $this->getMockBuilder(OutputInterface::class)->getMock();
        $outputMock->expects(self::atLeastOnce())->method('writeln');
        $subject = new BackendGroupsExplicitAllowDenyMigration();
        $subject->setOutput($outputMock);
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BackendGroupsExplicitAllowDenyMigrationBefore.csv');
        self::assertTrue($subject->updateNecessary());
        $subject->executeUpdate();
        self::assertFalse($subject->updateNecessary());
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/BackendGroupsExplicitAllowDenyMigrationAfter.csv');

        // Just ensure that running the upgrade again does not change anything
        $subject->executeUpdate();
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/BackendGroupsExplicitAllowDenyMigrationAfter.csv');
    }
}
