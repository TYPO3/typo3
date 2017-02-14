<?php
namespace TYPO3\CMS\Backend\Tree\Pagetree;

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
 * Interface for classes which perform pre or post processing
 */
interface CollectionProcessorInterface
{
    /**
     * Post process the subelement collection of a specific node
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
     * @param int $mountPoint
     * @param int $level
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection $nodeCollection
     */
    public function postProcessGetNodes($node, $mountPoint, $level, $nodeCollection);

    /**
     * Post process the subelement collection of a specific node-filter combination
     *
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode $node
     * @param string $searchFilter
     * @param int $mountPoint
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection $nodeCollection
     */
    public function postProcessFilteredNodes($node, $searchFilter, $mountPoint, $nodeCollection);

    /**
     * Post process the collection of tree mounts
     *
     * @param string $searchFilter
     * @param \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNodeCollection $nodeCollection
     */
    public function postProcessGetTreeMounts($searchFilter, $nodeCollection);
}
