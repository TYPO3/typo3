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

namespace TYPO3\CMS\Backend\Tree;

/**
 * Abstract Tree Data Provider
 */
abstract class AbstractTreeDataProvider
{
    /**
     * Root Node
     *
     * @var \TYPO3\CMS\Backend\Tree\TreeNode
     */
    protected $rootNode;

    /**
     * Returns the root node
     *
     * @return \TYPO3\CMS\Backend\Tree\TreeNode
     */
    abstract public function getRoot();
}
