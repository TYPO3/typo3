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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Install\FolderStructure\RootNode;
use TYPO3\CMS\Install\FolderStructure\StructureFacade;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class StructureFacadeTest extends UnitTestCase
{
    #[Test]
    public function getStatusCallsRootGetStatus(): void
    {
        $facade = $this->getAccessibleMock(StructureFacade::class, null, [], '', false);
        $root = $this->createMock(RootNode::class);
        $root->expects(self::once())->method('getStatus')->willReturn([]);
        $facade->_set('structure', $root);
        $facade->getStatus();
    }

    #[Test]
    public function fixCallsFixOfStructure(): void
    {
        $facade = $this->getAccessibleMock(StructureFacade::class, null, [], '', false);
        $root = $this->createMock(RootNode::class);
        $root->expects(self::once())->method('fix')->willReturn([]);
        $facade->_set('structure', $root);
        $facade->fix();
    }
}
