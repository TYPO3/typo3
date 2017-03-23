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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Generation of elements of the type "user"
 */
class UserElement extends AbstractFormElement
{
    /**
     * User defined field type
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render()
    {
        $resultArray = $this->initializeResultArray();

        $parameterArray = $this->data['parameterArray'];
        $parameterArray['table'] = $this->data['tableName'];
        $parameterArray['field'] = $this->data['fieldName'];
        $parameterArray['row'] = $this->data['databaseRow'];
        $parameterArray['parameters'] = isset($parameterArray['fieldConf']['config']['parameters'])
            ? $parameterArray['fieldConf']['config']['parameters']
            : [];
        $html = GeneralUtility::callUserFunction(
            $parameterArray['fieldConf']['config']['userFunc'],
            $parameterArray,
            $this
        );
        if (!isset($parameterArray['fieldConf']['config']['noTableWrapping'])
            || (bool)$parameterArray['fieldConf']['config']['noTableWrapping'] === false
        ) {
            $html = '<div class="formengine-field-item t3js-formengine-field-item">' . $html . '</div>';
        }
        $resultArray['html'] = $html;
        return $resultArray;
    }
}
