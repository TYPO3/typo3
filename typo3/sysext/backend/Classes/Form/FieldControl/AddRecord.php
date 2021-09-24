<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Form\FieldControl;

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Renders the icon with link parameters to add a new record,
 * typically used for single elements of type=group or type=select.
 */
class AddRecord extends AbstractNode
{
    /**
     * Add button control
     *
     * @return array As defined by FieldControl class
     */
    public function render(): array
    {
        $options = $this->data['renderData']['fieldControlOptions'];
        $parameterArray = $this->data['parameterArray'];
        $itemName = (string)$parameterArray['itemFormElName'];

        // Handle options and fallback
        $title = $options['title'] ?? 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.createNew';
        $setValue = $options['setValue'] ?? 'append';

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
            // Still no table - this element can not handle the add control.
            return [];
        }

        $pid = $this->resolvePid($options, $table);
        $prefixOfFormElName = 'data[' . $this->data['tableName'] . '][' . $this->data['databaseRow']['uid'] . '][' . $this->data['fieldName'] . ']';
        $flexFormPath = '';
        if (str_starts_with($itemName, $prefixOfFormElName)) {
            $flexFormPath = str_replace('][', '/', substr($itemName, strlen($prefixOfFormElName) + 1, -1));
        }

        $urlParameters = [
            'P' => [
                'params' => [
                    'table' => $table,
                    'pid' => $pid,
                    'setValue' => $setValue,
                ],
                'table' => $this->data['tableName'],
                'field' => $this->data['fieldName'],
                'uid' => $this->data['databaseRow']['uid'],
                'flexFormPath' => $flexFormPath,
                'returnUrl' => $this->data['returnUrl'],
            ],
        ];

        $id = StringUtility::getUniqueId('t3js-formengine-fieldcontrol-');

        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        return [
            'iconIdentifier' => 'actions-add',
            'title' => $title,
            'linkAttributes' => [
                'id' => htmlspecialchars($id),
                'href' => (string)$uriBuilder->buildUriFromRoute('wizard_add', $urlParameters),
            ],
            'requireJsModules' => [
                ['TYPO3/CMS/Backend/FormEngine/FieldControl/AddRecord' => 'function(FieldControl) {new FieldControl(' . GeneralUtility::quoteJSvalue('#' . $id) . ');}'],
            ],
        ];
    }

    protected function resolvePid(array $options, string $table): int
    {
        // Target pid of new records is current pid by default
        $pid = $this->data['effectivePid'];
        if (isset($options['pid'])) {
            // pid configured in options - use it
            if ($options['pid'] === '###SITEROOT###' && ($this->data['site'] ?? null) instanceof Site) {
                // Substitute marker with pid from site object
                $pid = $this->data['site']->getRootPageId();
            } else {
                $pid = $options['pid'];
            }
        } elseif (
            isset($GLOBALS['TCA'][$table]['ctrl']['rootLevel'])
            && (int)$GLOBALS['TCA'][$table]['ctrl']['rootLevel'] === 1
        ) {
            // Target table can only exist on root level - set 0 as pid
            $pid = 0;
        }
        return (int)$pid;
    }
}
