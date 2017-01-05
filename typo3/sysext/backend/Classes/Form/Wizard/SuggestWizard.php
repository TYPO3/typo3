<?php
namespace TYPO3\CMS\Backend\Form\Wizard;

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
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Wizard for rendering an AJAX selector for records.
 * See SuggestWizardController for the ajax handling counterpart.
 *
 * @deprecated since TYPO3 v8, will be removed in TYPO3 v9 - suggest has been merged to GroupElement directly
 */
class SuggestWizard
{

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * Construct
     *
     * @param StandaloneView $view
     * @deprecated since TYPO3 CMS 8, will be removed in TYPO3 CMS 9.
     */
    public function __construct(StandaloneView $view = null)
    {
        GeneralUtility::logDeprecatedFunction();
        $this->view = $view ?: $this->getFluidTemplateObject('SuggestWizard.html');
    }

    /**
     * Renders an ajax-enabled text field. Also adds required JS
     *
     * @param array $data Main data array from FormEngine
     * @throws \RuntimeException for incomplete incoming arguments
     * @return string The HTML code for the selector
     */
    public function renderSuggestSelector(array $data)
    {
        $fieldName = $data['fieldName'];
        $dataStructureIdentifier = '';
        $flexFormSheetName = '';
        $flexFormFieldName = '';
        $flexFormContainerName = '';
        $flexFormContainerFieldName = '';
        if ($data['processedTca']['columns'][$fieldName]['config']['type'] === 'flex') {
            $flexFormConfig = $data['processedTca']['columns'][$fieldName];
            $dataStructureIdentifier = $flexFormConfig['config']['dataStructureIdentifier'];
            if (!isset($flexFormConfig['config']['dataStructureIdentifier'])) {
                throw new \RuntimeException(
                    'A data structure identifier must be set in [\'config\'] part of a flex form.'
                    . ' This is usually added by TcaFlexPrepare data processor',
                    1478604742
                );
            }
            if (isset($data['flexFormSheetName'])) {
                $flexFormSheetName = $data['flexFormSheetName'];
            }
            if (isset($data['flexFormFieldName'])) {
                $flexFormFieldName = $data['flexFormFieldName'];
            }
            if (isset($data['flexFormContainerName'])) {
                $flexFormContainerName = $data['flexFormContainerName'];
            }
            if (isset($data['flexFormContainerFieldName'])) {
                $flexFormContainerFieldName = $data['flexFormContainerFieldName'];
            }
        }

        // Get minimumCharacters from TCA
        $minChars = 0;
        $fieldTca = $data['parameterArray']['fieldConf'];
        if (isset($fieldTca['config']['wizards']['suggest']['default']['minimumCharacters'])) {
            $minChars = (int)$fieldTca['config']['wizards']['suggest']['default']['minimumCharacters'];
        }
        // Overwrite it with minimumCharacters from TSConfig if given
        $fieldTsConfig = $data['parameterArray']['fieldTSConfig'];
        if (isset($fieldTsConfig['suggest.']['default.']['minimumCharacters'])) {
            $minChars = (int)$fieldTsConfig['suggest.']['default.']['minimumCharacters'];
        }
        $minChars = $minChars > 0 ? $minChars : 2;

        $this->view->assignMultiple([
            'placeholder' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.findRecord',
            'fieldName' => $data['fieldName'],
            'tableName' => $data['tableName'],
            'field' => $data['parameterArray']['itemFormElName'],
            'uid' => $data['databaseRow']['uid'],
            'pid' => (int)$data['effectivePid'],
            'dataStructureIdentifier' => $dataStructureIdentifier,
            'flexFormSheetName' => $flexFormSheetName,
            'flexFormFieldName' => $flexFormFieldName,
            'flexFormContainerName' => $flexFormContainerName,
            'flexFormContainerFieldName' => $flexFormContainerFieldName,
            'fieldtype' => $fieldTca['config']['type'],
            'minchars' => (int)$minChars,
        ]);

        return $this->view->render();
    }

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $filename Which templateFile should be used.
     *
     * @return StandaloneView
     */
    protected function getFluidTemplateObject(string $filename = null): StandaloneView
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setLayoutRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Layouts')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);

        if ($filename === null) {
            $filename = 'SuggestWizard.html';
        }

        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates/Wizards/' . $filename));

        $view->getRequest()->setControllerExtensionName('Backend');
        return $view;
    }
}
