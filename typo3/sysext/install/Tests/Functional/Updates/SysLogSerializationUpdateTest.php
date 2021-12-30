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

use TYPO3\CMS\Install\Updates\SysLogSerializationUpdate;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class SysLogSerializationUpdateTest extends FunctionalTestCase
{
    protected string $baseDataSet = __DIR__ . '/Fixtures/SysLogSerializationBase.csv';
    protected string $resultDataSet = __DIR__ . '/Fixtures/SysLogSerializationFinished.csv';

    /**
     * @test
     */
    public function sysLogEntriesAreUpdated(): void
    {
        $subject = new SysLogSerializationUpdate();
        $this->importCSVDataSet($this->baseDataSet);
        self::assertTrue($subject->updateNecessary());
        $subject->executeUpdate();
        self::assertFalse($subject->updateNecessary());
        $this->assertCSVDataSet($this->resultDataSet);

        // Just ensure that running the upgrade again does not change anything
        $subject->executeUpdate();
        $this->assertCSVDataSet($this->resultDataSet);
    }
}
