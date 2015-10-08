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
class FileMountRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function initializeObjectSetsRespectStoragePidToFalse()
    {
        /** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManagerInterface */
        $objectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        $fixture = new \TYPO3\CMS\Extbase\Domain\Repository\FileMountRepository($objectManager);
        $querySettings = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class);
        $querySettings->expects($this->once())->method('setRespectStoragePage')->with(false);
        $objectManager->expects($this->once())->method('get')->with(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class)->will($this->returnValue($querySettings));
        $fixture->initializeObject();
    }
}
