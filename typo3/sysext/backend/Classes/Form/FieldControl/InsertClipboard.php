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
use TYPO3\CMS\Lang\LanguageService;

/**
 * Renders the icon "insert record from clipboard",
 * typically used for type=group.
 */
class InsertClipboard extends AbstractNode
{
    /**
     * Add button control
     *
     * @return array As defined by FieldControl class
     */
    public function render()
    {
        $languageService = $this->getLanguageService();

        $parameterArray = $this->data['parameterArray'];
        $elementName = $parameterArray['itemFormElName'];
        $config = $parameterArray['fieldConf']['config'];
        $internalType = (string)$config['internal_type'];
        $clipboardElements = $config['clipboardElements'];

        if ((isset($config['readOnly']) && $config['readOnly'])
            || empty($clipboardElements)
        ) {
            return [];
        }

        $title = '';
        $clipboardOnClick = [];
        if ($internalType === 'file_reference' || $internalType === 'file') {
            $title = sprintf($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.clipInsert_file'), count($clipboardElements));
            foreach ($clipboardElements as $clipboardElement) {
                $value = $clipboardElement['value'];
                $title = 'unescape(' . GeneralUtility::quoteJSvalue(rawurlencode(basename($clipboardElement['title']))) . ')';
                $clipboardOnClick[] = 'setFormValueFromBrowseWin('
                        . GeneralUtility::quoteJSvalue($elementName) . ','
                        . 'unescape(' . GeneralUtility::quoteJSvalue(rawurlencode(str_replace('%20', ' ', $value))) . '),'
                        . $title . ','
                        . $title
                    . ');';
            }
        } elseif ($internalType === 'db') {
            $title = sprintf($languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.clipInsert_db'), count($clipboardElements));
            foreach ($clipboardElements as $clipboardElement) {
                $value = $clipboardElement['value'];
                $title = GeneralUtility::quoteJSvalue($clipboardElement['title']);
                $clipboardOnClick[] = 'setFormValueFromBrowseWin('
                        . GeneralUtility::quoteJSvalue($elementName) . ','
                        . 'unescape(' . GeneralUtility::quoteJSvalue(rawurlencode(str_replace('%20', ' ', $value))) . '),'
                        . $title . ','
                        . $title
                    . ');';
            }
        }
        $clipboardOnClick[] = 'return false;';

        return [
            'iconIdentifier' => 'actions-document-paste-into',
            'title' => $title,
            'linkAttributes' => [
                'onClick' => implode('', $clipboardOnClick),
            ],
        ];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
