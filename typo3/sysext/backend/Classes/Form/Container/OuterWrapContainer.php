<?php

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

namespace TYPO3\CMS\Backend\Form\Container;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Render header and footer row.
 *
 * This is an entry container called from controllers.
 * It wraps the title and a footer around the main html.
 * It either calls a FullRecordContainer or ListOfFieldsContainer to render
 * a full record or only some fields from a full record.
 */
class OuterWrapContainer extends AbstractContainer
{
    public function __construct(
        private readonly IconFactory $iconFactory,
    ) {}

    /**
     * Entry method
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUserAuthentication();

        $table = $this->data['tableName'];
        $row = $this->data['databaseRow'];

        $options = $this->data;
        if (empty($this->data['fieldListToRender'])) {
            $options['renderType'] = 'fullRecordContainer';
        } else {
            $options['renderType'] = 'listOfFieldsContainer';
        }
        $result = $this->nodeFactory->create($options)->render();

        $childHtml = $result['html'];

        $icon = $this->iconFactory
            ->getIconForRecord($table, $row, IconSize::SMALL)
            ->render();

        // @todo: Could this be done in a more clever way? Does it work at all?
        $tableTitle = $languageService->sL($this->data['processedTca']['ctrl']['title']);

        if ($this->data['command'] === 'new') {
            $newOrUid = ' <span class="typo3-TCEforms-newToken">' . htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.new')) . '</span>';

            // @todo: There is quite some stuff do to for WS overlays ...
            $workspacedPageRecord = BackendUtility::getRecordWSOL('pages', $this->data['effectivePid'], 'title');
            $pageTitle = BackendUtility::getRecordTitle('pages', $workspacedPageRecord, true, false);
            if ($table === 'pages') {
                $label = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.createNewPage'));
                $pageTitle = sprintf($label, $tableTitle);
            } else {
                $label = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.createNewRecord'));
                if ($this->data['effectivePid'] === 0) {
                    $label = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.createNewRecordRootLevel'));
                }
                $pageTitle = sprintf($label, $tableTitle, $pageTitle);
            }
        } else {
            $newOrUid = ' <span class="typo3-TCEforms-recUid">[' . htmlspecialchars($row['uid']) . ']</span>';

            // @todo: getRecordTitlePrep applies an htmlspecialchars here
            $recordLabel = BackendUtility::getRecordTitlePrep($this->data['recordTitle']);
            if ($table === 'pages') {
                $label = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.editPage'));
                $pageTitle = sprintf($label, $tableTitle, $recordLabel);
            } else {
                $label = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.editRecord'));
                $workspacedPageRecord = BackendUtility::getRecordWSOL('pages', $row['pid'], 'uid,title');
                $pageTitle = BackendUtility::getRecordTitle('pages', $workspacedPageRecord, true, false);
                if (empty($recordLabel)) {
                    $label = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.editRecordNoTitle'));
                }
                if ($this->data['effectivePid'] === 0) {
                    $label = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.editRecordRootLevel'));
                }
                if (!empty($recordLabel)) {
                    // Use record title and prepend an edit label.
                    $pageTitle = sprintf($label, $tableTitle, $recordLabel, $pageTitle);
                } else {
                    // Leave out the record title since it is not set.
                    $pageTitle = sprintf($label, $tableTitle, $pageTitle);
                }
            }
        }

        $view = $this->backendViewFactory->create($this->data['request']);

        $descriptionColumn = !empty($this->data['processedTca']['ctrl']['descriptionColumn'])
            ? $this->data['processedTca']['ctrl']['descriptionColumn'] : null;
        if ($descriptionColumn !== null && isset($this->data['databaseRow'][$descriptionColumn])) {
            $view->assign('recordDescription', $this->data['databaseRow'][$descriptionColumn]);
        }
        $readOnlyRecord = !empty($this->data['processedTca']['ctrl']['readOnly'])
            ? (bool)$this->data['processedTca']['ctrl']['readOnly'] : null;
        if ($readOnlyRecord === true) {
            $view->assign('recordReadonly', true);
        }
        $fieldInformationResult = $this->renderFieldInformation();
        $fieldInformationHtml = $fieldInformationResult['html'];
        $result = $this->mergeChildReturnIntoExistingResult($result, $fieldInformationResult, false);

        $fieldWizardResult = $this->renderFieldWizard();
        $fieldWizardHtml = $fieldWizardResult['html'];
        $result = $this->mergeChildReturnIntoExistingResult($result, $fieldWizardResult, false);

        $view->assignMultiple([
            'pageTitle' => $pageTitle,
            'fieldInformationHtml' => $fieldInformationHtml,
            'fieldWizardHtml' => $fieldWizardHtml,
            'childHtml' => $childHtml,
            'icon' => $icon,
            'tableName' => $backendUser->shallDisplayDebugInformation() ? $table : '',
            'tableTitle' => $tableTitle,
            'newOrUid' => $newOrUid,
            'isNewRecord' => $this->data['command'] === 'new',
        ]);
        $result['html'] = $view->render('Form/OuterWrapContainer');
        return $result;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

}
