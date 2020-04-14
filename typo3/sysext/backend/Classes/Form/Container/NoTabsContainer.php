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

namespace TYPO3\CMS\Backend\Form\Container;

/**
 * Handle a record that has no tabs.
 *
 * This container is called by FullRecordContainer and just wraps the output
 * of PaletteAndSingleContainer in some HTML.
 */
class NoTabsContainer extends AbstractContainer
{
    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $options = $this->data;
        $options['renderType'] = 'paletteAndSingleContainer';
        $resultArray = $this->nodeFactory->create($options)->render();
        $resultArray['html'] = '<div class="tab-content">' . $resultArray['html'] . '</div>';
        return $resultArray;
    }
}
