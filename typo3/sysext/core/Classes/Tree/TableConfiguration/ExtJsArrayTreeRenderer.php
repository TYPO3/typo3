<?php
namespace TYPO3\CMS\Core\Tree\TableConfiguration;

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
 * Renders a tca tree array for ExtJS
 */
class ExtJsArrayTreeRenderer extends \TYPO3\CMS\Backend\Tree\Renderer\ExtJsJsonTreeRenderer
{
    /**
     * Renders a node collection recursive or just a single instance
     *
     * @param \TYPO3\CMS\Backend\Tree\AbstractTree $tree
     * @param bool $recursive
     * @return array
     */
    public function renderTree(\TYPO3\CMS\Backend\Tree\AbstractTree $tree, $recursive = true)
    {
        $this->recursionLevel = 0;
        return $this->renderNode($tree->getRoot(), $recursive);
    }
}
