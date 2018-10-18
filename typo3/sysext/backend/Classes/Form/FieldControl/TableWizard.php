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
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

/**
 * Renders the icon with link parameters to the table wizard,
 * typically used for type=text with renderType=textTable.
 */
class TableWizard extends AbstractNode
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
        $itemName = $parameterArray['itemFormElName'];
        $row = $this->data['databaseRow'];

        if (!MathUtility::canBeInterpretedAsInteger($row['uid'])) {
            return [];
        }

        // Handle options and fallback
        $title = $options['title'] ?? 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.tableWizard';
        $xmlOutput = isset($options['xmlOutput']) ? (int)$options['xmlOutput'] : 0;
        $numNewRows = isset($options['numNewRows']) ? (int)$options['numNewRows'] : 5;

        $prefixOfFormElName = 'data[' . $this->data['tableName'] . '][' . $this->data['databaseRow']['uid'] . '][' . $this->data['fieldName'] . ']';
        $flexFormPath = '';
        if (GeneralUtility::isFirstPartOfStr($itemName, $prefixOfFormElName)) {
            $flexFormPath = str_replace('][', '/', substr($itemName, strlen($prefixOfFormElName) + 1, -1));
        }

        $urlParameters = [
            'P' => [
                'params' => [
                    'xmlOutput' => $xmlOutput,
                    'numNewRows' => $numNewRows,
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
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        return [
            'iconIdentifier' => 'content-table',
            'title' => $title,
            'linkAttributes' => [
                'id' => htmlspecialchars($id),
                'href' => (string)$uriBuilder->buildUriFromRoute('wizard_table', $urlParameters),
            ],
            'requireJsModules' => [
                ['TYPO3/CMS/Backend/FormEngine/FieldControl/TableWizard' => 'function(FieldControl) {new FieldControl(' . GeneralUtility::quoteJSvalue('#' . $id) . ');}'],
            ],
        ];
    }
}
