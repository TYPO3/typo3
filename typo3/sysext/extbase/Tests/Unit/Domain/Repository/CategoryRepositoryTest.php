<?php

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

use TYPO3\CMS\Extbase\Domain\Repository\CategoryRepository;
use TYPO3\CMS\Extbase\Object\ObjectManagerInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class CategoryRepositoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function initializeObjectSetsRespectStoragePidToFalse()
    {
        /** @var $objectManager \TYPO3\CMS\Extbase\Object\ObjectManagerInterface */
        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $fixture = new CategoryRepository($objectManager);
        $querySettings = $this->createMock(Typo3QuerySettings::class);
        $querySettings->expects(self::once())->method('setRespectStoragePage')->with(false);
        $objectManager->expects(self::once())->method('get')->with(Typo3QuerySettings::class)->willReturn($querySettings);
        $fixture->initializeObject();
    }
}
