<?php
namespace TYPO3\CMS\Beuser\Tests\Unit\Domain\Repository;

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
class BackendUserSessionRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function classCanBeInstantiated()
    {
        $objectManager = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
        new \TYPO3\CMS\Beuser\Domain\Repository\BackendUserSessionRepository($objectManager);
    }
}
