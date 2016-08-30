<?php
namespace TYPO3\CMS\Belog\Tests\Unit\Domain\Repository;

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

/**
 * Test case
 */
class LogEntryRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function initializeObjectSetsRespectStoragePidToFalse()
    {
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings $querySettings */
        $querySettings = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class);
        $objectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $objectManager->expects($this->any())->method('get')->with(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class)->will($this->returnValue($querySettings));
        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);
        $querySettings->expects($this->atLeastOnce())->method('setRespectStoragePage')->with(false);
        /** @var \TYPO3\CMS\Belog\Domain\Repository\LogEntryRepository|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMock(\TYPO3\CMS\Belog\Domain\Repository\LogEntryRepository::class, ['setDefaultQuerySettings'], [$objectManager]);
        $subject->expects($this->once())->method('setDefaultQuerySettings')->with($querySettings);
        $subject->initializeObject();
    }
}
