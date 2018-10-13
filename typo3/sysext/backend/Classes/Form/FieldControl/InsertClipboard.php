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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;

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
    public function render(): array
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
        $dataAttributes = [
            'element' => $elementName,
            'clipboardItems' => [],
        ];
        if ($internalType === 'file_reference' || $internalType === 'file') {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Deprecation logged by TcaMigration class.
            $title = sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clipInsert_file'), count($clipboardElements));
            foreach ($clipboardElements as $clipboardElement) {
                $dataAttributes['clipboardItems'][] = [
                    'title' => rawurlencode(PathUtility::basename($clipboardElement['title'])),
                    'value' => $clipboardElement['value'],
                ];
            }
        } elseif ($internalType === 'db') {
            $title = sprintf($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.clipInsert_db'), count($clipboardElements));
            foreach ($clipboardElements as $clipboardElement) {
                $dataAttributes['clipboardItems'][] = [
                    'title' => $clipboardElement['title'],
                    'value' => $clipboardElement['value'],
                ];
            }
        }

        $id = StringUtility::getUniqueId('t3js-formengine-fieldcontrol-');

        return [
            'iconIdentifier' => 'actions-document-paste-into',
            'title' => $title,
            'linkAttributes' => [
                'id' => htmlspecialchars($id),
                'data-element' => $dataAttributes['element'],
                'data-clipboard-items' => json_encode($dataAttributes['clipboardItems']),
            ],
            'requireJsModules' => [
                ['TYPO3/CMS/Backend/FormEngine/FieldControl/InsertClipboard' => 'function(FieldControl) {new FieldControl(' . GeneralUtility::quoteJSvalue('#' . $id) . ');}'],
            ],
        ];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
