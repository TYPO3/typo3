<?php
namespace TYPO3\CMS\Backend\Tree;

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
 * Abstract ExtJS tree based on ExtDirect
 */
abstract class AbstractExtJsTree extends \TYPO3\CMS\Backend\Tree\AbstractTree
{
    /**
     * State Provider
     *
     * @var \TYPO3\CMS\Backend\Tree\AbstractTreeStateProvider
     */
    protected $stateProvider = null;

    /**
     * @param \TYPO3\CMS\Backend\Tree\AbstractTreeStateProvider $stateProvider
     * @return void
     */
    public function setStateProvider(\TYPO3\CMS\Backend\Tree\AbstractTreeStateProvider $stateProvider)
    {
        $this->stateProvider = $stateProvider;
    }

    /**
     * @return \TYPO3\CMS\Backend\Tree\AbstractTreeStateProvider
     */
    public function getStateProvider()
    {
        return $this->stateProvider;
    }

    /**
     * Fetches the next tree level
     *
     * @param int $nodeId
     * @param stdClass $nodeData
     * @return array
     */
    abstract public function getNextTreeLevel($nodeId, $nodeData);
}
