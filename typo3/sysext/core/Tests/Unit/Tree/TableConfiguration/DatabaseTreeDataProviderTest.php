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

namespace TYPO3\CMS\Core\Tests\Unit\Tree\TableConfiguration;

use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Core\Tree\TableConfiguration\DatabaseTreeDataProvider;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DatabaseTreeDataProviderTest extends UnitTestCase
{
    /**
     * @test
     */
    public function loadTreeDataLevelMaximumSetToZeroWorks(): void
    {
        $subject = $this->getAccessibleMock(DatabaseTreeDataProvider::class, ['getRelatedRecords', 'getStartingPoints', 'getChildrenOf'], [], '', false);
        $subject->method('getStartingPoints')->willReturn([0]);
        $treeData = new TreeNode();
        $subject->_set('treeData', $treeData);
        $subject->_set('levelMaximum', 0);
        $subject->expects(self::never())->method('getChildrenOf');
        $subject->_call('loadTreeData');
    }

    /**
     * @test
     */
    public function loadTreeDataLevelMaximumSetToOneWorks(): void
    {
        $subject = $this->getAccessibleMock(DatabaseTreeDataProvider::class, ['getRelatedRecords', 'getStartingPoints', 'getChildrenOf'], [], '', false);
        $subject->method('getStartingPoints')->willReturn([0]);
        $treeData = new TreeNode();
        $subject->_set('treeData', $treeData);
        $subject->_set('levelMaximum', 1);
        $subject->expects(self::once())->method('getChildrenOf')->with($treeData, 1);
        $subject->_call('loadTreeData');
    }
}
