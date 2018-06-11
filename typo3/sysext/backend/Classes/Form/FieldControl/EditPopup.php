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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Renders the icon with link parameters to edit a selected element,
 * typically used for single elements of type=group or type=select.
 */
class EditPopup extends AbstractNode
{
    /**
     * Edit popup control
     *
     * @return array As defined by FieldControl class
     */
    public function render()
    {
        $languageService = $this->getLanguageService();
        $options = $this->data['renderData']['fieldControlOptions'];

        $title = $options['title'] ?? 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.edit';

        $parameterArray = $this->data['parameterArray'];
        $itemName = $parameterArray['itemFormElName'];
        $windowOpenParameters = $options['windowOpenParameters'] ?? 'height=800,width=600,status=0,menubar=0,scrollbars=1';

        $urlParameters  = [
            'P' => [
                'table' => $this->data['tableName'],
                'field' => $this->data['fieldName'],
                'formName' => 'editform',
                'hmac' => GeneralUtility::hmac('editform' . $itemName, 'wizard_js'),
                'fieldChangeFunc' => $parameterArray['fieldChangeFunc'],
                'fieldChangeFuncHash' => GeneralUtility::hmac(serialize($parameterArray['fieldChangeFunc'])),
            ],
        ];

        $url = BackendUtility::getModuleUrl('wizard_edit', $urlParameters);
        $onClick = [];
        $onClick[] = 'this.blur();';
        $onClick[] = 'if (!TBE_EDITOR.curSelected(' . GeneralUtility::quoteJSvalue($itemName) . ')) {';
        $onClick[] =    'top.TYPO3.Modal.confirm(';
        $onClick[] =        '"' . $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:warning.header') . '",';
        $onClick[] =        '"' . $languageService->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:mess.noSelItemForEdit') . '",';
        $onClick[] =        'top.TYPO3.Severity.notice, [{text: TYPO3.lang[\'button.ok\'] || \'OK\', btnClass: \'btn-notice\', name: \'ok\'}]';
        $onClick[] =    ')';
        $onClick[] =    '.on("button.clicked", function(e) {';
        $onClick[] =        'if (e.target.name == "ok") { top.TYPO3.Modal.dismiss(); }}';
        $onClick[] =    ');';
        $onClick[] =    'return false;';
        $onClick[] = '}';
        $onClick[] = 'vHWin=window.open(';
        $onClick[] =    GeneralUtility::quoteJSvalue($url);
        $onClick[] =    '+\'&P[currentValue]=\'+TBE_EDITOR.rawurlencode(';
        $onClick[] =        'document.editform[' . GeneralUtility::quoteJSvalue($itemName) . '].value';
        $onClick[] =    ')';
        $onClick[] =    '+\'&P[currentSelectedValues]=\'+TBE_EDITOR.curSelected(';
        $onClick[] =        GeneralUtility::quoteJSvalue($itemName);
        $onClick[] =    '),';
        $onClick[] =    '\'\',';
        $onClick[] =    GeneralUtility::quoteJSvalue($windowOpenParameters);
        $onClick[] = ');';
        $onClick[] = 'vHWin.focus();';
        $onClick[] = 'return false;';

        return [
            'iconIdentifier' => 'actions-open',
            'title' => $title,
            'linkAttributes' => [
                'onClick' => implode('', $onClick),
            ],
        ];
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
