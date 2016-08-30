<?php
namespace TYPO3\CMS\Beuser\Tests\Unit\Service;

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
class ModuleDataStorageServiceTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function loadModuleDataReturnsModuleDataObjectForEmptyModuleData()
    {
        // The test calls several static dependencies that can not be mocked and
        // call database in the end, so we need to mock the database here.
        $GLOBALS['TYPO3_DB'] = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, [], [], '', false);

        // Simulate empty module data
        $GLOBALS['BE_USER'] = $this->getMock(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class, [], [], '', false);
        $GLOBALS['BE_USER']->uc = [];
        $GLOBALS['BE_USER']->uc['moduleData'] = [];

        /** @var \TYPO3\CMS\Beuser\Service\ModuleDataStorageService $subject */
        $subject = $this->getAccessibleMock(\TYPO3\CMS\Beuser\Service\ModuleDataStorageService::class, ['dummy'], [], '', false);
        $objectManagerMock = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManager::class, [], [], '', false);
        $moduleDataMock = $this->getMock(\TYPO3\CMS\Beuser\Domain\Model\ModuleData::class, [], [], '', false);
        $objectManagerMock
            ->expects($this->once())
            ->method('get')
            ->with(\TYPO3\CMS\Beuser\Domain\Model\ModuleData::class)
            ->will($this->returnValue($moduleDataMock));
        $subject->_set('objectManager', $objectManagerMock);

        $this->assertSame($moduleDataMock, $subject->loadModuleData());
    }
}
