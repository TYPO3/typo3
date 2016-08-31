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

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\View\JsonView;
use TYPO3\CMS\Form\Exception as FormException;
use TYPO3\CMS\Form\Service\TranslationService;
use TYPO3\CMS\Lang\LanguageService;

/**
 * The form manager controller
 *
 * Scope: backend
 */
class FormManagerController extends AbstractBackendController
{

    /**
     * Default View Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * Initialize the references action.
     * This action use the Fluid JsonView::class as view.
     *
     * @return void
     * @internal
     */
    public function initializeReferencesAction()
    {
        $this->defaultViewObjectName = JsonView::class;
    }

    /**
     * Displays the Form Manager
     *
     * @return void
     * @internal
     */
    public function indexAction()
    {
        $this->registerDocheaderButtons();
        $this->view->getModuleTemplate()->setModuleName($this->request->getPluginName() . '_' . $this->request->getControllerName());
        $this->view->getModuleTemplate()->setFlashMessageQueue($this->controllerContext->getFlashMessageQueue());

        $this->view->assign('forms', $this->getAvailableFormDefinitions());
        $this->view->assign('stylesheets', $this->resolveResourcePaths($this->formSettings['formManager']['stylesheets']));
        $this->view->assign('dynamicRequireJsModules', $this->formSettings['formManager']['dynamicRequireJsModules']);
        $this->view->assign('formManagerAppInitialData', $this->getFormManagerAppInitialData());
        if (!empty($this->formSettings['formManager']['javaScriptTranslationFile'])) {
            $this->getPageRenderer()->addInlineLanguageLabelFile($this->formSettings['formManager']['javaScriptTranslationFile']);
        }
    }

    /**
     * Creates a new Form and redirects to the Form Editor
     *
     * @param string $formName
     * @param string $templatePath
     * @param string $prototypeName
     * @param string $savePath
     * @return string
     * @throws FormException
     * @internal
     */
    public function createAction(string $formName, string $templatePath, string $prototypeName, string $savePath): string
    {
        if (!$this->isValidTemplatePath($prototypeName, $templatePath)) {
            throw new FormException(sprintf('The template path "%s" is not allowed', $templatePath), 1329233410);
        }
        if (empty($formName)) {
            throw new FormException(sprintf('No form name', $templatePath), 1472312204);
        }

        $templatePath = GeneralUtility::getFileAbsFileName($templatePath);
        $form = Yaml::parse(file_get_contents($templatePath));
        $form['label'] = $formName;
        $form['identifier'] = $this->formPersistenceManager->getUniqueIdentifier($this->convertFormNameToIdentifier($formName));
        $form['prototypeName'] = $prototypeName;

        $formPersistenceIdentifier = $this->formPersistenceManager->getUniquePersistenceIdentifier($form['identifier'], $savePath);
        $this->formPersistenceManager->save($formPersistenceIdentifier, $form);

        return $this->controllerContext->getUriBuilder()->uriFor('index', ['formPersistenceIdentifier' => $formPersistenceIdentifier], 'FormEditor');
    }

    /**
     * Duplicates a given formDefinition and redirects to the Form Editor
     *
     * @param string $formName
     * @param string $formPersistenceIdentifier persistence identifier of the form to duplicate
     * @param string $savePath
     * @return string
     * @internal
     */
    public function duplicateAction(string $formName, string $formPersistenceIdentifier, string $savePath): string
    {
        $formToDuplicate = $this->formPersistenceManager->load($formPersistenceIdentifier);
        $formToDuplicate['label'] = $formName;
        $formToDuplicate['identifier'] = $this->formPersistenceManager->getUniqueIdentifier($this->convertFormNameToIdentifier($formName));

        $formPersistenceIdentifier = $this->formPersistenceManager->getUniquePersistenceIdentifier($formToDuplicate['identifier'], $savePath);
        $this->formPersistenceManager->save($formPersistenceIdentifier, $formToDuplicate);

        return $this->controllerContext->getUriBuilder()->uriFor('index', ['formPersistenceIdentifier' => $formPersistenceIdentifier], 'FormEditor');
    }

    /**
     * Show references to this persistence identifier
     *
     * @param string $formPersistenceIdentifier persistence identifier of the form to duplicate
     * @return void
     * @internal
     */
    public function referencesAction(string $formPersistenceIdentifier)
    {
        $this->view->assign('references', $this->getProcessedReferencesRows($formPersistenceIdentifier));
        $this->view->assign('formPersistenceIdentifier', $formPersistenceIdentifier);
        // referencesAction uses the extbase JsonView::class.
        // That's why we have to set the view variables in this way.
        $this->view->setVariablesToRender([
            'references',
            'formPersistenceIdentifier'
        ]);
    }

    /**
     * Delete a formDefinition identified by the $formPersistenceIdentifier.
     * Only formDefinitions within storage folders are deletable.
     *
     * @param string $formPersistenceIdentifier persistence identifier to delete
     * @return void
     * @internal
     */
    public function deleteAction(string $formPersistenceIdentifier)
    {
        if (
            empty($this->getReferences($formPersistenceIdentifier))
            && strpos($formPersistenceIdentifier, 'EXT:') === false
        ) {
            $this->formPersistenceManager->delete($formPersistenceIdentifier);
        } else {
            $this->addFlashMessage(
                TranslationService::getInstance()->translate(
                    $this->formSettings['formManager']['controller']['deleteAction']['errorMessage'],
                    [$formPersistenceIdentifier],
                    $this->formSettings['formManager']['translationFile'],
                    null,
                    $this->formSettings['formManager']['controller']['deleteAction']['errorMessage']
                ),
                TranslationService::getInstance()->translate(
                    $this->formSettings['formManager']['controller']['deleteAction']['errorTitle'],
                    null,
                    $this->formSettings['formManager']['translationFile'],
                    null,
                    $this->formSettings['formManager']['controller']['deleteAction']['errorTitle']
                ),
                AbstractMessage::ERROR,
                true
            );
        }
        $this->redirect('index');
    }

    /**
     * Return a list of all accessible file mountpoints.
     *
     * Only registered mountpoints from
     * TYPO3.CMS.Form.persistenceManager.allowedFileMounts
     * are listet. This is list will be reduced by the configured
     * mountpoints for the current backend user.
     *
     * @return array
     */
    protected function getAccessibleFormStorageFolders(): array
    {
        $preparedAccessibleFormStorageFolders = [];
        foreach ($this->formPersistenceManager->getAccessibleFormStorageFolders() as $identifier => $folder) {
            $preparedAccessibleFormStorageFolders[] = [
                'label' => $folder->getName(),
                'value' => $identifier
            ];
        }
        return $preparedAccessibleFormStorageFolders;
    }

    /**
     * Returns the json encoded data which is used by the form editor
     * JavaScript app.
     *
     * @return string
     */
    protected function getFormManagerAppInitialData(): string
    {
        $formManagerAppInitialData = [
            'selectablePrototypesConfiguration' => $this->formSettings['formManager']['selectablePrototypesConfiguration'],
            'accessibleFormStorageFolders' => $this->getAccessibleFormStorageFolders(),
            'endpoints' => [
                'create' => $this->controllerContext->getUriBuilder()->uriFor('create'),
                'duplicate' => $this->controllerContext->getUriBuilder()->uriFor('duplicate'),
                'delete' => $this->controllerContext->getUriBuilder()->uriFor('delete'),
                'references' => $this->controllerContext->getUriBuilder()->uriFor('references')
            ],
        ];

        $formManagerAppInitialData = ArrayUtility::reIndexNumericArrayKeysRecursive($formManagerAppInitialData);
        $formManagerAppInitialData = TranslationService::getInstance()->translateValuesRecursive(
            $formManagerAppInitialData,
            $this->formSettings['formManager']['translationFile']
        );
        return json_encode($formManagerAppInitialData);
    }

    /**
     * List all formDefinitions which can be loaded through t form persistence
     * manager. Enrich this data by a reference counter.
     * @return array
     */
    protected function getAvailableFormDefinitions(): array
    {
        $availableFormDefinitions = [];
        foreach ($this->formPersistenceManager->listForms() as $formDefinition) {
            $referenceCount = count($this->getReferences($formDefinition['persistenceIdentifier']));
            $formDefinition['referenceCount'] = $referenceCount;
            $availableFormDefinitions[] = $formDefinition;
        }
        return $availableFormDefinitions;
    }

    /**
     * Returns an array with informations about the references for a
     * formDefinition identified by $persistenceIdentifier.
     *
     * @param string $persistenceIdentifier
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getProcessedReferencesRows(string $persistenceIdentifier): array
    {
        if (empty($persistenceIdentifier)) {
            throw new \InvalidArgumentException('$persistenceIdentifier must not be empty.', 1477071939);
        }

        $references = [];
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $referenceRows = $this->getReferences($persistenceIdentifier);
        foreach ($referenceRows as &$referenceRow) {
            $record = $this->getBackendUtility()::getRecord($referenceRow['tablename'], $referenceRow['recuid']);
            if (!$record) {
                continue;
            }
            $pageRecord = $this->getBackendUtility()::getRecord('pages', $record['pid']);
            $urlParameters = [
                'edit' => [
                    $referenceRow['tablename'] => [
                        $referenceRow['recuid'] => 'edit'
                    ]
                ],
                'returnUrl' => $this->getBackendUtility()::getModuleUrl('web_FormFormbuilder')
            ];

            $references[] = [
                'recordPageTitle' => is_array($pageRecord) ? $this->getBackendUtility()::getRecordTitle('pages', $pageRecord) : '',
                'recordTitle' => $this->getBackendUtility()::getRecordTitle($referenceRow['tablename'], $record, true),
                'recordIcon' => $iconFactory->getIconForRecord($referenceRow['tablename'], $record, Icon::SIZE_SMALL)->render(),
                'recordUid' => $referenceRow['recuid'],
                'recordEditUrl' => $this->getBackendUtility()::getModuleUrl('record_edit', $urlParameters),
            ];
        }
        return $references;
    }

    /**
     * Returns an array with all sys_refindex database rows which be
     * connected to a formDefinition identified by $persistenceIdentifier
     *
     * @param string $persistenceIdentifier
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function getReferences(string $persistenceIdentifier): array
    {
        if (empty($persistenceIdentifier)) {
            throw new \InvalidArgumentException('$persistenceIdentifier must not be empty.', 1472238493);
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
        $referenceRows = $queryBuilder
            ->select('*')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('deleted', 0),
                $queryBuilder->expr()->eq('softref_key', $queryBuilder->createNamedParameter('formPersistenceIdentifier', \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq('ref_string', $queryBuilder->createNamedParameter($persistenceIdentifier, \PDO::PARAM_STR)),
                $queryBuilder->expr()->eq('tablename', $queryBuilder->createNamedParameter('tt_content', \PDO::PARAM_STR))
            )
            ->execute()
            ->fetchAll();
        return $referenceRows;
    }

    /**
     * Check if a given $templatePath for a given $prototypeName is valid
     * and accessible.
     *
     * Valid template paths has to be configured within
     * TYPO3.CMS.Form.formManager.selectablePrototypesConfiguration.[('identifier':  $prototypeName)].newFormTemplates.[('templatePath': $templatePath)]
     *
     * @param string $prototypeName
     * @param string $templatePath
     * @return bool
     */
    protected function isValidTemplatePath(string $prototypeName, string $templatePath): bool
    {
        $isValid = false;
        foreach ($this->formSettings['formManager']['selectablePrototypesConfiguration'] as $prototypesConfiguration) {
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
     * Registers the Icons into the docheader
     *
     * @throws \InvalidArgumentException
     */
    protected function registerDocheaderButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();
        $currentRequest = $this->request;
        $moduleName = $currentRequest->getPluginName();
        $getVars = $this->request->getArguments();

        $mayMakeShortcut = $this->getBackendUser()->mayMakeShortcut();
        if ($mayMakeShortcut) {
            $extensionName = $currentRequest->getControllerExtensionName();
            if (count($getVars) === 0) {
                $modulePrefix = strtolower('tx_' . $extensionName . '_' . $moduleName);
                $getVars = ['id', 'M', $modulePrefix];
            }

            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setModuleName($moduleName)
                ->setDisplayName($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:module.shortcut_name'))
                ->setGetVariables($getVars);
            $buttonBar->addButton($shortcutButton);
        }

        if (isset($getVars['action']) && $getVars['action'] !== 'index') {
            $backButton = $buttonBar->makeLinkButton()
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_common.xlf:back'))
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-view-go-up', Icon::SIZE_SMALL))
                ->setHref($this->getBackendUtility()::getModuleUrl($moduleName));
            $buttonBar->addButton($backButton);
        } else {
            $addFormButton = $buttonBar->makeLinkButton()
                ->setDataAttributes(['identifier' => 'newForm'])
                ->setHref('#')
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:form/Resources/Private/Language/Database.xlf:formManager.create_new_form'))
                ->setIcon($this->view->getModuleTemplate()->getIconFactory()->getIcon('actions-document-new', Icon::SIZE_SMALL));
            $buttonBar->addButton($addFormButton, ButtonBar::BUTTON_POSITION_LEFT);
        }
    }

    /**
     * Returns a form identifier which is the lower cased form name.
     *
     * @param string $formName
     * @return string
     */
    protected function convertFormNameToIdentifier(string $formName): string
    {
        $formIdentifier = preg_replace('/[^a-zA-Z0-9-_]/', '', $formName);
        $formIdentifier = lcfirst($formIdentifier);
        return $formIdentifier;
    }

    /**
     * Returns the BackendUtility
     * This wrapper is needed for unit tests.
     *
     * @return string
     */
    protected function getBackendUtility(): string
    {
        return BackendUtility::class;
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
     * Returns the Language Service
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the page renderer
     *
     * @return PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
