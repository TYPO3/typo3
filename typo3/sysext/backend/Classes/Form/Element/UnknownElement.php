<?php
namespace TYPO3\CMS\Backend\Form\Element;

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
 * Generation of TCEform elements of where the type is unknown
 */
class UnknownElement extends AbstractFormElement
{
    /**
     * Handler for unknown types.
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $type = $this->data['parameterArray']['fieldConf']['config']['type'];
        $renderType = $this->data['renderType'];
        $resultArray = $this->initializeResultArray();
        $resultArray['html'] = 'Unknown type: ' . $type . ($renderType ? ', render type: ' . $renderType : '') . '<br />';
        return $resultArray;
    }
}
