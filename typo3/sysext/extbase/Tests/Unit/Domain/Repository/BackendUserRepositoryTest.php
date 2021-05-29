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

namespace TYPO3\CMS\Extbase\Tests\Unit\Domain\Repository;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Domain\Repository\BackendUserGroupRepository;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class BackendUserRepositoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function initializeObjectSetsRespectStoragePidToFalse(): void
    {
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $subject = new BackendUserGroupRepository($objectManager);
        $querySettings = $this->createMock(Typo3QuerySettings::class);
        $querySettings->expects(self::once())->method('setRespectStoragePage')->with(false);
        GeneralUtility::addInstance(Typo3QuerySettings::class, $querySettings);
        $subject->initializeObject();
    }

    /**
     * @test
     */
    public function initializeObjectSetsDefaultQuerySettings(): void
    {
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        /** @var $subject \TYPO3\CMS\Extbase\Domain\Repository\BackendUserGroupRepository */
        $subject = $this->getMockBuilder(BackendUserGroupRepository::class)
            ->setMethods(['setDefaultQuerySettings'])
            ->setConstructorArgs([$objectManager])
            ->getMock();
        $querySettings = $this->createMock(Typo3QuerySettings::class);
        GeneralUtility::addInstance(Typo3QuerySettings::class, $querySettings);
        $subject->expects(self::once())->method('setDefaultQuerySettings')->with($querySettings);
        $subject->initializeObject();
    }
}
