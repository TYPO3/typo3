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

namespace TYPO3\CMS\Form\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Fluid\View\TemplateView;
use TYPO3\CMS\Form\Domain\Configuration\ConfigurationService;
use TYPO3\CMS\Form\Domain\Configuration\FormDefinitionConversionService;
use TYPO3\CMS\Form\Domain\Exception\RenderingException;
use TYPO3\CMS\Form\Domain\Factory\ArrayFormFactory;
use TYPO3\CMS\Form\Exception;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Form\Type\FormDefinitionArray;

/**
 * The form editor controller
 *
 * Scope: backend
 * @internal
 */
class FormEditorController extends AbstractBackendController
{
    protected const JS_MODULE_NAMES = ['app', 'mediator', 'viewModel'];

    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected PageRenderer $pageRenderer;
    protected IconFactory $iconFactory;
    protected FormDefinitionConversionService $formDefinitionConversionService;

    /**
     * @var array
     */
    protected $prototypeConfiguration;

    public function __construct(
        ModuleTemplateFactory $moduleTemplateFactory,
        PageRenderer $pageRenderer,
        IconFactory $iconFactory,
        FormDefinitionConversionService $formDefinitionConversionService
    ) {
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->pageRenderer = $pageRenderer;
        $this->iconFactory = $iconFactory;
        $this->formDefinitionConversionService = $formDefinitionConversionService;
    }

    /**
     * Displays the form editor
     *
     * @param string $formPersistenceIdentifier
     * @param string|null $prototypeName
     * @return ResponseInterface
     * @throws PersistenceManagerException
     * @internal
     */
    public function indexAction(string $formPersistenceIdentifier, string $prototypeName = null): ResponseInterface
    {
        if (!$this->formPersistenceManager->isAllowedPersistencePath($formPersistenceIdentifier)) {
            throw new PersistenceManagerException(sprintf('Read "%s" is not allowed', $formPersistenceIdentifier), 1614500662);
        }

        if (
            PathUtility::isExtensionPath($formPersistenceIdentifier)
            && !$this->formSettings['persistenceManager']['allowSaveToExtensionPaths']
        ) {
            throw new PersistenceManagerException('Edit a extension formDefinition is not allowed.', 1478265661);
        }

        $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
        $formDefinition = $this->formPersistenceManager->load($formPersistenceIdentifier);

        if ($prototypeName === null) {
            $prototypeName = $formDefinition['prototypeName'] ?? 'standard';
        } else {
            // Loading a form definition with another prototype is currently not implemented but is planned in the future.
            // This safety check is a preventive measure.
            $selectablePrototypeNames = $configurationService->getSelectablePrototypeNamesDefinedInFormEditorSetup();
            if (!in_array($prototypeName, $selectablePrototypeNames, true)) {
                throw new Exception(sprintf('The prototype name "%s" is not configured within "formManager.selectablePrototypesConfiguration" ', $prototypeName), 1528625039);
            }
        }

        $formDefinition['prototypeName'] = $prototypeName;
        $this->prototypeConfiguration = $configurationService->getPrototypeConfiguration($prototypeName);

        $formDefinition = $this->transformFormDefinitionForFormEditor($formDefinition);
        $formEditorDefinitions = $this->getFormEditorDefinitions();

        $formEditorAppInitialData = [
            'formEditorDefinitions' => $formEditorDefinitions,
            'formDefinition' => $formDefinition,
            'formPersistenceIdentifier' => $formPersistenceIdentifier,
            'prototypeName' => $prototypeName,
            'endpoints' => [
                'formPageRenderer' => $this->uriBuilder->uriFor('renderFormPage'),
                'saveForm' => $this->uriBuilder->uriFor('saveForm'),
            ],
            'additionalViewModelModules' => $this->prototypeConfiguration['formEditor']['dynamicRequireJsModules']['additionalViewModelModules'] ?? [],
            'maximumUndoSteps' => $this->prototypeConfiguration['formEditor']['maximumUndoSteps'],
        ];

        $this->view->assign('formEditorTemplates', $this->renderFormEditorTemplates($formEditorDefinitions));
        $this->view->assign('dynamicRequireJsModules', $this->prototypeConfiguration['formEditor']['dynamicRequireJsModules']);

        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $addInlineSettings = [
            'FormEditor' => [
                'typo3WinBrowserUrl' => (string)$uriBuilder->buildUriFromRoute('wizard_element_browser'),
            ],
        ];

        $addInlineSettings = array_replace_recursive(
            $addInlineSettings,
            $this->prototypeConfiguration['formEditor']['addInlineSettings']
        );

        if (json_encode($formEditorAppInitialData) === false) {
            throw new Exception('The form editor app data could not be encoded', 1628677079);
        }

        $requireJsModules = array_filter(
            $this->prototypeConfiguration['formEditor']['dynamicRequireJsModules'],
            fn (string $name) => in_array($name, self::JS_MODULE_NAMES, true),
            ARRAY_FILTER_USE_KEY
        );
        $pageRenderer = $this->pageRenderer;
        $pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Form/Backend/Helper', 'Helper')
                ->invoke('dispatchFormEditor', $requireJsModules, $formEditorAppInitialData)
        );
        $pageRenderer->addInlineSettingArray(null, $addInlineSettings);
        $pageRenderer->addInlineLanguageLabelFile('EXT:form/Resources/Private/Language/locallang_formEditor_failSafeErrorHandling_javascript.xlf');
        $stylesheets = $this->resolveResourcePaths($this->prototypeConfiguration['formEditor']['stylesheets']);
        foreach ($stylesheets as $stylesheet) {
            $pageRenderer->addCssFile($stylesheet);
        }

        $moduleTemplate = $this->initializeModuleTemplate($this->request);
        $moduleTemplate->setModuleClass($this->request->getPluginName() . '_' . $this->request->getControllerName());
        $moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());
        $moduleTemplate->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/locallang_module.xlf:mlang_tabs_tab'),
            $formDefinition['label']
        );
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
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
     * @return ResponseInterface
     * @internal
     */
    public function saveFormAction(string $formPersistenceIdentifier, FormDefinitionArray $formDefinition): ResponseInterface
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

        $response = [
            'status' => 'success',
        ];

        try {
            if (!$this->formPersistenceManager->isAllowedPersistencePath($formPersistenceIdentifier)) {
                throw new PersistenceManagerException(sprintf('Save "%s" is not allowed', $formPersistenceIdentifier), 1614500663);
            }
            $this->formPersistenceManager->save($formPersistenceIdentifier, $formDefinition);
            $configurationService = GeneralUtility::makeInstance(ConfigurationService::class);
            $this->prototypeConfiguration = $configurationService->getPrototypeConfiguration($formDefinition['prototypeName']);
            $formDefinition = $this->transformFormDefinitionForFormEditor($formDefinition);
            $response['formDefinition'] = $formDefinition;
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

        return $this->jsonResponse();
    }

    /**
     * Render a page from the formDefinition which was build by the form editor.
     * Use the frontend rendering and set the form framework to preview mode.
     *
     * @param FormDefinitionArray $formDefinition
     * @param int $pageIndex
     * @param string $prototypeName
     * @return ResponseInterface
     * @internal
     */
    public function renderFormPageAction(
        FormDefinitionArray $formDefinition,
        int $pageIndex,
        string $prototypeName = null
    ): ResponseInterface {
        $prototypeName = $prototypeName ?: $formDefinition['prototypeName'] ?? 'standard';
        $formDefinition = $formDefinition->getArrayCopy();

        $formFactory = GeneralUtility::makeInstance(ArrayFormFactory::class);
        $formDefinition = $formFactory->build($formDefinition, $prototypeName);
        $formDefinition->setRenderingOption('previewMode', true);
        $form = $formDefinition->bind($this->request);
        $form->setCurrentSiteLanguage($this->buildFakeSiteLanguage(0, 0));
        $form->overrideCurrentPage($pageIndex);

        return $this->htmlResponse($form->render());
    }

    /**
     * Build a SiteLanguage object to render the form preview with a
     * specific language.
     *
     * @param int $pageId
     * @param int $languageId
     * @return SiteLanguage
     */
    protected function buildFakeSiteLanguage(int $pageId, int $languageId): SiteLanguage
    {
        $fakeSiteConfiguration = [
            'languages' => [
                [
                    'languageId' => $languageId,
                    'title' => 'Dummy',
                    'navigationTitle' => '',
                    'typo3Language' => '',
                    'flag' => '',
                    'locale' => '',
                    'iso-639-1' => '',
                    'hreflang' => '',
                    'direction' => '',
                ],
            ],
        ];

        $currentSiteLanguage = GeneralUtility::makeInstance(Site::class, 'form-dummy', $pageId, $fakeSiteConfiguration)
            ->getLanguageById($languageId);
        return $currentSiteLanguage;
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
        /** @var array<string, array<string, string>> $formElementsByGroup */
        $formElementsByGroup = [];

        foreach ($formElementsDefinition as $formElementName => $formElementConfiguration) {
            if (!isset($formElementConfiguration['group'])) {
                continue;
            }
            if (!isset($formElementsByGroup[$formElementConfiguration['group']])) {
                $formElementsByGroup[$formElementConfiguration['group']] = [];
            }

            $formElementConfiguration = GeneralUtility::makeInstance(TranslationService::class)->translateValuesRecursive(
                $formElementConfiguration,
                $this->prototypeConfiguration['formEditor']['translationFiles'] ?? []
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

            usort($formElementsByGroup[$groupName], static function ($a, $b) {
                return $a['sorting'] - $b['sorting'];
            });
            unset($formElementsByGroup[$groupName]['sorting']);

            $groupConfiguration = GeneralUtility::makeInstance(TranslationService::class)->translateValuesRecursive(
                $groupConfiguration,
                $this->prototypeConfiguration['formEditor']['translationFiles'] ?? []
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
        $formEditorDefinitions = GeneralUtility::makeInstance(TranslationService::class)->translateValuesRecursive(
            $formEditorDefinitions,
            $this->prototypeConfiguration['formEditor']['translationFiles'] ?? []
        );
        return $formEditorDefinitions;
    }

    /**
     * Initialize ModuleTemplate and register docheader icons.
     */
    protected function initializeModuleTemplate(ServerRequestInterface $request): ModuleTemplate
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);

        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $getVars = $request->getArguments();

        if (isset($getVars['action']) && $getVars['action'] === 'index') {
            $newPageButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['action' => 'formeditor-new-page', 'identifier' => 'headerNewPage'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.new_page_button'))
                ->setName('formeditor-new-page')
                ->setValue('new-page')
                ->setClasses('t3-form-element-new-page-button hidden')
                ->setIcon($this->iconFactory->getIcon('actions-page-new', Icon::SIZE_SMALL));

            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

            $closeButton = $buttonBar->makeLinkButton()
                ->setDataAttributes(['identifier' => 'closeButton'])
                ->setHref((string)$uriBuilder->buildUriFromRoute('web_FormFormbuilder'))
                ->setClasses('t3-form-element-close-form-button hidden')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:rm.closeDoc'))
                ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL));

            $saveButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['identifier' => 'saveButton'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.save_button'))
                ->setName('formeditor-save-form')
                ->setValue('save')
                ->setClasses('t3-form-element-save-form-button hidden')
                ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL))
                ->setShowLabelText(true);

            $formSettingsButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['identifier' => 'formSettingsButton'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.form_settings_button'))
                ->setName('formeditor-form-settings')
                ->setValue('settings')
                ->setClasses('t3-form-element-form-settings-button hidden')
                ->setIcon($this->iconFactory->getIcon('actions-system-extension-configure', Icon::SIZE_SMALL))
                ->setShowLabelText(true);

            $undoButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['identifier' => 'undoButton'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.undo_button'))
                ->setName('formeditor-undo-form')
                ->setValue('undo')
                ->setClasses('t3-form-element-undo-form-button hidden disabled')
                ->setIcon($this->iconFactory->getIcon('actions-edit-undo', Icon::SIZE_SMALL));

            $redoButton = $buttonBar->makeInputButton()
                ->setDataAttributes(['identifier' => 'redoButton'])
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formEditor.redo_button'))
                ->setName('formeditor-redo-form')
                ->setValue('redo')
                ->setClasses('t3-form-element-redo-form-button hidden disabled')
                ->setIcon($this->iconFactory->getIcon('actions-edit-redo', Icon::SIZE_SMALL));

            $buttonBar->addButton($newPageButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
            $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
            $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
            $buttonBar->addButton($formSettingsButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
            $buttonBar->addButton($undoButton, ButtonBar::BUTTON_POSITION_LEFT, 5);
            $buttonBar->addButton($redoButton, ButtonBar::BUTTON_POSITION_LEFT, 5);
        }

        return $moduleTemplate;
    }

    /**
     * Render the "text/x-formeditor-template" templates.
     *
     * @param array $formEditorDefinitions
     * @return string
     */
    protected function renderFormEditorTemplates(array $formEditorDefinitions): string
    {
        $fluidConfiguration = $this->prototypeConfiguration['formEditor']['formEditorFluidConfiguration'] ?? null;
        $formEditorPartials = $this->prototypeConfiguration['formEditor']['formEditorPartials'] ?? null;

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

        $view = GeneralUtility::makeInstance(TemplateView::class);
        // @deprecated since v11, will be removed with v12.
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
     * @todo move this to FormDefinitionConversionService
     * @param array $formDefinition
     * @return array
     */
    protected function transformFormDefinitionForFormEditor(array $formDefinition): array
    {
        $multiValueFormElementProperties = [];
        $multiValueFinisherProperties = [];

        foreach ($this->prototypeConfiguration['formElementsDefinition'] as $type => $configuration) {
            if (!isset($configuration['formEditor']['editors'])) {
                continue;
            }
            foreach ($configuration['formEditor']['editors'] as $editorConfiguration) {
                if ($editorConfiguration['templateName'] === 'Inspector-PropertyGridEditor') {
                    $multiValueFormElementProperties[$type][] = $editorConfiguration['propertyPath'];
                }
            }
        }

        foreach ($this->prototypeConfiguration['formElementsDefinition']['Form']['formEditor']['propertyCollections']['finishers'] ?? [] as $configuration) {
            if (!isset($configuration['editors'])) {
                continue;
            }

            foreach ($configuration['editors'] as $editorConfiguration) {
                if ($editorConfiguration['templateName'] === 'Inspector-PropertyGridEditor') {
                    $multiValueFinisherProperties[$configuration['identifier']][] = $editorConfiguration['propertyPath'];
                }
            }
        }

        $formDefinition = $this->filterEmptyArrays($formDefinition);
        $formDefinition = $this->migrateEmailFinisherRecipients($formDefinition);

        // @todo: replace with rte parsing
        $formDefinition = ArrayUtility::stripTagsFromValuesRecursive($formDefinition);
        $formDefinition = $this->transformMultiValuePropertiesForFormEditor(
            $formDefinition,
            'type',
            $multiValueFormElementProperties
        );
        $formDefinition = $this->transformMultiValuePropertiesForFormEditor(
            $formDefinition,
            'identifier',
            $multiValueFinisherProperties
        );

        $formDefinition = $this->formDefinitionConversionService->addHmacData($formDefinition);
        $formDefinition = $this->formDefinitionConversionService->migrateFinisherConfiguration($formDefinition);

        return $formDefinition;
    }

    /**
     * Some data needs a transformation before it can be used by the
     * form editor. This rules for multivalue elements like select
     * elements. To ensure the right sorting if the data goes into
     * javascript, we need to do transformations:
     *
     * [
     *   '5' => '5',
     *   '4' => '4',
     *   '3' => '3'
     * ]
     *
     *
     * This method transform this into:
     *
     * [
     *   [
     *     _label => '5'
     *     _value => 5
     *   ],
     *   [
     *     _label => '4'
     *     _value => 4
     *   ],
     *   [
     *     _label => '3'
     *     _value => 3
     *   ],
     * ]
     *
     * @param array $formDefinition
     * @param string $identifierProperty
     * @param array $multiValueProperties
     * @return array
     */
    protected function transformMultiValuePropertiesForFormEditor(
        array $formDefinition,
        string $identifierProperty,
        array $multiValueProperties
    ): array {
        $output = $formDefinition;
        foreach ($formDefinition as $key => $value) {
            $identifier = $value[$identifierProperty] ?? null;

            if (array_key_exists($identifier, $multiValueProperties)) {
                $multiValuePropertiesForIdentifier = $multiValueProperties[$identifier];

                foreach ($multiValuePropertiesForIdentifier as $multiValueProperty) {
                    if (!ArrayUtility::isValidPath($value, $multiValueProperty, '.')) {
                        continue;
                    }

                    $multiValuePropertyData = ArrayUtility::getValueByPath($value, $multiValueProperty, '.');

                    if (!is_array($multiValuePropertyData)) {
                        continue;
                    }

                    $newMultiValuePropertyData = [];

                    foreach ($multiValuePropertyData as $k => $v) {
                        $newMultiValuePropertyData[] = [
                            '_label' => $v,
                            '_value' => $k,
                        ];
                    }

                    $value = ArrayUtility::setValueByPath($value, $multiValueProperty, $newMultiValuePropertyData, '.');
                }
            }

            $output[$key] = $value;

            if (is_array($value)) {
                $output[$key] = $this->transformMultiValuePropertiesForFormEditor(
                    $value,
                    $identifierProperty,
                    $multiValueProperties
                );
            }
        }

        return $output;
    }

    /**
     * Remove keys from an array if the key value is an empty array
     *
     * @param array $array
     * @return array
     */
    protected function filterEmptyArrays(array $array): array
    {
        foreach ($array as $key => $value) {
            if (!is_array($value)) {
                continue;
            }
            if (empty($value)) {
                unset($array[$key]);
                continue;
            }
            $array[$key] = $this->filterEmptyArrays($value);
            if (empty($array[$key])) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * Migrate single recipient options to their list successors
     *
     * @param array $formDefinition
     * @return array
     */
    protected function migrateEmailFinisherRecipients(array $formDefinition): array
    {
        foreach ($formDefinition['finishers'] ?? [] as $i => $finisherConfiguration) {
            if (!in_array($finisherConfiguration['identifier'], ['EmailToSender', 'EmailToReceiver'], true)) {
                continue;
            }

            $recipientAddress = $finisherConfiguration['options']['recipientAddress'] ?? '';
            $recipientName = $finisherConfiguration['options']['recipientName'] ?? '';
            $carbonCopyAddress = $finisherConfiguration['options']['carbonCopyAddress'] ?? '';
            $blindCarbonCopyAddress = $finisherConfiguration['options']['blindCarbonCopyAddress'] ?? '';
            $replyToAddress = $finisherConfiguration['options']['replyToAddress'] ?? '';

            if (!empty($recipientAddress)) {
                $finisherConfiguration['options']['recipients'][$recipientAddress] = $recipientName;
            }

            if (!empty($carbonCopyAddress)) {
                $finisherConfiguration['options']['carbonCopyRecipients'][$carbonCopyAddress] = '';
            }

            if (!empty($blindCarbonCopyAddress)) {
                $finisherConfiguration['options']['blindCarbonCopyRecipients'][$blindCarbonCopyAddress] = '';
            }

            if (!empty($replyToAddress)) {
                $finisherConfiguration['options']['replyToRecipients'][$replyToAddress] = '';
            }

            unset(
                $finisherConfiguration['options']['recipientAddress'],
                $finisherConfiguration['options']['recipientName'],
                $finisherConfiguration['options']['carbonCopyAddress'],
                $finisherConfiguration['options']['blindCarbonCopyAddress'],
                $finisherConfiguration['options']['replyToAddress']
            );
            $formDefinition['finishers'][$i] = $finisherConfiguration;
        }

        return $formDefinition;
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
