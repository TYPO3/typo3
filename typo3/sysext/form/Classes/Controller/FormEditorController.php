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
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Loader\FalYamlFileLoader;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader\Configuration;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Exception\InvalidFormDefinitionException;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Form\Type\FormDefinitionArray;

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

        $prototypeName = $prototypeName ?? $formDefinition['prototypeName'] ?? 'standard';
        /** @var \TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader\Configuration */
        $configuration = GeneralUtility::makeInstance(Configuration::class)
            ->setRemoveImportsProperty(false)
            ->setMergeLists(false);
        $formDefinition = $this->formPersistenceManager->load($formPersistenceIdentifier, $configuration);
        $formDefinition = ArrayUtility::stripTagsFromValuesRecursive($formDefinition);
        $formDefinition = $this->transformFormDefinitionWithImportsForFormEditor($formDefinition, is_array($formDefinition['imports']));
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
        $this->view->assign('formEditorTemplates', $this->renderFormEditorTemplates($formEditorDefinitions));
        $this->view->assign('dynamicRequireJsModules', $this->prototypeConfiguration['formEditor']['dynamicRequireJsModules']);

        $popupWindowWidth  = 700;
        $popupWindowHeight = 750;
        $popupWindowSize = ($this->getBackendUser()->getTSConfigVal('options.popupWindowSize'))
            ? trim($this->getBackendUser()->getTSConfigVal('options.popupWindowSize'))
            : null;
        if (!empty($popupWindowSize)) {
            list($popupWindowWidth, $popupWindowHeight) = GeneralUtility::intExplode('x', $popupWindowSize);
        }
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $addInlineSettings = [
            'FormEditor' => [
                'typo3WinBrowserUrl' => (string)$uriBuilder->buildUriFromRoute('wizard_element_browser'),
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
     * Initialize the save action.
     * This action uses the Fluid JsonView::class as view.
     *
     * @internal
     */
    public function initializeSaveFormAction()
    {
        $this->defaultViewObjectName = JsonView::class;
    }

    /**
     * Save a formDefinition which was build by the form editor.
     *
     * @param string $formPersistenceIdentifier
     * @param FormDefinitionArray $formDefinition
     * @internal
     */
    public function saveFormAction(string $formPersistenceIdentifier, FormDefinitionArray $formDefinition)
    {
        $formDefinition = $formDefinition->getArrayCopy();

        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormSave'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'beforeFormSave')) {
                $formDefinition = $hookObj->beforeFormSave(
                    $formPersistenceIdentifier,
                    $formDefinition
                );
            }
        }

        $formDefinition = $this->transformFormDefinitionWithImportsForFormFramework(
            $formPersistenceIdentifier,
            $formDefinition,
            is_array($formDefinition['imports'])
        );

        $response = [
            'status' => 'success',
        ];

        try {
            $this->formPersistenceManager->save($formPersistenceIdentifier, $formDefinition);
        } catch (PersistenceManagerException $e) {
            $response = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }

        $this->view->assign('response', $response);
        // saveFormAction uses the extbase JsonView::class.
        // That's why we have to set the view variables in this way.
        $this->view->setVariablesToRender([
            'response',
        ]);
    }

    /**
     * Render a page from the formDefinition which was build by the form editor.
     * Use the frontend rendering and set the form framework to preview mode.
     *
     * @param FormDefinitionArray $formDefinition
     * @param int $pageIndex
     * @param string $prototypeName
     * @return string
     * @internal
     */
    public function renderFormPageAction(FormDefinitionArray $formDefinition, int $pageIndex, string $prototypeName = null): string
    {
        $prototypeName = $prototypeName ?? $formDefinition['prototypeName'] ?? 'standard';
        $formFactory = $this->objectManager->get(ArrayFormFactory::class);
        $formDefinition = $formFactory->build($formDefinition->getArrayCopy(), $prototypeName);
        $formDefinition->setRenderingOption('previewMode', true);
        $form = $formDefinition->bind($this->request, $this->response);
        $form->overrideCurrentPage($pageIndex);

        return $form->render();
    }

    /**
     * Prepare the formElements.*.formEditor section from the YAML settings.
     * Sort all formElements into groups and add additional data.
     *
     * @param array $formElementsDefinition
     * @return array
     */
    protected function getInsertRenderablesPanelConfiguration(array $formElementsDefinition): array
    {
        $formElementsByGroup = [];

        foreach ($formElementsDefinition as $formElementName => $formElementConfiguration) {
            if (!isset($formElementConfiguration['group'])) {
                continue;
            }
            if (!isset($formElementsByGroup[$formElementConfiguration['group']])) {
                $formElementsByGroup[$formElementConfiguration['group']] = [];
            }

            $formElementConfiguration = TranslationService::getInstance()->translateValuesRecursive(
                $formElementConfiguration,
                $this->prototypeConfiguration['formEditor']['translationFile']
            );

            $formElementsByGroup[$formElementConfiguration['group']][] = [
                'key' => $formElementName,
                'cssKey' => preg_replace('/[^a-z0-9]/', '-', strtolower($formElementName)),
                'label' => $formElementConfiguration['label'],
                'sorting' => $formElementConfiguration['groupSorting'],
                'iconIdentifier' => $formElementConfiguration['iconIdentifier'],
            ];
        }

        $formGroups = [];
        foreach ($this->prototypeConfiguration['formEditor']['formElementGroups'] ?? [] as $groupName => $groupConfiguration) {
            if (!isset($formElementsByGroup[$groupName])) {
                continue;
            }

            usort($formElementsByGroup[$groupName], function ($a, $b) {
                return $a['sorting'] - $b['sorting'];
            });
            unset($formElementsByGroup[$groupName]['sorting']);

            $groupConfiguration = TranslationService::getInstance()->translateValuesRecursive(
                $groupConfiguration,
                $this->prototypeConfiguration['formEditor']['translationFile']
            );

            $formGroups[] = [
                'key' => $groupName,
                'elements' => $formElementsByGroup[$groupName],
                'label' => $groupConfiguration['label'],
            ];
        }

        return $formGroups;
    }

    /**
     * Reduce the YAML settings by the 'formEditor' keyword.
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
            /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
            $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);

            $closeButton = $buttonBar->makeLinkButton()
                ->setDataAttributes(['identifier' => 'closeButton'])
                ->setHref((string)$uriBuilder->buildUriFromRoute('web_FormFormbuilder'))
                ->setClasses('t3-form-element-close-form-button hidden')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-close', Icon::SIZE_SMALL));

            $saveButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['identifier' => 'saveButton'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.save_button'))
                ->setName('formeditor-save-form')
                ->setValue('save')
                ->setClasses('t3-form-element-save-form-button hidden')
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL))
                ->setShowLabelText(true);

            $formSettingsButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['identifier' => 'formSettingsButton'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.form_settings_button'))
                ->setName('formeditor-form-settings')
                ->setValue('settings')
                ->setClasses('t3-form-element-form-settings-button hidden')
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-system-extension-configure', Icon::SIZE_SMALL))
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
            $buttonBar->addButton($formSettingsButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
            $buttonBar->addButton($undoButton, ButtonBar::BUTTON_POSITION_LEFT, 5);
            $buttonBar->addButton($redoButton, ButtonBar::BUTTON_POSITION_LEFT, 5);
        }
    }

    /**
     * @param array $array
     * @param bool $hasImports
     * @return array
     * @throws PropertyException
     */
    protected function transformFormDefinitionWithImportsForFormEditor(array $array, bool $hasImports): array
    {
        $result = $array;
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                if (
                    $key === 'renderables'
                    || $key === 'validators'
                    || $key === 'finishers'
                ) {
                    if ($hasImports) {
                        foreach ($value as $itemKey => $item) {
                            if (is_int($itemKey)) {
                                throw new InvalidFormDefinitionException(
                                    'All array keys within "' . $key . '" must be strings.',
                                    1505505524
                                );
                            }

                            if ($itemKey !== $item['identifier']) {
                                throw new InvalidFormDefinitionException(
                                    'All items keys within "' . $key . '" must be equal to the "identifier" property.',
                                    1505505525
                                );
                            }

                            if (!isset($item['sorting'])) {
                                throw new InvalidFormDefinitionException(
                                    'All items within "' . $key . '" must have a "sorting" property.',
                                    1505505526
                                );
                            }
                        }
                    }
                    // transform string keys to integer keys
                    $value = array_values($value);

                    if ($hasImports) {
                        // sort by "sorting"
                        usort($value, function ($a, $b) {
                            return (float)$a['sorting'] - (float)$b['sorting'];
                        });
                    }
                }
                $result[$key] = $this->transformFormDefinitionWithImportsForFormEditor($value, $hasImports);
            }
        }
        return $result;
    }

    /**
     * @param string $formPersistenceIdentifier
     * @param array $formDefinition
     * @param bool $hasImports
     * @return array
     */
    protected function transformFormDefinitionWithImportsForFormFramework(
        string $formPersistenceIdentifier,
        array $formDefinition,
        bool $hasImports
    ): array {
        if ($hasImports) {
            $fakeYaml = $this->generateFakeYamlFromImports(
                $formDefinition['imports']
            );
            /** @var \TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader\Configuration */
            $configuration = GeneralUtility::makeInstance(Configuration::class)
                ->setMergeLists(false);
            $importsFormDefinition = $this->objectManager->get(FalYamlFileLoader::class, $configuration)
                ->loadFromContent($fakeYaml);
            $importsFormDefinition = $this->castValuesToNumbers($importsFormDefinition);
        }

        $formDefinition = $this->castValuesToNumbers($formDefinition);
        $formDefinition = $this->setIdentifiersAsKeys($formDefinition);
        $formDefinition = $this->setNewSortings($formDefinition);

        if ($hasImports) {
            $this->makeFormDefinitionWithImportsDiff($formDefinition, $importsFormDefinition);
        }
        return $formDefinition;
    }

    /**
     * @param array $array
     * @return array
     */
    public static function castValuesToNumbers(array $array): array
    {
        $result = $array;
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                $result[$key] = self::castValuesToNumbers($value);
            } elseif (MathUtility::canBeInterpretedAsInteger($value)) {
                $result[$key] = (int)$value;
            } elseif (MathUtility::canBeInterpretedAsFloat($value)) {
                $result[$key] = (float)$value;
            }
        }
        return $result;
    }

    /**
     * @param array $formDefinition
     * @return array
     */
    protected function setNewSortings(array $formDefinition): array
    {
        $result = $formDefinition;

        foreach ($result as $key => $value) {
            if (is_array($value)) {
                if (
                    $key === 'renderables'
                    || $key === 'validators'
                    || $key === 'finishers'
                ) {
                    $sorting = 10;
                    foreach ($value as $identifier => $item) {
                        $value[$identifier]['sorting'] = $sorting;
                        $sorting += 10;
                    }
                }
                $result[$key] = $this->setNewSortings($value);
            }
        }
        return $result;
    }

    /**
     * @param array $array
     * @return array
     */
    protected function setIdentifiersAsKeys(array $array): array
    {
        $result = $array;
        foreach ($result as $key => $value) {
            if (is_array($value)) {
                if (
                    $key === 'renderables'
                    || $key === 'validators'
                    || $key === 'finishers'
                ) {
                    $newValue = [];
                    foreach ($value as $itemKey => $item) {
                        $newValue[$item['identifier']] = $item;
                    }
                    $value = $newValue;
                }
                $result[$key] = $this->setIdentifiersAsKeys($value);
            }
        }
        return $result;
    }

    /**
     * @param array $imports
     * @return string
     */
    protected function generateFakeYamlFromImports(array $imports): string
    {
        $fakeYaml = 'imports:' . LF;
        foreach ($imports as $import) {
            foreach ($import as $resource) {
                $fakeYaml .= '  - { resource: "' . $resource . '" }' . LF;
            }
        }
        return $fakeYaml;
    }

    /**
     * @param array &$newFullFormDefinition
     * @param array $importsFormDefinition
     * @param array $path
     */
    protected function makeFormDefinitionWithImportsDiff(
        array &$newFullFormDefinition,
        array $importsFormDefinition,
        array $path = []
    ) {
        foreach ($importsFormDefinition as $key => $valueFromImportsFormDefinition) {
            $currentPath = $path;
            $currentPath[] = $key;
            $currentPathString = implode('/', $currentPath);
            if (is_array($valueFromImportsFormDefinition)) {
                if (!ArrayUtility::isValidPath($newFullFormDefinition, $currentPathString)) {
                    // Overwrite the value within the new formDefinition with null
                    // because the value exists within one of the imports
                    // but not within the new formDefinition which means
                    // that the value should be deleted.
                    $newFullFormDefinition = ArrayUtility::setValueByPath($newFullFormDefinition, $currentPathString, null);
                } else {
                    $this->makeFormDefinitionWithImportsDiff($newFullFormDefinition, $valueFromImportsFormDefinition, $currentPath);
                    $value = ArrayUtility::getValueByPath($newFullFormDefinition, $currentPathString);
                    // If values are deleted within deeper nestings, the array
                    // keys still exists. If empty arrays exists within the new formDefinition
                    // then they should be removed.
                    if (is_array($value) && empty($value)) {
                        $newFullFormDefinition = ArrayUtility::removeByPath($newFullFormDefinition, $currentPathString);
                    }
                }
            } else {
                if (
                    ArrayUtility::isValidPath($newFullFormDefinition, $currentPathString)
                    && ArrayUtility::getValueByPath($newFullFormDefinition, $currentPathString) === $valueFromImportsFormDefinition
                ) {
                    // Remove the value within the new formDefinition
                    // because the value already exists within one of the imports.
                    $newFullFormDefinition = ArrayUtility::removeByPath($newFullFormDefinition, $currentPathString);
                } elseif (!ArrayUtility::isValidPath($newFullFormDefinition, $currentPathString)) {
                    // Overwrite the value within the new formDefinition with null
                    // because the value exists within one of the imports
                    // but not within the new formDefinition which means
                    // that the value should be deleted.
                    $newFullFormDefinition = ArrayUtility::setValueByPath($newFullFormDefinition, $currentPathString, null);
                }
            }
        }
    }

    /**
     * Render the "text/x-formeditor-template" templates.
     *
     * @param array $formEditorDefinitions
     * @return string
     */
    protected function renderFormEditorTemplates(array $formEditorDefinitions): string
    {
        $fluidConfiguration = $this->prototypeConfiguration['formEditor']['formEditorFluidConfiguration'];
        $formEditorPartials = $this->prototypeConfiguration['formEditor']['formEditorPartials'];

        if (!isset($fluidConfiguration['templatePathAndFilename'])) {
            throw new RenderingException(
                'The option templatePathAndFilename must be set.',
                1485636499
            );
        }
        if (
            !isset($fluidConfiguration['layoutRootPaths'])
            || !is_array($fluidConfiguration['layoutRootPaths'])
        ) {
            throw new RenderingException(
                'The option layoutRootPaths must be set.',
                1480294721
            );
        }
        if (
            !isset($fluidConfiguration['partialRootPaths'])
            || !is_array($fluidConfiguration['partialRootPaths'])
        ) {
            throw new RenderingException(
                'The option partialRootPaths must be set.',
                1480294722
            );
        }

        $insertRenderablesPanelConfiguration = $this->getInsertRenderablesPanelConfiguration($formEditorDefinitions['formElements']);

        $view = $this->objectManager->get(TemplateView::class);
        $view->setControllerContext(clone $this->controllerContext);
        $view->getRenderingContext()->getTemplatePaths()->fillFromConfigurationArray($fluidConfiguration);
        $view->setTemplatePathAndFilename($fluidConfiguration['templatePathAndFilename']);
        $view->assignMultiple([
            'insertRenderablesPanelConfiguration' => $insertRenderablesPanelConfiguration,
            'formEditorPartials' => $formEditorPartials,
        ]);

        return $view->render();
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
