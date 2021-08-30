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

namespace TYPO3\CMS\Install\Tests\Unit\FolderStructure;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Install\FolderStructure\RootNode;
use TYPO3\CMS\Install\FolderStructure\StructureFacade;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class StructureFacadeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getStatusReturnsStatusOfStructureAndReturnsItsResult(): void
    {
        /** @var $facade StructureFacade|AccessibleObjectInterface|MockObject */
        $facade = $this->getAccessibleMock(StructureFacade::class, ['dummy'], [], '', false);
        $root = $this->createMock(RootNode::class);
        $root->expects(self::once())->method('getStatus')->willReturn([]);
        $facade->_set('structure', $root);
        $status = $facade->getStatus();
        self::assertInstanceOf(FlashMessageQueue::class, $status);
    }

    /**
     * @test
     */
    public function fixCallsFixOfStructureAndReturnsItsResult(): void
    {
        /** @var $facade StructureFacade|AccessibleObjectInterface|MockObject */
        $facade = $this->getAccessibleMock(StructureFacade::class, ['dummy'], [], '', false);
        $root = $this->createMock(RootNode::class);
        $root->expects(self::once())->method('fix')->willReturn([]);
        $facade->_set('structure', $root);
        $status = $facade->fix();
        self::assertInstanceOf(FlashMessageQueue::class, $status);
    }
}
