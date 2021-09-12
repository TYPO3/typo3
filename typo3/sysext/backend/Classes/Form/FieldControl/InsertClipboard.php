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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
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
        $internalType = (string)($config['internal_type'] ?? 'db');
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
        if ($internalType === 'db') {
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
                JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Backend/FormEngine/FieldControl/InsertClipboard')->instance('#' . $id),
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
