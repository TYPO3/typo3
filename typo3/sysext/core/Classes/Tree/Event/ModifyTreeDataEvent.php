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

namespace TYPO3\CMS\Core\Tree\Event;

use TYPO3\CMS\Backend\Tree\TreeNode;
use TYPO3\CMS\Core\Tree\TableConfiguration\AbstractTableConfigurationTreeDataProvider;

/**
 * Allows to modify tree data for any database tree
 */
final class ModifyTreeDataEvent
{

    /**
     * @var TreeNode
     */
    private $treeData;

    /**
     * @var AbstractTableConfigurationTreeDataProvider
     */
    private $provider;

    public function __construct(TreeNode $treeData, AbstractTableConfigurationTreeDataProvider $provider)
    {
        $this->treeData = $treeData;
        $this->provider = $provider;
    }

    public function getTreeData(): TreeNode
    {
        return $this->treeData;
    }

    public function setTreeData(TreeNode $treeData): void
    {
        $this->treeData = $treeData;
    }

    public function getProvider(): AbstractTableConfigurationTreeDataProvider
    {
        return $this->provider;
    }
}
