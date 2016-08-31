<?php
declare(strict_types=1);
namespace TYPO3\CMS\Form\Controller;

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

use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Lang\LanguageService;

/**
 * The form editor controller
 *
 * Scope: backend
 */
class FormEditorController extends AbstractBackendController
{

    /**
     * Default View Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @var array
     */
    protected $prototypeConfiguration;

    /**
     * Displays the form editor
     *
     * @param string $formPersistenceIdentifier
     * @param string $prototypeName
     * @return void
     * @throws PersistenceManagerException
     * @internal
     */
    public function indexAction(string $formPersistenceIdentifier, string $prototypeName = null)
    {
        $this->registerDocheaderButtons();
        $this->view->getModuleTemplate()->setModuleName($this->request->getPluginName() . '_' . $this->request->getControllerName());
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        if (
            strpos($formPersistenceIdentifier, 'EXT:') === 0
            && !$this->formSettings['persistenceManager']['allowSaveToExtensionPaths']
        ) {
            throw new PersistenceManagerException('Edit a extension formDefinition is not allowed.', 1478265661);
        }

        $formDefinition = $this->formPersistenceManager->load($formPersistenceIdentifier);
        $formDefinition = ArrayUtility::stripTagsFromValuesRecursive($formDefinition);
        if (empty($prototypeName)) {
            $prototypeName = isset($formDefinition['prototypeName']) ? $formDefinition['prototypeName'] : 'standard';
        }
        $formDefinition['prototypeName'] = $prototypeName;

        $configurationService = $this->objectManager->get(ConfigurationService::class);
        $this->prototypeConfiguration = $configurationService->getPrototypeConfiguration($prototypeName);

        $formEditorDefinitions = $this->getFormEditorDefinitions();

        $formEditorAppInitialData = [
            'formEditorDefinitions' => $formEditorDefinitions,
            'formDefinition' => $formDefinition,
            'formPersistenceIdentifier' => $formPersistenceIdentifier,
            'prototypeName' => $prototypeName,
            'endpoints' => [
                'formPageRenderer' => $this->controllerContext->getUriBuilder()->uriFor('renderFormPage'),
                'saveForm' => $this->controllerContext->getUriBuilder()->uriFor('saveForm')
            ],
            'additionalViewModelModules' => $this->prototypeConfiguration['formEditor']['dynamicRequireJsModules']['additionalViewModelModules'],
            'maximumUndoSteps' => $this->prototypeConfiguration['formEditor']['maximumUndoSteps'],
        ];

        $this->view->assign('formEditorAppInitialData', json_encode($formEditorAppInitialData));
        $this->view->assign('stylesheets', $this->resolveResourcePaths($this->prototypeConfiguration['formEditor']['stylesheets']));
        $this->view->assign('formEditorTemplates', $this->renderFormEditorTemplates(
            $this->prototypeConfiguration['formEditor']['formEditorTemplates'],
            $formEditorDefinitions
        ));
        $this->view->assign('dynamicRequireJsModules', $this->prototypeConfiguration['formEditor']['dynamicRequireJsModules']);

        $popupWindowWidth  = 700;
        $popupWindowHeight = 750;
        $popupWindowSize = ($this->getBackendUser()->getTSConfigVal('options.popupWindowSize'))
            ? trim($this->getBackendUser()->getTSConfigVal('options.popupWindowSize'))
            : null;
        if (!empty($popupWindowSize)) {
            list($popupWindowWidth, $popupWindowHeight) = GeneralUtility::intExplode('x', $popupWindowSize);
        }

        $addInlineSettings = [
            'FormEditor' => [
                'typo3WinBrowserUrl' => BackendUtility::getModuleUrl('wizard_element_browser'),
            ],
            'Popup' => [
                'PopupWindow' => [
                    'width' => $popupWindowWidth,
                    'height' => $popupWindowHeight
                ],
            ]
        ];

        $addInlineSettings = array_replace_recursive(
            $addInlineSettings,
            $this->prototypeConfiguration['formEditor']['addInlineSettings']
        );
        $this->view->assign('addInlineSettings', $addInlineSettings);
    }

    /**
     * Save a formDefinition which was build by the form editor.
     *
     * @param string $formPersistenceIdentifier
     * @param array $formDefinition
     * @return string
     * @internal
     */
    public function saveFormAction(string $formPersistenceIdentifier, array $formDefinition): string
    {
        $formDefinition = ArrayUtility::stripTagsFromValuesRecursive($formDefinition);
        $formDefinition = $this->convertJsonArrayToAssociativeArray($formDefinition);
        $this->formPersistenceManager->save($formPersistenceIdentifier, $formDefinition);
        return '';
    }

    /**
     * Render a page from the formDefinition which was build by the form editor.
     * Use the frontend rendering and set the form framework to preview mode.
     *
     * @param array $formDefinition
     * @param int $pageIndex
     * @param string $prototypeName
     * @return string
     * @internal
     */
    public function renderFormPageAction(array $formDefinition, int $pageIndex, string $prototypeName = null): string
    {
        $formDefinition = ArrayUtility::stripTagsFromValuesRecursive($formDefinition);
        $formDefinition = $this->convertJsonArrayToAssociativeArray($formDefinition);
        if (empty($prototypeName)) {
            $prototypeName = isset($formDefinition['prototypeName']) ? $formDefinition['prototypeName'] : 'standard';
        }

        $formFactory = $this->objectManager->get(ArrayFormFactory::class);
        $formDefinition = $formFactory->build($formDefinition, $prototypeName);
        $formDefinition->setRenderingOption('previewMode', true);
        $form = $formDefinition->bind($this->request, $this->response);
        $form->overrideCurrentPage($pageIndex);
        return $form->render();
    }

    /**
     * Prepare the formElements.*.formEditor section from the yaml settings.
     * Sort all formElements into groups and add additional data.
     *
     * @param array $formElementsDefinition
     * @return array
     */
    protected function getInsertRenderablesPanelConfiguration(array $formElementsDefinition): array
    {
        $formElementGroups = isset($this->prototypeConfiguration['formEditor']['formElementGroups']) ? $this->prototypeConfiguration['formEditor']['formElementGroups'] : [];
        $formElementsByGroup = [];

        foreach ($formElementsDefinition as $formElementName => $formElementConfiguration) {
            if (!isset($formElementConfiguration['group'])) {
                continue;
            }
            if (!isset($formElementsByGroup[$formElementConfiguration['group']])) {
                $formElementsByGroup[$formElementConfiguration['group']] = [];
            }

            $formElementsByGroup[$formElementConfiguration['group']][] = [
                'key' => $formElementName,
                'cssKey' => preg_replace('/[^a-z0-9]/', '-', strtolower($formElementName)),
                'label' => TranslationService::getInstance()->translate(
                    $formElementConfiguration['label'],
                    null,
                    $this->prototypeConfiguration['formEditor']['translationFile'],
                    null,
                    $formElementConfiguration['label']
                ),
                'sorting' => $formElementConfiguration['groupSorting'],
                'iconIdentifier' => $formElementConfiguration['iconIdentifier'],
            ];
        }

        $formGroups = [];
        foreach ($formElementGroups as $groupName => $groupConfiguration) {
            if (!isset($formElementsByGroup[$groupName])) {
                continue;
            }

            usort($formElementsByGroup[$groupName], function ($a, $b) {
                return $a['sorting'] - $b['sorting'];
            });
            unset($formElementsByGroup[$groupName]['sorting']);

            $formGroups[] = [
                'key' => $groupName,
                'elements' => $formElementsByGroup[$groupName],
                'label' => TranslationService::getInstance()->translate(
                    $groupConfiguration['label'],
                    null,
                    $this->prototypeConfiguration['formEditor']['translationFile'],
                    null,
                    $groupConfiguration['label']
                ),
            ];
        }

        return $formGroups;
    }

    /**
     * Reduce the Yaml settings by the 'formEditor' keyword.
     *
     * @return array
     */
    protected function getFormEditorDefinitions(): array
    {
        $formEditorDefinitions = [];
        foreach ([$this->prototypeConfiguration, $this->prototypeConfiguration['formEditor']] as $configuration) {
            foreach ($configuration as $firstLevelItemKey => $firstLevelItemValue) {
                if (substr($firstLevelItemKey, -10) !== 'Definition') {
                    continue;
                }
                $reducedKey = substr($firstLevelItemKey, 0, -10);
                foreach ($configuration[$firstLevelItemKey] as $formEditorDefinitionKey => $formEditorDefinitionValue) {
                    if (isset($formEditorDefinitionValue['formEditor'])) {
                        $formEditorDefinitionValue = array_intersect_key($formEditorDefinitionValue, array_flip(['formEditor']));
                        $formEditorDefinitions[$reducedKey][$formEditorDefinitionKey] = $formEditorDefinitionValue['formEditor'];
                    } else {
                        $formEditorDefinitions[$reducedKey][$formEditorDefinitionKey] = $formEditorDefinitionValue;
                    }
                }
            }
        }
        $formEditorDefinitions = ArrayUtility::reIndexNumericArrayKeysRecursive($formEditorDefinitions);
        $formEditorDefinitions = TranslationService::getInstance()->translateValuesRecursive(
            $formEditorDefinitions,
            $this->prototypeConfiguration['formEditor']['translationFile']
        );
        return $formEditorDefinitions;
    }

    /**
     * Registers the Icons into the docheader
     *
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $getVars = $this->request->getArguments();

        if (isset($getVars['action']) && $getVars['action'] === 'index') {
            $newPageButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['action' => 'formeditor-new-page', 'identifier' => 'headerNewPage'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.new_page_button'))
                ->setName('formeditor-new-page')
                ->setValue('new-page')
                ->setClasses('t3-form-element-new-page-button hidden')
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-page-new', Icon::SIZE_SMALL));

            $closeButton = $buttonBar->makeLinkButton()
                ->setDataAttributes(['identifier' => 'closeButton'])
                ->setHref(BackendUtility::getModuleUrl('web_FormFormbuilder'))
                ->setClasses('t3-form-element-close-form-button hidden')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-document-close', Icon::SIZE_SMALL));

            $saveButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['identifier' => 'saveButton'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.save_button'))
                ->setName('formeditor-save-form')
                ->setValue('save')
                ->setClasses('t3-form-element-save-form-button hidden')
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL))
                ->setShowLabelText(true);

            $undoButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['identifier' => 'undoButton'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.undo_button'))
                ->setName('formeditor-undo-form')
                ->setValue('undo')
                ->setClasses('t3-form-element-undo-form-button hidden disabled')
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));

            $redoButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['identifier' => 'redoButton'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.redo_button'))
                ->setName('formeditor-redo-form')
                ->setValue('redo')
                ->setClasses('t3-form-element-redo-form-button hidden disabled')
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-forward', Icon::SIZE_SMALL));

            $buttonBar->addButton($newPageButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
            $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
            $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
            $buttonBar->addButton($undoButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
            $buttonBar->addButton($redoButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
        }
    }

    /**
     * Some data which is build by the form editor needs a transformation before
     * it can be used by the framework.
     * Multivalue elements like select elements produce data like:
     *
     * [
     *   _label => 'label'
     *   _value => 'value'
     * ]
     *
     * This method transform this into:
     *
     * [
     *   'value' => 'label'
     * ]
     *
     * @param array $input
     * @return array
     */
    protected function convertJsonArrayToAssociativeArray(array $input): array
    {
        $output = [];
        foreach ($input as $key => $value) {
            if (is_integer($key) && is_array($value) && isset($value['_label']) && isset($value['_value'])) {
                $key = $value['_value'];
                $value = $value['_label'];
            }
            if (is_array($value)) {
                $output[$key] = $this->convertJsonArrayToAssociativeArray($value);
            } else {
                $output[$key] = $value;
            }
        }
        return $output;
    }

    /**
     * Render the "text/x-formeditor-template" templates.
     *
     * @param array $formEditorTemplates
     * @param array $formEditorDefinitions
     * @return array
     */
    protected function renderFormEditorTemplates(array $formEditorTemplates, array $formEditorDefinitions): array
    {
        if (
            !isset($formEditorTemplates['templateRootPaths'])
            || !is_array($formEditorTemplates['templateRootPaths'])
        ) {
            throw new RenderingException(
                'The option templateRootPaths must be set.',
                1480294720
            );
        }
        if (
            !isset($formEditorTemplates['layoutRootPaths'])
            || !is_array($formEditorTemplates['layoutRootPaths'])
        ) {
            throw new RenderingException(
                'The option layoutRootPaths must be set.',
                1480294721
            );
        }
        if (
            !isset($formEditorTemplates['partialRootPaths'])
            || !is_array($formEditorTemplates['partialRootPaths'])
        ) {
            throw new RenderingException(
                'The option partialRootPaths must be set.',
                1480294722
            );
        }

        $standaloneView = $this->objectManager->get(StandaloneView::class);
        $standaloneView->setTemplateRootPaths($formEditorTemplates['templateRootPaths']);
        $standaloneView->setLayoutRootPaths($formEditorTemplates['layoutRootPaths']);
        $standaloneView->setPartialRootPaths($formEditorTemplates['partialRootPaths']);
        $standaloneView->assignMultiple([
            'insertRenderablesPanelConfiguration' => $this->getInsertRenderablesPanelConfiguration($formEditorDefinitions['formElements'])
        ]);

        unset($formEditorTemplates['templateRootPaths']);
        unset($formEditorTemplates['layoutRootPaths']);
        unset($formEditorTemplates['partialRootPaths']);

        $renderedFormEditorTemplates = [];
        foreach ($formEditorTemplates as $formEditorTemplateName => $formEditorTemplateTemplate) {
            $renderedFormEditorTemplates[$formEditorTemplateName] = $standaloneView->render($formEditorTemplateTemplate);
        }

        return $renderedFormEditorTemplates;
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Returns the language service
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
