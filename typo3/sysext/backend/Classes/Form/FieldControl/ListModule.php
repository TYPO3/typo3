<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Form\FieldControl;

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

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Renders the icon with link parameters to jump to the list module
 * "single table" view, showing only one configurable table.
 */
class ListModule extends AbstractNode
{
    /**
     * Add button control
     *
     * @return array As defined by FieldControl class
     */
    public function render()
    {
        $options = $this->data['renderData']['fieldControlOptions'];
        $parameterArray = $this->data['parameterArray'];

        // Handle options and fallback
        $title = $options['title'] ?? 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.list';

        $table = '';
        if (isset($options['table'])) {
            // Table given in options - use it
            $table = $options['table'];
        } elseif ($parameterArray['fieldConf']['config']['type'] === 'group'
            && isset($parameterArray['fieldConf']['config']['internal_type'])
            && $parameterArray['fieldConf']['config']['internal_type'] === 'db'
            && !empty($parameterArray['fieldConf']['config']['allowed'])
        ) {
            // Use first table from allowed list if specific table is not set in options
            $allowedTables = GeneralUtility::trimExplode(',', $parameterArray['fieldConf']['config']['allowed'], true);
            $table = array_pop($allowedTables);
        } elseif ($parameterArray['fieldConf']['config']['type'] === 'select'
            && !empty($parameterArray['fieldConf']['config']['foreign_table'])
        ) {
            // Use foreign_table if given for type=select
            $table = $parameterArray['fieldConf']['config']['foreign_table'];
        }
        if (empty($table)) {
            // Still no table - this element can not handle the list control.
            return [];
        }

        // Target pid of new records is current pid by default
        $pid = $this->data['effectivePid'];
        if (isset($options['pid'])) {
            // pid configured in options - use it
            $pid = $options['pid'];
        } elseif (isset($GLOBALS['TCA'][$table]['ctrl']['rootLevel']) && (int)$GLOBALS['TCA'][$table]['ctrl'] === 1) {
            // Target table can only exist on root level - set 0 as pid
            $pid = 0;
        }

        $urlParameters = [
            'P' => [
                'params' => [
                    'table' => $table,
                    'pid' => $pid,
                ],
                'table' => $this->data['tableName'],
                'uid' => $this->data['databaseRow']['uid'],
                'returnUrl' => $this->data['returnUrl'],
            ],
        ];

        $id = StringUtility::getUniqueId('t3js-formengine-fieldcontrol-');

        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);

        return [
            'iconIdentifier' => 'actions-system-list-open',
            'title' => $title,
            'linkAttributes' => [
                'id' => htmlspecialchars($id),
                'href' => (string)$uriBuilder->buildUriFromRoute('wizard_list', $urlParameters),
            ],
            'requireJsModules' => [
                ['TYPO3/CMS/Backend/FormEngine/FieldControl/ListModule' => 'function(FieldControl) {new FieldControl(' . GeneralUtility::quoteJSvalue('#' . $id) . ');}'],
            ],
        ];
    }
}
