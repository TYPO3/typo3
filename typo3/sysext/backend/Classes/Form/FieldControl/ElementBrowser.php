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
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders the icon "select element via element browser",
 * typically used for type=group.
 */
class ElementBrowser extends AbstractNode
{
    /**
     * Add button control
     *
     * @return array As defined by FieldControl class
     */
    public function render()
    {
        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $parameterArray = $this->data['parameterArray'];
        $elementName = $parameterArray['itemFormElName'];
        $config = $parameterArray['fieldConf']['config'];
        $internalType = (string)$config['internal_type'];
        $allowed = GeneralUtility::trimExplode(',', $config['allowed'], true);

        if (isset($config['readOnly']) && $config['readOnly']) {
            return [];
        }

        if ($internalType === 'db') {
            $title = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.browse_db';
        } else {
            $title = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.browse_file';
        }

        // Check against inline uniqueness - Create some onclick js for delete control and element browser
        // to override record selection in some FAL scenarios - See 'appearance' docs of group element
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);
        $elementBrowserOnClickInline = '';
        if ($this->data['isInlineChild']
            && $this->data['inlineParentUid']
            && $this->data['inlineParentConfig']['foreign_table'] === $table
            && $this->data['inlineParentConfig']['foreign_unique'] === $fieldName
        ) {
            $objectPrefix = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']) . '-' . $table;
            $elementBrowserOnClickInline = $objectPrefix . '|inline.checkUniqueElement|inline.setUniqueElement';
        }
        $elementBrowserType = $internalType;
        if (is_array($config['appearance']) && isset($config['appearance']['elementBrowserType'])) {
            $elementBrowserType = $config['appearance']['elementBrowserType'];
        }
        $elementBrowserAllowed = implode(',', $allowed);
        if (is_array($config['appearance']) && isset($config['appearance']['elementBrowserAllowed'])) {
            $elementBrowserAllowed = $config['appearance']['elementBrowserAllowed'];
        }
        $elementBrowserOnClick = 'setFormValueOpenBrowser('
                . GeneralUtility::quoteJSvalue($elementBrowserType) . ','
                . GeneralUtility::quoteJSvalue($elementName . '|||' . $elementBrowserAllowed . '|' . $elementBrowserOnClickInline)
            . ');'
            . ' return false;';

        return [
            'iconIdentifier' => 'actions-insert-record',
            'title' => $title,
            'linkAttributes' => [
                'onClick' => $elementBrowserOnClick,
            ],
        ];
    }
}
