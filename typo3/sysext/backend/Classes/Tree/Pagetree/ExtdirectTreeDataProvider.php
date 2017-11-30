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

use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Data Provider of the Page Tree
 */
class ExtdirectTreeDataProvider extends \TYPO3\CMS\Backend\Tree\AbstractTree
{
    /**
     * Data Provider
     *
     * @var \TYPO3\CMS\Backend\Tree\Pagetree\DataProvider
     */
    protected $dataProvider = null;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
    }

    /**
     * Sets the data provider
     */
    protected function initDataProvider()
    {
        /** @var $dataProvider \TYPO3\CMS\Backend\Tree\Pagetree\DataProvider */
        $dataProvider = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\DataProvider::class);
        $this->setDataProvider($dataProvider);
    }

    /**
     * Returns the root node of the tree
     *
     * @return array
     */
    public function getRoot()
    {
        $this->initDataProvider();
        $node = $this->dataProvider->getRoot();
        return $node->toArray();
    }

    /**
     * Fetches the next tree level
     *
     * @param int $nodeId
     * @param \stdClass $nodeData
     * @return array
     */
    public function getNextTreeLevel($nodeId, $nodeData)
    {
        $this->initDataProvider();
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        if ($nodeId === 'root') {
            $nodeCollection = $this->dataProvider->getTreeMounts();
        } else {
            $nodeCollection = $this->dataProvider->getNodes($node, $node->getMountPoint());
        }
        return $nodeCollection->toArray();
    }

    /**
     * Returns a tree that only contains elements that match the given search string
     *
     * @param int $nodeId
     * @param \stdClass $nodeData
     * @param string $searchFilter
     * @return array
     */
    public function getFilteredTree($nodeId, $nodeData, $searchFilter)
    {
        if (strval($searchFilter) === '') {
            return [];
        }
        /** @var $node \TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode */
        $node = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\Pagetree\PagetreeNode::class, (array)$nodeData);
        $this->initDataProvider();
        if ($nodeId === 'root') {
            $nodeCollection = $this->dataProvider->getTreeMounts($searchFilter);
        } else {
            $nodeCollection = $this->dataProvider->getFilteredNodes($node, $searchFilter, $node->getMountPoint());
        }
        return $nodeCollection->toArray();
    }
}
