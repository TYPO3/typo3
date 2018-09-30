<?php
declare(strict_types = 1);
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
 * Generation of elements of the type "user". This is a dummy implementation.
 *
 * type="user" elements should be combined with a custom renderType to create custom output.
 * This implementation registered for type="user" kicks in if no renderType is given and is just
 * a fallback implementation to hint developers that the TCA registration is incomplete.
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
        $parameterArray = $this->data['parameterArray'];
        $resultArray = $this->initializeResultArray();

        if (empty($parameterArray['fieldConf']['config']['userFunc'])) {
            // If there is no (deprecated) userFunc, render some dummy output to explain this element
            // should usually not be called at all.
            // @deprecated The if can be removed in TYPO3 v10.0, keeping the body only.
            $resultArray['html'] = '<div class="alert alert-warning">';
            $resultArray['html'] .= 'This is dummy output: Field <code>' . htmlspecialchars($this->data['fieldName']) . '</code>';
            $resultArray['html'] .= 'of table <code>' . htmlspecialchars($this->data['tableName']) . '</code>';
            $resultArray['html'] .= ' is registered as type="user" element without a specific renderType.';
            $resultArray['html'] .= ' Please look up details in TCA reference documentation for type="user".';
            $resultArray['html'] .= '</div>';
            return $resultArray;
        }

        // @deprecated since TYPO3 v9, everything below will be removed in TYPO3 v10.0.
        trigger_error(
            'Properties "userFunc", "noTableWrapping" and "parameters" will be removed in TYPO3 v10.0. Use a renderType instead.',
            E_USER_DEPRECATED
        );
        $parameterArray['table'] = $this->data['tableName'];
        $parameterArray['field'] = $this->data['fieldName'];
        $parameterArray['row'] = $this->data['databaseRow'];
        $parameterArray['parameters'] = $parameterArray['fieldConf']['config']['parameters'] ?? [];
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
