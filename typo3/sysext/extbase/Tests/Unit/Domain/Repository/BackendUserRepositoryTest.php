<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Domain\Repository;

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
class BackendUserRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function initializeObjectSetsRespectStoragePidToFalse()
    {
        $objectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $fixture = new \TYPO3\CMS\Extbase\Domain\Repository\BackendUserGroupRepository($objectManager);
        $querySettings = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class);
        $querySettings->expects($this->once())->method('setRespectStoragePage')->with(false);
        $objectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class)->will($this->returnValue($querySettings));
        $fixture->initializeObject();
    }

    /**
     * @test
     */
    public function initializeObjectSetsDefaultQuerySettings()
    {
        $objectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        /** @var $fixture \TYPO3\CMS\Extbase\Domain\Repository\BackendUserGroupRepository */
        $fixture = $this->getMock(\TYPO3\CMS\Extbase\Domain\Repository\BackendUserGroupRepository::class, ['setDefaultQuerySettings'], [$objectManager]);
        $querySettings = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class);
        $objectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class)->will($this->returnValue($querySettings));
        $fixture->expects($this->once())->method('setDefaultQuerySettings')->with($querySettings);
        $fixture->initializeObject();
    }
}
