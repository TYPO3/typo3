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
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\JavaScriptItems;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Handle FormEngine inline ajax calls
 */
class FormInlineAjaxController extends AbstractFormEngineAjaxController
{
    /**
     * Create a new inline child via AJAX.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function createAction(ServerRequestInterface $request): ResponseInterface
    {
        $ajaxArguments = $request->getParsedBody()['ajax'] ?? $request->getQueryParams()['ajax'];
        $parentConfig = $this->extractSignedParentConfigFromRequest((string)$ajaxArguments['context']);

        $domObjectId = $ajaxArguments[0] ?? '';
        $inlineFirstPid = $this->getInlineFirstPidFromDomObjectId($domObjectId);
        if (!MathUtility::canBeInterpretedAsInteger($inlineFirstPid) && strpos((string)$inlineFirstPid, 'NEW') !== 0) {
            throw new \RuntimeException(
                'inlineFirstPid should either be an integer or a "NEW..." string',
                1521220491
            );
        }
        $childChildUid = null;
        if (isset($ajaxArguments[1]) && MathUtility::canBeInterpretedAsInteger($ajaxArguments[1])) {
            $childChildUid = (int)$ajaxArguments[1];
        }

        // Parse the DOM identifier, add the levels to the structure stack
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByParsingDomObjectIdString($domObjectId);
        $inlineStackProcessor->injectAjaxConfiguration($parentConfig);
        $inlineTopMostParent = $inlineStackProcessor->getStructureLevel(0);

        // Parent, this table embeds the child table
        $parent = $inlineStackProcessor->getStructureLevel(-1);

        // Child, a record from this table should be rendered
        $child = $inlineStackProcessor->getUnstableStructure();
        if (isset($child['uid']) && MathUtility::canBeInterpretedAsInteger($child['uid'])) {
            // If uid comes in, it is the id of the record neighbor record "create after"
            $childVanillaUid = -1 * abs((int)$child['uid']);
        } else {
            // Else inline first Pid is the storage pid of new inline records
            $childVanillaUid = $inlineFirstPid;
        }

        $childTableName = $parentConfig['foreign_table'];

        $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'command' => 'new',
            'tableName' => $childTableName,
            'vanillaUid' => $childVanillaUid,
            'isInlineChild' => true,
            'inlineStructure' => $inlineStackProcessor->getStructure(),
            'inlineFirstPid' => $inlineFirstPid,
            'inlineParentUid' => $parent['uid'],
            'inlineParentTableName' => $parent['table'],
            'inlineParentFieldName' => $parent['field'],
            'inlineParentConfig' => $parentConfig,
            'inlineTopMostParentUid' => $inlineTopMostParent['uid'],
            'inlineTopMostParentTableName' => $inlineTopMostParent['table'],
            'inlineTopMostParentFieldName' => $inlineTopMostParent['field'],
        ];
        if ($childChildUid) {
            $formDataCompilerInput['inlineChildChildUid'] = $childChildUid;
        }
        $childData = $formDataCompiler->compile($formDataCompilerInput);

        if (($parentConfig['foreign_selector'] ?? false) && ($parentConfig['appearance']['useCombination'] ?? false)) {
            // We have a foreign_selector. So, we just created a new record on an intermediate table in $childData.
            // Now, if a valid id is given as second ajax parameter, the intermediate row should be connected to an
            // existing record of the child-child table specified by the given uid. If there is no such id, user
            // clicked on "created new" and a new child-child should be created, too.
            if ($childChildUid) {
                // Fetch existing child child
                $childData['databaseRow'][$parentConfig['foreign_selector']] = [
                    $childChildUid,
                ];
                $childData['combinationChild'] = $this->compileChildChild($childData, $parentConfig, $inlineStackProcessor->getStructure());
            } else {
                $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
                $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
                $formDataCompilerInput = [
                    'command' => 'new',
                    'tableName' => $childData['processedTca']['columns'][$parentConfig['foreign_selector']]['config']['foreign_table'],
                    'vanillaUid' => $inlineFirstPid,
                    'isInlineChild' => true,
                    'isInlineAjaxOpeningContext' => true,
                    'inlineStructure' => $inlineStackProcessor->getStructure(),
                    'inlineFirstPid' => $inlineFirstPid,
                ];
                $childData['combinationChild'] = $formDataCompiler->compile($formDataCompilerInput);
            }
        }

        $childData['inlineParentUid'] = $parent['uid'];
        $childData['renderType'] = 'inlineRecordContainer';
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $childResult = $nodeFactory->create($childData)->render();

        $jsonArray = [
            'data' => '',
            'stylesheetFiles' => [],
            'scriptItems' => GeneralUtility::makeInstance(JavaScriptItems::class),
            'scriptCall' => [],
            'compilerInput' => [
                'uid' => $childData['databaseRow']['uid'],
                'childChildUid' => $childChildUid,
                'parentConfig' => $parentConfig,
            ],
        ];

        $jsonArray = $this->mergeChildResultIntoJsonResult($jsonArray, $childResult);

        return new JsonResponse($jsonArray);
    }

    /**
     * Show the details of a child record.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function detailsAction(ServerRequestInterface $request): ResponseInterface
    {
        $ajaxArguments = $request->getParsedBody()['ajax'] ?? $request->getQueryParams()['ajax'];

        $domObjectId = $ajaxArguments[0] ?? '';
        $inlineFirstPid = $this->getInlineFirstPidFromDomObjectId($domObjectId);
        $parentConfig = $this->extractSignedParentConfigFromRequest((string)$ajaxArguments['context']);

        // Parse the DOM identifier, add the levels to the structure stack
        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByParsingDomObjectIdString($domObjectId);
        $inlineStackProcessor->injectAjaxConfiguration($parentConfig);

        // Parent, this table embeds the child table
        $parent = $inlineStackProcessor->getStructureLevel(-1);
        $parentFieldName = $parent['field'];

        // Set flag in config so that only the fields are rendered
        // @todo: Solve differently / rename / whatever
        $parentConfig['renderFieldsOnly'] = true;

        $parentData = [
            'processedTca' => [
                'columns' => [
                    $parentFieldName => [
                        'config' => $parentConfig,
                    ],
                ],
            ],
            'uid' => $parent['uid'],
            'tableName' => $parent['table'],
            'inlineFirstPid' => $inlineFirstPid,
            // Hand over given original return url to compile stack. Needed if inline children compile links to
            // another view (eg. edit metadata in a nested inline situation like news with inline content element image),
            // so the back link is still the link from the original request. See issue #82525. This is additionally
            // given down in TcaInline data provider to compiled children data.
            'returnUrl' => $parentConfig['originalReturnUrl'],
        ];

        // Child, a record from this table should be rendered
        $child = $inlineStackProcessor->getUnstableStructure();

        $childData = $this->compileChild($parentData, $parentFieldName, (int)$child['uid'], $inlineStackProcessor->getStructure());

        $childData['inlineParentUid'] = (int)$parent['uid'];
        $childData['renderType'] = 'inlineRecordContainer';
        $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
        $childResult = $nodeFactory->create($childData)->render();

        $jsonArray = [
            'data' => '',
            'stylesheetFiles' => [],
            'scriptItems' => GeneralUtility::makeInstance(JavaScriptItems::class),
            'scriptCall' => [],
        ];

        $jsonArray = $this->mergeChildResultIntoJsonResult($jsonArray, $childResult);

        return new JsonResponse($jsonArray);
    }

    /**
     * Adds localizations or synchronizes the locations of all child records.
     * Handle AJAX calls to localize all records of a parent, localize a single record or to synchronize with the original language parent.
     *
     * @param ServerRequestInterface $request the incoming request
     * @return ResponseInterface the filled response
     */
    public function synchronizeLocalizeAction(ServerRequestInterface $request): ResponseInterface
    {
        $ajaxArguments = $request->getParsedBody()['ajax'] ?? $request->getQueryParams()['ajax'];
        $domObjectId = $ajaxArguments[0] ?? '';
        $type = $ajaxArguments[1] ?? null;
        $parentConfig = $this->extractSignedParentConfigFromRequest((string)$ajaxArguments['context']);

        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        // Parse the DOM identifier (string), add the levels to the structure stack (array), load the TCA config:
        $inlineStackProcessor->initializeByParsingDomObjectIdString($domObjectId);
        $inlineStackProcessor->injectAjaxConfiguration($parentConfig);
        $inlineFirstPid = $this->getInlineFirstPidFromDomObjectId($domObjectId);

        $jsonArray = [
            'data' => '',
            'stylesheetFiles' => [],
            'scriptItems' => GeneralUtility::makeInstance(JavaScriptItems::class),
            'compilerInput' => [
                'localize' => [],
            ],
        ];
        if ($type === 'localize' || $type === 'synchronize' || MathUtility::canBeInterpretedAsInteger($type)) {
            // Parent, this table embeds the child table
            $parent = $inlineStackProcessor->getStructureLevel(-1);
            $parentFieldName = $parent['field'];

            $processedTca = $GLOBALS['TCA'][$parent['table']];
            $processedTca['columns'][$parentFieldName]['config'] = $parentConfig;

            $formDataCompilerInputForParent = [
                'vanillaUid' => (int)$parent['uid'],
                'command' => 'edit',
                'tableName' => $parent['table'],
                'processedTca' => $processedTca,
                'inlineFirstPid' => $inlineFirstPid,
                'columnsToProcess' => [
                    $parentFieldName,
                ],
                // @todo: still needed? NO!
                'inlineStructure' => $inlineStackProcessor->getStructure(),
                // Do not compile existing children, we don't need them now
                'inlineCompileExistingChildren' => false,
            ];
            // Full TcaDatabaseRecord is required here to have the list of connected uids $oldItemList
            $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
            $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
            $parentData = $formDataCompiler->compile($formDataCompilerInputForParent);
            $parentConfig = $parentData['processedTca']['columns'][$parentFieldName]['config'];
            $parentLanguageField = $parentData['processedTca']['ctrl']['languageField'];
            $parentLanguage = $parentData['databaseRow'][$parentLanguageField];
            $oldItemList = $parentData['databaseRow'][$parentFieldName];

            // DataHandler cannot handle arrays as field value
            if (is_array($parentLanguage)) {
                $parentLanguage = implode(',', $parentLanguage);
            }

            $cmd = [];
            // Localize a single child element from default language of the parent element
            if (MathUtility::canBeInterpretedAsInteger($type)) {
                $cmd[$parent['table']][$parent['uid']]['inlineLocalizeSynchronize'] = [
                    'field' => $parent['field'],
                    'language' => $parentLanguage,
                    'ids' => [$type],
                ];
            } else {
                // Either localize or synchronize all child elements from default language of the parent element
                $cmd[$parent['table']][$parent['uid']]['inlineLocalizeSynchronize'] = [
                    'field' => $parent['field'],
                    'language' => $parentLanguage,
                    'action' => $type,
                ];
            }

            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->start([], $cmd);
            $tce->process_cmdmap();

            $newItemList = $tce->registerDBList[$parent['table']][$parent['uid']][$parentFieldName];

            $oldItems = $this->getInlineRelatedRecordsUidArray($oldItemList);
            $newItems = $this->getInlineRelatedRecordsUidArray($newItemList);

            // Render error messages from DataHandler
            $tce->printLogErrorMessages();
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $messages = $flashMessageService->getMessageQueueByIdentifier()->getAllMessagesAndFlush();
            if (!empty($messages)) {
                foreach ($messages as $message) {
                    $jsonArray['messages'][] = [
                        'title'    => $message->getTitle(),
                        'message'  => $message->getMessage(),
                        'severity' => $message->getSeverity(),
                    ];
                    if ($message->getSeverity() === AbstractMessage::ERROR) {
                        $jsonArray['hasErrors'] = true;
                    }
                }
            }

            // Set the items that should be removed in the forms view:
            $removedItems = array_diff($oldItems, $newItems);
            $jsonArray['compilerInput']['delete'] = $removedItems;

            $localizedItems = array_diff($newItems, $oldItems);
            foreach ($localizedItems as $i => $childUid) {
                $childData = $this->compileChild($parentData, $parentFieldName, (int)$childUid, $inlineStackProcessor->getStructure());

                $childData['inlineParentUid'] = (int)$parent['uid'];
                $childData['renderType'] = 'inlineRecordContainer';
                $nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
                $childResult = $nodeFactory->create($childData)->render();

                $jsonArray = $this->mergeChildResultIntoJsonResult($jsonArray, $childResult);

                // Get the name of the field used as foreign selector (if any):
                $foreignSelector = isset($parentConfig['foreign_selector']) && $parentConfig['foreign_selector'] ? $parentConfig['foreign_selector'] : false;
                $selectedValue = $foreignSelector ? $childData['databaseRow'][$foreignSelector] : null;
                if (is_array($selectedValue)) {
                    $selectedValue = $selectedValue[0];
                }

                $jsonArray['compilerInput']['localize'][$i] = [
                    'uid' => $childUid,
                    'selectedValue' => $selectedValue,
                ];

                // Remove possible virtual records in the form which showed that a child records could be localized:
                $transOrigPointerFieldName = $childData['processedTca']['ctrl']['transOrigPointerField'];
                if (isset($childData['databaseRow'][$transOrigPointerFieldName]) && $childData['databaseRow'][$transOrigPointerFieldName]) {
                    $transOrigPointerFieldValue = $childData['databaseRow'][$transOrigPointerFieldName];
                    if (is_array($transOrigPointerFieldValue)) {
                        $transOrigPointerFieldValue = $transOrigPointerFieldValue[0];
                        if (is_array($transOrigPointerFieldValue) && ($transOrigPointerFieldValue['uid'] ?? false)) {
                            // With nested inline containers (eg. fal sys_file_reference), row[l10n_parent][0] is sometimes
                            // a table / row combination again. See tx_styleguide_inline_fal inline_5. If this happens we
                            // pick the uid field from the array ... Basically, we need the uid of the 'default language' record,
                            // since this is used in JS to locate and remove the 'shadowed' container.
                            // @todo: Find out if this is really necessary that sometimes ['databaseRow']['l10n_parent'][0]
                            // is resolved to a direct uid, and sometimes it's a array with items. Could this be harmonized?
                            $transOrigPointerFieldValue = $transOrigPointerFieldValue['uid'];
                        }
                    }
                    $jsonArray['compilerInput']['localize'][$i]['remove'] = $transOrigPointerFieldValue;
                }
            }
        }
        return new JsonResponse($jsonArray);
    }

    /**
     * Store status of inline children expand / collapse state in backend user uC.
     *
     * @param ServerRequestInterface $request the incoming request
     * @return ResponseInterface the filled response
     */
    public function expandOrCollapseAction(ServerRequestInterface $request): ResponseInterface
    {
        $ajaxArguments = $request->getParsedBody()['ajax'] ?? $request->getQueryParams()['ajax'];
        [$domObjectId, $expand, $collapse] = $ajaxArguments;

        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        // Parse the DOM identifier (string), add the levels to the structure stack (array), don't load TCA config
        $inlineStackProcessor->initializeByParsingDomObjectIdString($domObjectId);

        $backendUser = $this->getBackendUserAuthentication();
        // The current table - for this table we should add/import records
        $currentTable = $inlineStackProcessor->getUnstableStructure();
        $currentTable = $currentTable['table'];
        // The top parent table - this table embeds the current table
        $top = $inlineStackProcessor->getStructureLevel(0);
        $topTable = $top['table'];
        $topUid = $top['uid'];
        $inlineView = $this->getInlineExpandCollapseStateArray();
        // Only do some action if the top record and the current record were saved before
        if (MathUtility::canBeInterpretedAsInteger($topUid)) {
            $expandUids = GeneralUtility::trimExplode(',', $expand);
            $collapseUids = GeneralUtility::trimExplode(',', $collapse);
            // Set records to be expanded
            foreach ($expandUids as $uid) {
                $inlineView[$topTable][$topUid][$currentTable][] = $uid;
            }
            // Set records to be collapsed
            foreach ($collapseUids as $uid) {
                $inlineView[$topTable][$topUid][$currentTable] = $this->removeFromArray($uid, $inlineView[$topTable][$topUid][$currentTable]);
            }
            // Save states back to database
            if (is_array($inlineView[$topTable][$topUid][$currentTable])) {
                $inlineView[$topTable][$topUid][$currentTable] = array_unique($inlineView[$topTable][$topUid][$currentTable]);
                $backendUser->uc['inlineView'] = json_encode($inlineView);
                $backendUser->writeUC();
            }
        }
        return new JsonResponse([]);
    }

    /**
     * Compile a full child record
     *
     * @param array $parentData Result array of parent
     * @param string $parentFieldName Name of parent field
     * @param int $childUid Uid of child to compile
     * @param array $inlineStructure Current inline structure
     * @return array Full result array
     *
     * @todo: This clones methods compileChild from TcaInline Provider. Find a better abstraction
     * @todo: to also encapsulate the more complex scenarios with combination child and friends.
     */
    protected function compileChild(array $parentData, $parentFieldName, $childUid, array $inlineStructure)
    {
        $parentConfig = $parentData['processedTca']['columns'][$parentFieldName]['config'];

        $inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
        $inlineStackProcessor->initializeByGivenStructure($inlineStructure);
        $inlineTopMostParent = $inlineStackProcessor->getStructureLevel(0);

        // @todo: do not use stack processor here ...
        $child = $inlineStackProcessor->getUnstableStructure();
        $childTableName = $child['table'];

        $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'command' => 'edit',
            'tableName' => $childTableName,
            'vanillaUid' => (int)$childUid,
            'returnUrl' => $parentData['returnUrl'],
            'isInlineChild' => true,
            'inlineStructure' => $inlineStructure,
            'inlineFirstPid' => $parentData['inlineFirstPid'],
            'inlineParentConfig' => $parentConfig,
            'isInlineAjaxOpeningContext' => true,

            // values of the current parent element
            // it is always a string either an id or new...
            'inlineParentUid' => $parentData['databaseRow']['uid'] ?? $parentData['uid'],
            'inlineParentTableName' => $parentData['tableName'],
            'inlineParentFieldName' => $parentFieldName,

            // values of the top most parent element set on first level and not overridden on following levels
            'inlineTopMostParentUid' => $inlineTopMostParent['uid'],
            'inlineTopMostParentTableName' => $inlineTopMostParent['table'],
            'inlineTopMostParentFieldName' => $inlineTopMostParent['field'],
        ];
        // For foreign_selector with useCombination $mainChild is the mm record
        // and $combinationChild is the child-child. For "normal" relations, $mainChild
        // is just the normal child record and $combinationChild is empty.
        $mainChild = $formDataCompiler->compile($formDataCompilerInput);
        if (($parentConfig['foreign_selector'] ?? false) && ($parentConfig['appearance']['useCombination'] ?? false)) {
            // This kicks in if opening an existing mainChild that has a child-child set
            $mainChild['combinationChild'] = $this->compileChildChild($mainChild, $parentConfig, $inlineStructure);
        }
        return $mainChild;
    }

    /**
     * With useCombination set, not only content of the intermediate table, but also
     * the connected child should be rendered in one go. Prepare this here.
     *
     * @param array $child Full data array of "mm" record
     * @param array $parentConfig TCA configuration of "parent"
     * @param array $inlineStructure Current inline structure
     * @return array Full data array of child
     */
    protected function compileChildChild(array $child, array $parentConfig, array $inlineStructure)
    {
        // foreign_selector on intermediate is probably type=select, so data provider of this table resolved that to the uid already
        $childChildUid = $child['databaseRow'][$parentConfig['foreign_selector']][0];
        // child-child table name is set in child tca "the selector field" foreign_table
        $childChildTableName = $child['processedTca']['columns'][$parentConfig['foreign_selector']]['config']['foreign_table'];
        $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'command' => 'edit',
            'tableName' => $childChildTableName,
            'vanillaUid' => (int)$childChildUid,
            'isInlineChild' => true,
            'isInlineAjaxOpeningContext' => true,
            // @todo: this is the wrong inline structure, isn't it? Shouldn't contain it the part from child child, too?
            'inlineStructure' => $inlineStructure,
            'inlineFirstPid' => $child['inlineFirstPid'],
            // values of the top most parent element set on first level and not overridden on following levels
            'inlineTopMostParentUid' => $child['inlineTopMostParentUid'],
            'inlineTopMostParentTableName' => $child['inlineTopMostParentTableName'],
            'inlineTopMostParentFieldName' => $child['inlineTopMostParentFieldName'],
        ];
        return $formDataCompiler->compile($formDataCompilerInput);
    }

    /**
     * Merge stuff from child array into json array.
     * This method is needed since ajax handling methods currently need to put scriptCalls before and after child code.
     *
     * @param array $jsonResult Given json result
     * @param array $childResult Given child result
     * @return array Merged json array
     */
    protected function mergeChildResultIntoJsonResult(array $jsonResult, array $childResult)
    {
        /** @var JavaScriptItems $scriptItems */
        $scriptItems = $jsonResult['scriptItems'];

        $jsonResult['data'] .= $childResult['html'];
        $jsonResult['stylesheetFiles'] = [];
        foreach ($childResult['stylesheetFiles'] as $stylesheetFile) {
            $jsonResult['stylesheetFiles'][] = $this->getRelativePathToStylesheetFile($stylesheetFile);
        }
        if (!empty($childResult['inlineData'])) {
            $jsonResult['inlineData'] = $childResult['inlineData'];
        }
        // @todo deprecate with TYPO3 v12.0
        foreach ($childResult['additionalJavaScriptPost'] as $singleAdditionalJavaScriptPost) {
            $jsonResult['scriptCall'][] = $singleAdditionalJavaScriptPost;
        }
        if (!empty($childResult['additionalInlineLanguageLabelFiles'])) {
            $labels = [];
            foreach ($childResult['additionalInlineLanguageLabelFiles'] as $additionalInlineLanguageLabelFile) {
                ArrayUtility::mergeRecursiveWithOverrule(
                    $labels,
                    $this->getLabelsFromLocalizationFile($additionalInlineLanguageLabelFile)
                );
            }
            $scriptItems->addGlobalAssignment(['TYPO3' => ['lang' => $labels]]);
        }
        $this->addRegisteredRequireJsModulesToJavaScriptItems($childResult, $scriptItems);
        // @todo deprecate modules with arbitrary JavaScript callback function in TYPO3 v12.0
        $jsonResult['requireJsModules'] = $this->createExecutableStringRepresentationOfRegisteredRequireJsModules($childResult, true);

        return $jsonResult;
    }

    /**
     * Gets an array with the uids of related records out of a list of items.
     * This list could contain more information than required. This methods just
     * extracts the uids.
     *
     * @param string $itemList The list of related child records
     * @return array An array with uids
     */
    protected function getInlineRelatedRecordsUidArray($itemList)
    {
        $itemArray = GeneralUtility::trimExplode(',', $itemList, true);
        // Perform modification of the selected items array:
        foreach ($itemArray as &$value) {
            $parts = explode('|', $value, 2);
            $value = $parts[0];
        }
        unset($value);
        return $itemArray;
    }

    /**
     * Return expand / collapse state array for a given table / uid combination
     *
     * @param string $table Handled table
     * @param int $uid Handled uid
     * @return array
     */
    protected function getInlineExpandCollapseStateArrayForTableUid($table, $uid)
    {
        $inlineView = $this->getInlineExpandCollapseStateArray();
        $result = [];
        if (MathUtility::canBeInterpretedAsInteger($uid)) {
            if (!empty($inlineView[$table][$uid])) {
                $result = $inlineView[$table][$uid];
            }
        }
        return $result;
    }

    /**
     * Get expand / collapse state of inline items
     *
     * @return array
     */
    protected function getInlineExpandCollapseStateArray()
    {
        $backendUser = $this->getBackendUserAuthentication();
        if (!$this->backendUserHasUcInlineView($backendUser)) {
            return [];
        }

        $inlineView = json_decode($backendUser->uc['inlineView'], true);
        if (!is_array($inlineView)) {
            $inlineView = [];
        }

        return $inlineView;
    }

    /**
     * Method to check whether the backend user has the property inline view for the current IRRE item.
     * In existing or old IRRE items the attribute may not exist, then the json_decode will fail.
     *
     * @param BackendUserAuthentication $backendUser
     * @return bool
     */
    protected function backendUserHasUcInlineView(BackendUserAuthentication $backendUser)
    {
        return !empty($backendUser->uc['inlineView']);
    }

    /**
     * Remove an element from an array.
     *
     * @param mixed $needle The element to be removed.
     * @param array $haystack The array the element should be removed from.
     * @param bool $strict Search elements strictly.
     * @return array The array $haystack without the $needle
     */
    protected function removeFromArray($needle, $haystack, $strict = false)
    {
        $pos = array_search($needle, $haystack, $strict);
        if ($pos !== false) {
            unset($haystack[$pos]);
        }
        return $haystack;
    }

    /**
     * Generates an error message that transferred as JSON for AJAX calls
     *
     * @param string $message The error message to be shown
     * @return array The error message in a JSON array
     * @todo remove with TYPO3 v12.0
     * @internal
     */
    protected function getErrorMessageForAJAX($message)
    {
        return [
            'data' => $message,
            'scriptCall' => [
                'alert("' . $message . '");',
            ],
        ];
    }

    /**
     * Get inlineFirstPid from a given objectId string
     *
     * @param string $domObjectId The id attribute of an element
     * @return int|string|null Pid or null
     */
    protected function getInlineFirstPidFromDomObjectId(string $domObjectId)
    {
        // Substitute FlexForm addition and make parsing a bit easier
        $domObjectId = str_replace('---', ':', $domObjectId);
        // The starting pattern of an object identifier (e.g. "data-<firstPidValue>-<anything>)
        $pattern = '/^data-(.+?)-(.+)$/';
        if (preg_match($pattern, $domObjectId, $match)) {
            return $match[1];
        }
        return null;
    }

    /**
     * Validates the config that is transferred over the wire to provide the
     * correct TCA config for the parent table
     *
     * @param string $contextString
     * @throws \RuntimeException
     * @return array
     */
    protected function extractSignedParentConfigFromRequest(string $contextString): array
    {
        if ($contextString === '') {
            throw new \RuntimeException('Empty context string given', 1489751361);
        }
        $context = json_decode($contextString, true);
        if (empty($context['config'])) {
            throw new \RuntimeException('Empty context config section given', 1489751362);
        }
        if (!hash_equals(GeneralUtility::hmac((string)$context['config'], 'InlineContext'), (string)$context['hmac'])) {
            throw new \RuntimeException('Hash does not validate', 1489751363);
        }
        return json_decode($context['config'], true);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
