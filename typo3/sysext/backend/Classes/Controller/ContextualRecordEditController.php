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

namespace TYPO3\CMS\Backend\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Dto\FormElementData;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedException;
use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordException;
use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordWorkspaceDeletePlaceholderException;
use TYPO3\CMS\Backend\Form\Exception\NoFieldsToRenderException;
use TYPO3\CMS\Backend\Form\FormAction;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormResultCollection;
use TYPO3\CMS\Backend\Form\FormResultFactory;
use TYPO3\CMS\Backend\Form\FormResultHandler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Module\ModuleProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * Lightweight controller for editing a single existing record inside the context panel.
 *
 * This is a focused alternative to EditDocumentController that only handles editing
 * one existing record. It renders the same FormEngine form but with a minimal contextual
 * template, communicating save/close signals to the parent frame via JavaScript.
 *
 * @internal This controller is not part of the TYPO3 public API.
 */
#[AsController]
readonly class ContextualRecordEditController
{
    public function __construct(
        private PageRenderer $pageRenderer,
        private UriBuilder $uriBuilder,
        private ModuleTemplateFactory $moduleTemplateFactory,
        private ModuleProvider $moduleProvider,
        private FormDataCompiler $formDataCompiler,
        private NodeFactory $nodeFactory,
        private FormResultFactory $formResultFactory,
        private FormResultHandler $formResultHandler,
        private TcaSchemaFactory $tcaSchemaFactory,
        private IconFactory $iconFactory,
    ) {}

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        return $request->getMethod() === 'POST' ? $this->persistAction($request) : $this->renderAction($request);
    }

    /**
     * Handle POST: process save/close via DataHandler and redirect back
     */
    private function persistAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $editConf = $this->parseAndValidateEditConf($queryParams['edit'] ?? []);
        $table = $editConf['table'];
        $uid = $this->resolveOverlayUid($table, $editConf['uid']);

        $requestAction = FormAction::createFromRequest($request);

        // Handle close (without save)
        if ($requestAction->shouldHandleDocumentClosing()) {
            return $this->redirectToSelf($queryParams, ['closed' => '1']);
        }

        $saveSucceeded = false;
        if ($requestAction->shouldProcessData()) {
            $saveSucceeded = $this->processData($request, $table, $uid);
            if ($saveSucceeded && $requestAction->shouldCloseAfterSave()) {
                return $this->redirectToSelf($queryParams, ['closed' => '1', 'justSaved' => '1']);
            }
        }

        // POST-redirect-GET
        $flags = ['edit' => [$table => [$uid => 'edit']]];
        if ($saveSucceeded) {
            $flags['justSaved'] = '1';
        }
        return $this->redirectToSelf($queryParams, $flags);
    }

    /**
     * Handle GET: compile the FormEngine form and render the contextual edit template.
     */
    private function renderAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->moduleTemplateFactory->create($request);
        $view->setUiBlock(true);

        $queryParams = $request->getQueryParams();
        $editConf = $this->parseAndValidateEditConf($queryParams['edit'] ?? []);
        $table = $editConf['table'];
        $uid = $this->resolveOverlayUid($table, $editConf['uid']);

        $returnUrl = GeneralUtility::sanitizeLocalUrl($queryParams['returnUrl'] ?? '', $request);
        $overrideVals = is_array($queryParams['overrideVals'] ?? false) ? $queryParams['overrideVals'] : [];
        $columnsOnly = $this->prepareColumnsOnlyConfiguration($queryParams['columnsOnly'] ?? null, $table);
        $module = $this->moduleProvider->getModule((string)($queryParams['module'] ?? ''), $this->getBackendUser());

        if ($module !== null) {
            $view->setModuleName($module->getIdentifier());
        }

        // Compile FormEngine form
        $currentEditingUrl = $this->uriBuilder->buildUriFromRoute('record_edit_contextual', array_merge($queryParams, [
            'edit' => [$table => [$uid => 'edit']],
            'returnUrl' => $returnUrl,
        ]));

        $formResult = $this->compileForm($request, $view, $table, $uid, $overrideVals, $columnsOnly, $currentEditingUrl);
        $firstEl = $formResult['element'] ?? null;
        if ($firstEl !== null) {
            $this->formResultHandler->addAssets($formResult['results']);
            $body = '
                <form action="' . htmlspecialchars((string)$currentEditingUrl) . '" method="post" enctype="multipart/form-data" name="editform" id="ContextualRecordEditController">
                    ' . $formResult['results']->getHtml() . '
                    <input type="hidden" name="returnUrl" value="' . htmlspecialchars($returnUrl) . '" />
                    <input type="hidden" name="closeDoc" value="0" />
                    ' . implode(LF, $formResult['results']->getHiddenFieldsHtml()) . '
                </form>';
        } else {
            $view->setUiBlock(false);
            $body = $formResult['errorHtml'] ?? $this->getInfobox(
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:noEditForm.message'),
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:noEditForm'),
            );
        }

        // Contextual JS module with options
        $contextualOptions = [];
        if ($queryParams['justSaved'] ?? false) {
            $contextualOptions['justSaved'] = true;
            $contextualOptions['savedRecordTitle'] = $firstEl !== null ? $firstEl->title : '';
        }
        if ($queryParams['closed'] ?? false) {
            $contextualOptions['closed'] = true;
        }
        $this->pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/backend/contextual-record-edit.js')->instance($contextualOptions)
        );
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/context-menu.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/localization.js');

        // Template variables
        $view->assign('bodyHtml', $body);
        $recordTitle = $firstEl !== null && trim($firstEl->title) !== ''
            ? $firstEl->title
            : '[' . $this->getLanguageService()->sL('core.core:labels.no_title') . ']';
        $view->assign('recordTitle', $recordTitle);

        // Full edit URL points to the standard EditDocumentController
        $fullEditParams = [
            'edit' => [$table => [$uid => 'edit']],
            'returnUrl' => $returnUrl,
        ];
        if ($module !== null) {
            $fullEditParams['module'] = $module->getIdentifier();
        }
        $view->assign('fullEditUrl', (string)$this->uriBuilder->buildUriFromRoute('record_edit', $fullEditParams));

        return $view->renderResponse('Form/ContextualRecordEdit');
    }

    /**
     * Parse and validate the edit configuration. Ensures exactly one record with command "edit".
     *
     * @return array{table: string, uid: int}
     */
    private function parseAndValidateEditConf(array|string $editConf): array
    {
        if (!is_array($editConf)) {
            throw new \InvalidArgumentException('Invalid edit configuration', 1772580316);
        }
        $backendUser = $this->getBackendUser();
        foreach ($editConf as $table => $conf) {
            if (!is_array($conf) || !$this->tcaSchemaFactory->has($table)) {
                continue;
            }
            if (!$backendUser->check('tables_modify', $table)) {
                continue;
            }
            foreach ($conf as $uidList => $command) {
                if ($command !== 'edit') {
                    continue;
                }
                $uid = (int)$uidList;
                if ($uid > 0) {
                    return ['table' => $table, 'uid' => $uid];
                }
            }
        }
        throw new \InvalidArgumentException('ContextualRecordEditController requires exactly one existing record to edit', 1772580317);
    }

    private function prepareColumnsOnlyConfiguration(mixed $columnsOnly, string $table): array
    {
        if (!is_array($columnsOnly) || $columnsOnly === []) {
            return [];
        }
        $finalColumnsOnly = array_map(
            static fn($fields) => is_array($fields) ? $fields : GeneralUtility::trimExplode(',', $fields, true),
            $columnsOnly
        );
        // Add slug generator fields as hidden fields
        if (!empty($finalColumnsOnly[$table]) && $this->tcaSchemaFactory->has($table)) {
            $schema = $this->tcaSchemaFactory->get($table);
            foreach ($finalColumnsOnly[$table] as $fieldName) {
                if (!$schema->hasField($fieldName)) {
                    continue;
                }
                $field = $schema->getField($fieldName);
                $postModifiers = $field->getConfiguration()['generatorOptions']['postModifiers'] ?? [];
                if ($field->isType(\TYPO3\CMS\Core\DataHandling\TableColumnType::SLUG)
                    && (!is_array($postModifiers) || $postModifiers === [])
                ) {
                    $fieldGroups = $field->getConfiguration()['generatorOptions']['fields'] ?? [];
                    if (is_string($fieldGroups)) {
                        $fieldGroups = [$fieldGroups];
                    }
                    foreach ($fieldGroups as $fields) {
                        $finalColumnsOnly['__hiddenGeneratorFields'][$table] = array_merge(
                            $finalColumnsOnly['__hiddenGeneratorFields'][$table] ?? [],
                            (is_array($fields) ? $fields : GeneralUtility::trimExplode(',', $fields, true))
                        );
                    }
                }
            }
            if (!empty($finalColumnsOnly['__hiddenGeneratorFields'][$table])) {
                $finalColumnsOnly['__hiddenGeneratorFields'][$table] = array_diff(
                    array_unique($finalColumnsOnly['__hiddenGeneratorFields'][$table]),
                    $finalColumnsOnly[$table]
                );
            }
        }
        return $finalColumnsOnly;
    }

    /**
     * Process save data via DataHandler.
     *
     * @return bool True if at least one record was saved without errors
     */
    private function processData(ServerRequestInterface $request, string $table, int $uid): bool
    {
        $parsedBody = $request->getParsedBody();
        $dataMap = $parsedBody['data'] ?? [];

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->setControl($parsedBody['control'] ?? []);
        $dataHandler->start($dataMap, $parsedBody['cmd'] ?? []);

        if (is_array($parsedBody['mirror'] ?? null)) {
            $dataHandler->setMirror($parsedBody['mirror']);
        }

        $dataHandler->process_datamap();
        $dataHandler->process_cmdmap();

        // Check if save succeeded (no errors for this record)
        $erroneousRecords = $dataHandler->printLogErrorMessages();
        return !in_array($table . '.' . $uid, $erroneousRecords, true) && isset($dataMap[$table][$uid]);
    }

    /**
     * @return array{element: FormElementData, results: FormResultCollection}|array{errorHtml: string}
     */
    private function compileForm(
        ServerRequestInterface $request,
        ModuleTemplate $view,
        string $table,
        int $uid,
        array $overrideVals,
        array $columnsOnly,
        UriInterface $currentEditingUrl,
    ): array {
        try {
            $formDataCompilerInput = [
                'request' => $request,
                'tableName' => $table,
                'vanillaUid' => $uid,
                'command' => 'edit',
                'returnUrl' => (string)$currentEditingUrl,
            ];
            if ($overrideVals !== [] && is_array($overrideVals[$table] ?? null)) {
                $formDataCompilerInput['overrideValues'] = $overrideVals[$table];
            }

            $formData = $this->formDataCompiler->compile($formDataCompilerInput, GeneralUtility::makeInstance(TcaDatabaseRecord::class));

            // Display "is-locked" message
            $lockInfo = BackendUtility::isRecordLocked($table, $formData['databaseRow']['uid']);
            if ($lockInfo) {
                $view->addFlashMessage($lockInfo['msg'], '', \TYPO3\CMS\Core\Type\ContextualFeedbackSeverity::WARNING);
            }

            $formElementData = new FormElementData(
                title: $formData['recordTitle'],
                table: $table,
                uid: $formData['databaseRow']['uid'],
                pid: $formData['databaseRow']['pid'] ?? 0,
                record: $formData['databaseRow'],
                viewId: 0,
                command: 'edit',
                userPermissionOnPage: $formData['userPermissionOnPage'],
            );

            BackendUtility::lockRecords($table, $formElementData->uid, $table === 'tt_content' ? $formElementData->pid : 0);

            if (!empty($columnsOnly[$table])) {
                $formData['fieldListToRender'] = implode(',', $columnsOnly[$table]);
                if (!empty($columnsOnly['__hiddenGeneratorFields'][$table])) {
                    $formData['hiddenFieldListToRender'] = implode(',', $columnsOnly['__hiddenGeneratorFields'][$table]);
                }
            }

            $formData['renderType'] = 'formWrapContainer';
            $formResult = $this->nodeFactory->create($formData)->render();
            $formResult = $this->formResultFactory->create($formResult);
            $formResults = new FormResultCollection();
            $formResults->add($formResult);

            return ['element' => $formElementData, 'results' => $formResults];
        } catch (NoFieldsToRenderException) {
            return ['errorHtml' => $this->getInfobox(
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:noFieldsEditForm.message'),
                $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf:noFieldsEditForm'),
            )];
        } catch (AccessDeniedException $e) {
            return ['errorHtml' => $this->getInfobox(
                $e->getMessage(),
                $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.noEditPermission'),
            )];
        } catch (DatabaseRecordException|DatabaseRecordWorkspaceDeletePlaceholderException $e) {
            return ['errorHtml' => $this->getInfobox($e->getMessage())];
        }
    }

    /**
     * Redirect back to this controller with additional flags for the JS module.
     */
    private function redirectToSelf(array $queryParams, array $additionalParams): ResponseInterface
    {
        $queryParams = array_merge($queryParams, $additionalParams);
        $url = $this->uriBuilder->buildUriFromRoute('record_edit_contextual', $queryParams);
        return new RedirectResponse($url, 302);
    }

    /**
     * Resolve the workspace-aware UID for a single record.
     * In a workspace, the live UID is replaced with the workspace overlay UID.
     */
    private function resolveOverlayUid(string $table, int $uid): int
    {
        $record = $this->getRecordForEdit($table, $uid);
        if (is_array($record)) {
            return (int)$record['uid'];
        }
        return $uid;
    }

    /**
     * Get record for editing, resolving workspace versions.
     *
     * @return array|false
     */
    private function getRecordForEdit(string $table, int $recordId): array|bool
    {
        $schema = $this->tcaSchemaFactory->get($table);
        $reqRecord = BackendUtility::getRecord($table, $recordId, 'uid,pid' . ($schema->isWorkspaceAware() ? ',t3ver_oid' : ''));
        if (is_array($reqRecord)) {
            if ($this->getBackendUser()->workspace !== 0) {
                if ($schema->isWorkspaceAware()) {
                    if ($reqRecord['t3ver_oid'] > 0 || VersionState::tryFrom($reqRecord['t3ver_state'] ?? 0) === VersionState::NEW_PLACEHOLDER) {
                        return $reqRecord;
                    }
                    $versionRec = BackendUtility::getWorkspaceVersionOfRecord(
                        $this->getBackendUser()->workspace,
                        $table,
                        $reqRecord['uid'],
                        'uid,pid,t3ver_oid'
                    );
                    return is_array($versionRec) ? $versionRec : $reqRecord;
                }
                return false;
            }
            return $reqRecord;
        }
        return false;
    }

    private function getInfobox(string $message, ?string $title = null): string
    {
        return '
            <div class="callout callout-danger">
                <div class="callout-icon">
                    <span class="icon-emphasized">
                        ' . $this->iconFactory->getIcon('actions-close', IconSize::SMALL)->render() . '
                    </span>
                </div>
                <div class="callout-content">
                    ' . ($title ? '<div class="callout-title">' . htmlspecialchars($title) . '</div>' : '') . '
                    <div class="callout-body">
                        ' . htmlspecialchars($message) . '
                    </div>
                </div>
            </div>';
    }

    private function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
