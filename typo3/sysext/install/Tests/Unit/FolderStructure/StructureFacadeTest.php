<?php
namespace TYPO3\CMS\Install\Tests\Unit\FolderStructure;

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
class StructureFacadeTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function getStatusReturnsStatusOfStructureAndReturnsItsResult()
    {
        /** @var $facade \TYPO3\CMS\Install\FolderStructure\StructureFacade|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $facade = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\StructureFacade::class, ['dummy'], [], '', false);
        $root = $this->getMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, [], [], '', false);
        $root->expects($this->once())->method('getStatus')->will($this->returnValue([]));
        $facade->_set('structure', $root);
        $status = $facade->getStatus();
        $this->assertInternalType('array', $status);
    }

    /**
     * @test
     */
    public function fixCallsFixOfStructureAndReturnsItsResult()
    {
        /** @var $facade \TYPO3\CMS\Install\FolderStructure\StructureFacade|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
        $facade = $this->getAccessibleMock(\TYPO3\CMS\Install\FolderStructure\StructureFacade::class, ['dummy'], [], '', false);
        $root = $this->getMock(\TYPO3\CMS\Install\FolderStructure\RootNode::class, [], [], '', false);
        $root->expects($this->once())->method('fix')->will($this->returnValue([]));
        $facade->_set('structure', $root);
        $status = $facade->fix();
        $this->assertInternalType('array', $status);
    }
}
