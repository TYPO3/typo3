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
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Http\AllowedMethodsTrait;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Pagination\ArrayPaginator;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Form\Exception as FormException;
use TYPO3\CMS\Form\Mvc\Configuration\ConfigurationManagerInterface as ExtFormConfigurationManagerInterface;
use TYPO3\CMS\Form\Mvc\Persistence\Exception\PersistenceManagerException;
use TYPO3\CMS\Form\Mvc\Persistence\FormPersistenceManagerInterface;
use TYPO3\CMS\Form\Service\DatabaseService;
use TYPO3\CMS\Form\Service\TranslationService;

/**
 * The form manager controller
 *
 * Scope: backend
 * @internal
 */
class FormManagerController extends ActionController
{
    use AllowedMethodsTrait;

    protected const JS_MODULE_NAMES = ['app', 'viewModel'];
    protected const PAGINATION_MAX = 20;

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly IconFactory $iconFactory,
        protected readonly DatabaseService $databaseService,
        protected readonly FormPersistenceManagerInterface $formPersistenceManager,
        protected readonly ExtFormConfigurationManagerInterface $extFormConfigurationManager,
        protected readonly TranslationService $translationService,
        protected readonly CharsetConverter $charsetConverter,
        protected readonly UriBuilder $coreUriBuilder,
    ) {}

    /**
     * Display the Form Manager. The main showing available forms.
     */
    protected function indexAction(int $page = 1, string $searchTerm = ''): ResponseInterface
    {
        $formSettings = $this->getFormSettings();
        $hasForms = $this->formPersistenceManager->hasForms($formSettings);
        $forms = $hasForms ? $this->getAvailableFormDefinitions($formSettings, trim($searchTerm)) : [];
        $arrayPaginator = new ArrayPaginator($forms, $page, self::PAGINATION_MAX);
        $pagination = new SimplePagination($arrayPaginator);
        $moduleTemplate = $this->initializeModuleTemplate($this->request, $page, $searchTerm);
        $moduleTemplate->assignMultiple([
            'paginator' => $arrayPaginator,
            'pagination' => $pagination,
            'searchTerm' => $searchTerm,
            'hasForms' => $hasForms,
            'stylesheets' => $formSettings['formManager']['stylesheets'],
            'formManagerAppInitialData' => json_encode($this->getFormManagerAppInitialData($formSettings)),
        ]);
        if (!empty($formSettings['formManager']['javaScriptTranslationFile'])) {
            $this->pageRenderer->addInlineLanguageLabelFile($formSettings['formManager']['javaScriptTranslationFile']);
        }
        $javaScriptModules = array_map(
            static fn(string $name) => JavaScriptModuleInstruction::create($name),
            array_filter(
                $formSettings['formManager']['dynamicJavaScriptModules'] ?? [],
                fn(string $name) => in_array($name, self::JS_MODULE_NAMES, true),
                ARRAY_FILTER_USE_KEY
            )
        );
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/form/backend/helper.js', 'Helper')
                ->invoke('dispatchFormManager', $javaScriptModules, $this->getFormManagerAppInitialData($formSettings))
        );
        array_map($this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(...), $javaScriptModules);
        $moduleTemplate->setModuleClass($this->request->getPluginName() . '_' . $this->request->getControllerName());
        $moduleTemplate->setFlashMessageQueue($this->getFlashMessageQueue());
        $moduleTemplate->setTitle(
            $this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/locallang_module.xlf:mlang_tabs_tab')
        );
        return $moduleTemplate->renderResponse('Backend/FormManager/Index');
    }

    /**
     * Initialize the "create" action.
     * This action uses the Fluid JsonView::class as view.
     */
    protected function initializeCreateAction(): void
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');
        $this->defaultViewObjectName = JsonView::class;
    }

    /**
     * Creates a new Form and redirects to the Form Editor
     *
     * @throws FormException
     * @throws PersistenceManagerException
     */
    protected function createAction(string $formName, string $templatePath, string $prototypeName, string $savePath): ResponseInterface
    {
        $formSettings = $this->getFormSettings();
        if (!$this->formPersistenceManager->isAllowedPersistencePath($savePath, $formSettings)) {
            throw new PersistenceManagerException(sprintf('Save to path "%s" is not allowed', $savePath), 1614500657);
        }
        if (!$this->isValidTemplatePath($formSettings, $prototypeName, $templatePath)) {
            throw new FormException(sprintf('The template path "%s" is not allowed', $templatePath), 1329233410);
        }
        if (empty($formName)) {
            throw new FormException('No form name', 1472312204);
        }
        $templatePath = GeneralUtility::getFileAbsFileName($templatePath);
        $form = Yaml::parse((string)file_get_contents($templatePath));
        $form['label'] = $formName;
        $form['identifier'] = $this->formPersistenceManager->getUniqueIdentifier($formSettings, $this->convertFormNameToIdentifier($formName));
        $form['prototypeName'] = $prototypeName;
        $formPersistenceIdentifier = $this->formPersistenceManager->getUniquePersistenceIdentifier($form['identifier'], $savePath, $formSettings);
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormCreate'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'beforeFormCreate')) {
                $form = $hookObj->beforeFormCreate(
                    $formPersistenceIdentifier,
                    $form
                );
            }
        }
        $response = [
            'status' => 'success',
            'url' => $this->uriBuilder->uriFor('index', ['formPersistenceIdentifier' => $formPersistenceIdentifier], 'FormEditor'),
        ];
        $form = ArrayUtility::stripTagsFromValuesRecursive($form);
        try {
            $this->formPersistenceManager->save($formPersistenceIdentifier, $form, $formSettings);
        } catch (PersistenceManagerException $e) {
            $response = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
        // createAction uses the Extbase JsonView::class.
        // That's why we have to set the view variables in this way.
        /** @var JsonView $view */
        $view = $this->view;
        $view->assign('response', $response);
        $view->setVariablesToRender([
            'response',
        ]);
        return $this->jsonResponse();
    }

    /**
     * Initialize the duplicate action.
     * This action uses the Fluid JsonView::class as view.
     */
    protected function initializeDuplicateAction(): void
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');
        $this->defaultViewObjectName = JsonView::class;
    }

    /**
     * Duplicates a given formDefinition and redirects to the Form Editor
     *
     * @throws PersistenceManagerException
     */
    protected function duplicateAction(string $formName, string $formPersistenceIdentifier, string $savePath): ResponseInterface
    {
        $formSettings = $this->getFormSettings();
        if (!$this->formPersistenceManager->isAllowedPersistencePath($savePath, $formSettings)) {
            throw new PersistenceManagerException(sprintf('Save to path "%s" is not allowed', $savePath), 1614500658);
        }
        if (!$this->formPersistenceManager->isAllowedPersistencePath($formPersistenceIdentifier, $formSettings)) {
            throw new PersistenceManagerException(sprintf('Read of "%s" is not allowed', $formPersistenceIdentifier), 1614500659);
        }
        $formToDuplicate = $this->formPersistenceManager->load($formPersistenceIdentifier, $formSettings, []);
        $formToDuplicate['label'] = $formName;
        $formToDuplicate['identifier'] = $this->formPersistenceManager->getUniqueIdentifier($formSettings, $this->convertFormNameToIdentifier($formName));
        $formPersistenceIdentifier = $this->formPersistenceManager->getUniquePersistenceIdentifier($formToDuplicate['identifier'], $savePath, $formSettings);
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDuplicate'] ?? [] as $className) {
            $hookObj = GeneralUtility::makeInstance($className);
            if (method_exists($hookObj, 'beforeFormDuplicate')) {
                $formToDuplicate = $hookObj->beforeFormDuplicate(
                    $formPersistenceIdentifier,
                    $formToDuplicate
                );
            }
        }
        $response = [
            'status' => 'success',
            'url' => $this->uriBuilder->uriFor('index', ['formPersistenceIdentifier' => $formPersistenceIdentifier], 'FormEditor'),
        ];
        $formToDuplicate = ArrayUtility::stripTagsFromValuesRecursive($formToDuplicate);
        try {
            $this->formPersistenceManager->save($formPersistenceIdentifier, $formToDuplicate, $formSettings);
        } catch (PersistenceManagerException $e) {
            $response = [
                'status' => 'error',
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ];
        }
        // createAction uses the Extbase JsonView::class.
        // That's why we have to set the view variables in this way.
        /** @var JsonView $view */
        $view = $this->view;
        $view->assign('response', $response);
        $view->setVariablesToRender([
            'response',
        ]);
        return $this->jsonResponse();
    }

    /**
     * Initialize the references action.
     * This action uses the Fluid JsonView::class as view.
     */
    protected function initializeReferencesAction(): void
    {
        $this->defaultViewObjectName = JsonView::class;
    }

    /**
     * Show references to this persistence identifier
     *
     * @throws PersistenceManagerException
     */
    protected function referencesAction(string $formPersistenceIdentifier): ResponseInterface
    {
        $formSettings = $this->getFormSettings();
        if (!$this->formPersistenceManager->isAllowedPersistencePath($formPersistenceIdentifier, $formSettings)) {
            throw new PersistenceManagerException(sprintf('Read from "%s" is not allowed', $formPersistenceIdentifier), 1614500660);
        }
        // referencesAction uses the extbase JsonView::class.
        // That's why we have to set the view variables in this way.
        /** @var JsonView $view */
        $view = $this->view;
        $view->assign('references', $this->getProcessedReferencesRows($formPersistenceIdentifier));
        $view->assign('formPersistenceIdentifier', $formPersistenceIdentifier);
        $view->setVariablesToRender([
            'references',
            'formPersistenceIdentifier',
        ]);
        return $this->jsonResponse();
    }

    protected function initializeDeleteAction(): void
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');
        $this->defaultViewObjectName = JsonView::class;
    }

    /**
     * Delete a formDefinition identified by the $formPersistenceIdentifier.
     *
     * @throws PersistenceManagerException
     */
    protected function deleteAction(string $formPersistenceIdentifier): ResponseInterface
    {
        $formSettings = $this->getFormSettings();
        if (!$this->formPersistenceManager->isAllowedPersistencePath($formPersistenceIdentifier, $formSettings)) {
            throw new PersistenceManagerException(sprintf('Delete "%s" is not allowed', $formPersistenceIdentifier), 1614500661);
        }
        $response = [
            'status' => 'success',
            'url' => $this->uriBuilder->uriFor('index', [], 'FormManager'),
        ];
        if (empty($this->databaseService->getReferencesByPersistenceIdentifier($formPersistenceIdentifier))) {
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/form']['beforeFormDelete'] ?? [] as $className) {
                $hookObj = GeneralUtility::makeInstance($className);
                if (method_exists($hookObj, 'beforeFormDelete')) {
                    $hookObj->beforeFormDelete(
                        $formPersistenceIdentifier
                    );
                }
            }
            $this->formPersistenceManager->delete($formPersistenceIdentifier, $formSettings);
        } else {
            $controllerConfiguration = $this->translationService->translateValuesRecursive(
                $formSettings['formManager']['controller'],
                $formSettings['formManager']['translationFiles'] ?? []
            );

            $response = [
                'status' => 'error',
                'title' => $controllerConfiguration['deleteAction']['errorTitle'],
                'message' => sprintf($controllerConfiguration['deleteAction']['errorMessage'], $formPersistenceIdentifier),
            ];
        }

        // deleteAction uses the extbase JsonView::class.
        // That's why we have to set the view variables in this way.
        /** @var JsonView $view */
        $view = $this->view;
        $view->assign('response', $response);
        $view->setVariablesToRender([
            'response',
        ]);
        return $this->jsonResponse();
    }

    protected function getFormSettings(): array
    {
        $typoScriptSettings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS, 'form');
        $formSettings = $this->extFormConfigurationManager->getYamlConfiguration($typoScriptSettings, false);
        if (!isset($formSettings['formManager'])) {
            // Config sub array formManager is crucial and should always exist. If it does
            // not, this indicates an issue in config loading logic. Except in this case.
            throw new \LogicException('Configuration could not be loaded', 1723717461);
        }
        return $formSettings;
    }

    /**
     * Return a list of all accessible file mountpoints.
     *
     * Only registered mount points from
     * persistenceManager.allowedFileMounts
     * are listed. This list will be reduced by the configured
     * mount points for the current backend user.
     */
    protected function getAccessibleFormStorageFolders(array $formSettings, bool $allowSaveToExtensionPaths): array
    {
        $preparedAccessibleFormStorageFolders = [];
        foreach ($this->formPersistenceManager->getAccessibleFormStorageFolders($formSettings) as $identifier => $folder) {
            $preparedAccessibleFormStorageFolders[] = [
                'label' => $folder->getStorage()->isPublic() ? $folder->getPublicUrl() : $identifier,
                'value' => $identifier,
            ];
        }
        if ($allowSaveToExtensionPaths) {
            foreach ($this->formPersistenceManager->getAccessibleExtensionFolders($formSettings) as $relativePath => $fullPath) {
                $preparedAccessibleFormStorageFolders[] = [
                    'label' => $relativePath,
                    'value' => $relativePath,
                ];
            }
        }
        return $preparedAccessibleFormStorageFolders;
    }

    /**
     * Returns the json encoded data which is used by the form editor
     * JavaScript app.
     */
    protected function getFormManagerAppInitialData(array $formSettings): array
    {
        $accessibleFormStorageFolders = $this->getAccessibleFormStorageFolders(
            $formSettings,
            $formSettings['persistenceManager']['allowSaveToExtensionPaths'] ?? false
        );
        $formManagerAppInitialData = [
            'selectablePrototypesConfiguration' => $formSettings['formManager']['selectablePrototypesConfiguration'],
            'accessibleFormStorageFolders' => $accessibleFormStorageFolders,
            'endpoints' => [
                'create' => $this->uriBuilder->uriFor('create'),
                'duplicate' => $this->uriBuilder->uriFor('duplicate'),
                'delete' => $this->uriBuilder->uriFor('delete'),
                'references' => $this->uriBuilder->uriFor('references'),
            ],
        ];
        $formManagerAppInitialData = ArrayUtility::reIndexNumericArrayKeysRecursive($formManagerAppInitialData);
        return $this->translationService->translateValuesRecursive(
            $formManagerAppInitialData,
            $formSettings['formManager']['translationFiles'] ?? []
        );
    }

    /**
     * List all formDefinitions which can be loaded through t form persistence
     * manager. Enrich this data by a reference counter.
     */
    protected function getAvailableFormDefinitions(array $formSettings, string $searchTerm = ''): array
    {
        $allReferencesForFileUid = $this->databaseService->getAllReferencesForFileUid();
        $allReferencesForPersistenceIdentifier = $this->databaseService->getAllReferencesForPersistenceIdentifier();
        $availableFormDefinitions = [];
        foreach ($this->formPersistenceManager->listForms($formSettings) as $formDefinition) {
            $referenceCount  = 0;
            if (isset($formDefinition['fileUid'])
                && array_key_exists($formDefinition['fileUid'], $allReferencesForFileUid)
            ) {
                $referenceCount = $allReferencesForFileUid[$formDefinition['fileUid']];
            } elseif (array_key_exists($formDefinition['persistenceIdentifier'], $allReferencesForPersistenceIdentifier)) {
                $referenceCount = $allReferencesForPersistenceIdentifier[$formDefinition['persistenceIdentifier']];
            }
            $formDefinition['referenceCount'] = $referenceCount;
            if ($searchTerm === ''
                || $this->valueContainsSearchTerm($formDefinition['name'], $searchTerm)
                || $this->valueContainsSearchTerm($formDefinition['persistenceIdentifier'], $searchTerm)
            ) {
                $availableFormDefinitions[] = $formDefinition;
            }
        }
        return $availableFormDefinitions;
    }

    protected function valueContainsSearchTerm(string $value, string $searchTerm): bool
    {
        return str_contains(strtolower($value), strtolower($searchTerm));
    }

    /**
     * Returns an array with information about the references for a
     * formDefinition identified by $persistenceIdentifier.
     */
    protected function getProcessedReferencesRows(string $persistenceIdentifier): array
    {
        if (empty($persistenceIdentifier)) {
            throw new \InvalidArgumentException('$persistenceIdentifier must not be empty.', 1477071939);
        }
        $references = [];
        $referenceRows = $this->databaseService->getReferencesByPersistenceIdentifier($persistenceIdentifier);
        foreach ($referenceRows as $referenceRow) {
            $record = $this->getRecord($referenceRow['tablename'], $referenceRow['recuid']);
            if (!$record) {
                continue;
            }
            $pageRecord = $this->getRecord('pages', $record['pid']);
            $urlParameters = [
                'edit' => [
                    $referenceRow['tablename'] => [
                        $referenceRow['recuid'] => 'edit',
                    ],
                ],
                'returnUrl' => $this->getModuleUrl('web_FormFormbuilder'),
            ];
            $references[] = [
                'recordPageTitle' => is_array($pageRecord) ? BackendUtility::getRecordTitle('pages', $pageRecord) : '',
                'recordTitle' => BackendUtility::getRecordTitle($referenceRow['tablename'], $record),
                'recordIcon' => $this->iconFactory->getIconForRecord($referenceRow['tablename'], $record, IconSize::SMALL)->render(),
                'recordUid' => $referenceRow['recuid'],
                'recordEditUrl' => $this->getModuleUrl('record_edit', $urlParameters),
            ];
        }
        return $references;
    }

    /**
     * Check if a given $templatePath for a given $prototypeName is valid
     * and accessible.
     *
     * Valid template paths has to be configured within
     * formManager.selectablePrototypesConfiguration.[('identifier':  $prototypeName)].newFormTemplates.[('templatePath': $templatePath)]
     */
    protected function isValidTemplatePath(array $formSettings, string $prototypeName, string $templatePath): bool
    {
        $isValid = false;
        foreach ($formSettings['formManager']['selectablePrototypesConfiguration'] as $prototypesConfiguration) {
            if ($prototypesConfiguration['identifier'] !== $prototypeName) {
                continue;
            }
            foreach ($prototypesConfiguration['newFormTemplates'] as $templatesConfiguration) {
                if ($templatesConfiguration['templatePath'] !== $templatePath) {
                    continue;
                }
                $isValid = true;
                break;
            }
        }
        $templatePath = GeneralUtility::getFileAbsFileName($templatePath);
        if (!is_file($templatePath)) {
            $isValid = false;
        }
        return $isValid;
    }

    /**
     * Init ModuleTemplate and register document header buttons
     */
    protected function initializeModuleTemplate(ServerRequestInterface $request, int $page, string $searchTerm): ModuleTemplate
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($request);
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        // Create new
        $addFormButton = $buttonBar->makeLinkButton()
            ->setDataAttributes(['identifier' => 'newForm'])
            ->setHref('#')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formManager.create_new_form'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-plus', IconSize::SMALL));
        $buttonBar->addButton($addFormButton);
        // Reload
        $reloadButton = $buttonBar->makeLinkButton()
            ->setHref($this->request->getAttribute('normalizedParams')->getRequestUri())
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', IconSize::SMALL));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT);
        // Shortcut
        $arguments = [];
        if ($searchTerm) {
            $arguments['tx_form_web_formformbuilder']['searchTerm'] = $searchTerm;
            $arguments['tx_form_web_formformbuilder']['controller'] = 'FormManager';
        }
        if ($page > 1) {
            $arguments['tx_form_web_formformbuilder']['page'] = $page;
            $arguments['tx_form_web_formformbuilder']['controller'] = 'FormManager';
        }
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('web_FormFormbuilder')
            ->setArguments($arguments)
            ->setDisplayName($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:module.shortcut_name'));
        $buttonBar->addButton($shortcutButton, ButtonBar::BUTTON_POSITION_RIGHT);
        return $moduleTemplate;
    }

    /**
     * Returns a form identifier which is the lower cased form name.
     */
    protected function convertFormNameToIdentifier(string $formName): string
    {
        $formName = \Normalizer::normalize($formName) ?: $formName;
        $formIdentifier = $this->charsetConverter->specCharsToASCII('utf-8', $formName);
        $formIdentifier = (string)preg_replace('/[^a-zA-Z0-9-_]/', '', $formIdentifier);
        return lcfirst($formIdentifier);
    }

    /**
     * Wrapper used for unit testing.
     */
    protected function getRecord(string $table, int $uid): ?array
    {
        return BackendUtility::getRecord($table, $uid);
    }

    /**
     * Wrapper used for unit testing.
     */
    protected function getModuleUrl(string $moduleName, array $urlParameters = []): string
    {
        return (string)$this->coreUriBuilder->buildUriFromRoute($moduleName, $urlParameters);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
