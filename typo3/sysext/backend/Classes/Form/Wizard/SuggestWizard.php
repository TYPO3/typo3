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
     */
    public function __construct(StandaloneView $view = null)
    {
        $this->view = $view ?: $this->getFluidTemplateObject('SuggestWizard.html');
    }

    /**
     * Renders an ajax-enabled text field. Also adds required JS
     *
     * @param string $fieldName The field name in the form
     * @param string $table The table we render this selector for
     * @param string $field The field we render this selector for
     * @param array $row The row which is currently edited
     * @param array $config The TSconfig of the field
     * @param array $flexFormConfig If field is within flex form, this is the TCA config of the flex field
     * @throws \RuntimeException for incomplete incoming arguments
     * @return string The HTML code for the selector
     */
    public function renderSuggestSelector($fieldName, $table, $field, array $row, array $config, array $flexFormConfig = [])
    {
        $dataStructureIdentifier = '';
        if (!empty($flexFormConfig) && $flexFormConfig['config']['type'] === 'flex') {
            $fieldPattern = 'data[' . $table . '][' . $row['uid'] . '][';
            $flexformField = str_replace($fieldPattern, '', $fieldName);
            $flexformField = substr($flexformField, 0, -1);
            $field = str_replace([']['], '|', $flexformField);
            if (!isset($flexFormConfig['config']['dataStructureIdentifier'])) {
                throw new \RuntimeException(
                    'A data structure identifier must be set in [\'config\'] part of a flex form.'
                    . ' This is usually added by TcaFlexPrepare data processor',
                    1478604742
                );
            }
            $dataStructureIdentifier = $flexFormConfig['config']['dataStructureIdentifier'];
        }

        // Get minimumCharacters from TCA
        $minChars = 0;
        if (isset($config['fieldConf']['config']['wizards']['suggest']['default']['minimumCharacters'])) {
            $minChars = (int)$config['fieldConf']['config']['wizards']['suggest']['default']['minimumCharacters'];
        }
        // Overwrite it with minimumCharacters from TSConfig if given
        if (isset($config['fieldTSConfig']['suggest.']['default.']['minimumCharacters'])) {
            $minChars = (int)$config['fieldTSConfig']['suggest.']['default.']['minimumCharacters'];
        }
        $minChars = $minChars > 0 ? $minChars : 2;

        // fetch the TCA field type to hand it over to the JS class
        $type = '';
        if (isset($config['fieldConf']['config']['type'])) {
            $type = $config['fieldConf']['config']['type'];
        }

        // Sign those parameters that come back in an ajax request to configure the search in searchAction()
        $hmac = GeneralUtility::hmac(
            (string)$table . (string)$field . (string)$row['uid'] . (string)$row['pid'] . (string)$dataStructureIdentifier,
            'formEngineSuggest'
        );

        $this->view->assignMultiple([
                'placeholder' => 'LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:labels.findRecord',
                'fieldname' => $fieldName,
                'table' => $table,
                'field' => $field,
                'uid' => $row['uid'],
                'pid' => (int)$row['pid'],
                'dataStructureIdentifier' => $dataStructureIdentifier,
                'fieldtype' => $type,
                'minchars' => (int)$minChars,
                'hmac' => $hmac,
            ]
        );

        return $this->view->render();
    }

    /**
     * Returns a new standalone view, shorthand function
     *
     * @param string $filename Which templateFile should be used.
     *
     * @return StandaloneView
     */
    protected function getFluidTemplateObject(string $filename = null):StandaloneView
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
