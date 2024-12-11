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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\JavaScriptItems;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Handle FormEngine files ajax calls
 */
#[AsController]
readonly class FormFilesAjaxController extends AbstractFormEngineAjaxController
{
    private const FILE_REFERENCE_TABLE = 'sys_file_reference';

    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private FormDataCompiler $formDataCompiler,
        private HashService $hashService,
        private NodeFactory $nodeFactory,
        private InlineStackProcessor $inlineStackProcessor,
    ) {}

    /**
     * Create a new file reference via AJAX.
     */
    public function createAction(ServerRequestInterface $request): ResponseInterface
    {
        $arguments = $request->getParsedBody()['ajax'];
        $parentConfig = $this->extractSignedParentConfigFromRequest((string)($arguments['context'] ?? ''));

        $domObjectId = (string)($arguments[0] ?? '');
        $inlineFirstPid = $this->getInlineFirstPidFromDomObjectId($domObjectId);
        if (!MathUtility::canBeInterpretedAsInteger($inlineFirstPid) && !str_starts_with((string)$inlineFirstPid, 'NEW')) {
            throw new \RuntimeException(
                'inlineFirstPid should either be an integer or a "NEW..." string',
                1664440476
            );
        }
        $fileId = null;
        if (isset($arguments[1]) && MathUtility::canBeInterpretedAsInteger($arguments[1])) {
            $fileId = (int)$arguments[1];
        }

        $inlineStructure = $this->inlineStackProcessor->getStructureFromString($domObjectId);
        $inlineStructure = $this->inlineStackProcessor->addAjaxConfigurationToStructure($inlineStructure, $parentConfig);
        $inlineTopMostParent = $this->inlineStackProcessor->getStructureLevelFromStructure($inlineStructure, 0);
        $inlineParent = $this->inlineStackProcessor->getStructureLevelFromStructure($inlineStructure, -1);
        $fileReference = $this->inlineStackProcessor->getUnstableStructureFromStructure($inlineStructure);

        if (isset($fileReference['uid']) && MathUtility::canBeInterpretedAsInteger($fileReference['uid'])) {
            // If uid comes in, it is the id of the record neighbor record "create after"
            $fileReferenceVanillaUid = -1 * abs((int)$fileReference['uid']);
        } else {
            // Else inline first Pid is the storage pid of new inline records
            $fileReferenceVanillaUid = $inlineFirstPid;
        }

        $formDataCompilerInput = [
            'request' => $request,
            'command' => 'new',
            'tableName' => self::FILE_REFERENCE_TABLE,
            'vanillaUid' => $fileReferenceVanillaUid,
            'isInlineChild' => true,
            'inlineStructure' => $inlineStructure,
            'inlineFirstPid' => $inlineFirstPid,
            'inlineParentUid' => $inlineParent['uid'],
            'inlineParentTableName' => $inlineParent['table'],
            'inlineParentFieldName' => $inlineParent['field'],
            'inlineParentConfig' => $parentConfig,
            'inlineTopMostParentUid' => $inlineTopMostParent['uid'],
            'inlineTopMostParentTableName' => $inlineTopMostParent['table'],
            'inlineTopMostParentFieldName' => $inlineTopMostParent['field'],
        ];
        if ($fileId) {
            $formDataCompilerInput['inlineChildChildUid'] = $fileId;
        }

        $fileReferenceData = $this->formDataCompiler->compile($formDataCompilerInput, GeneralUtility::makeInstance(TcaDatabaseRecord::class));

        $fileReferenceData['inlineParentUid'] = $inlineParent['uid'];
        $fileReferenceData['renderType'] = 'fileReferenceContainer';

        return $this->jsonResponse(
            $this->mergeFileReferenceResultIntoJsonResult(
                [
                    'data' => '',
                    'stylesheetFiles' => [],
                    'scriptItems' => new JavaScriptItems(),
                    'compilerInput' => [
                        'uid' => $fileReferenceData['databaseRow']['uid'],
                        'childChildUid' => $fileId,
                    ],
                ],
                $this->nodeFactory->create($fileReferenceData)->render()
            )
        );
    }

    /**
     * Show the details of a file reference
     */
    public function detailsAction(ServerRequestInterface $request): ResponseInterface
    {
        $arguments = $request->getParsedBody()['ajax'] ?? $request->getQueryParams()['ajax'];

        $domObjectId = (string)($arguments[0] ?? '');
        $inlineFirstPid = $this->getInlineFirstPidFromDomObjectId($domObjectId);
        $parentConfig = $this->extractSignedParentConfigFromRequest((string)($arguments['context'] ?? ''));

        $inlineStructure = $this->inlineStackProcessor->getStructureFromString($domObjectId);
        $inlineStructure = $this->inlineStackProcessor->addAjaxConfigurationToStructure($inlineStructure, $parentConfig);
        $inlineParent = $this->inlineStackProcessor->getStructureLevelFromStructure($inlineStructure, -1);
        $fileReference = $this->inlineStackProcessor->getUnstableStructureFromStructure($inlineStructure);

        $parentFieldName = $inlineParent['field'];

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
            'uid' => $inlineParent['uid'],
            'tableName' => $inlineParent['table'],
            'inlineFirstPid' => $inlineFirstPid,
            'returnUrl' => $parentConfig['originalReturnUrl'],
        ];

        $fileReferenceData = $this->compileFileReference($request, $parentData, $parentFieldName, (int)$fileReference['uid'], $inlineStructure);
        $fileReferenceData['inlineParentUid'] = (int)$inlineParent['uid'];
        $fileReferenceData['renderType'] = 'fileReferenceContainer';

        return $this->jsonResponse(
            $this->mergeFileReferenceResultIntoJsonResult(
                [
                    'data' => '',
                    'stylesheetFiles' => [],
                    'scriptItems' => new JavaScriptItems(),
                ],
                $this->nodeFactory->create($fileReferenceData)->render()
            )
        );
    }

    /**
     * Adds localizations or synchronizes the locations of all file references.
     */
    public function synchronizeLocalizeAction(ServerRequestInterface $request): ResponseInterface
    {
        $arguments = $request->getParsedBody()['ajax'];

        $domObjectId = (string)($arguments[0] ?? '');
        $type = $arguments[1] ?? null;
        $parentConfig = $this->extractSignedParentConfigFromRequest((string)($arguments['context'] ?? ''));

        $inlineStructure = $this->inlineStackProcessor->getStructureFromString($domObjectId);
        $inlineStructure = $this->inlineStackProcessor->addAjaxConfigurationToStructure($inlineStructure, $parentConfig);
        $inlineFirstPid = $this->getInlineFirstPidFromDomObjectId($domObjectId);

        $jsonArray = [
            'data' => '',
            'stylesheetFiles' => [],
            'scriptItems' => new JavaScriptItems(),
            'compilerInput' => [
                'localize' => [],
            ],
        ];
        if ($type === 'localize' || $type === 'synchronize' || MathUtility::canBeInterpretedAsInteger($type)) {
            // Parent, this table embeds the sys_file_reference table
            $inlineParent = $this->inlineStackProcessor->getStructureLevelFromStructure($inlineStructure, -1);
            $parentFieldName = $inlineParent['field'];

            $processedTca = $GLOBALS['TCA'][$inlineParent['table']];
            $processedTca['columns'][$parentFieldName]['config'] = $parentConfig;

            $formDataCompilerInputForParent = [
                'request' => $request,
                'vanillaUid' => (int)$inlineParent['uid'],
                'command' => 'edit',
                'tableName' => $inlineParent['table'],
                'processedTca' => $processedTca,
                'inlineFirstPid' => $inlineFirstPid,
                'columnsToProcess' => [
                    $parentFieldName,
                ],
                // @todo: still needed? NO!
                'inlineStructure' => $inlineStructure,
                // Do not compile existing file references, we don't need them now
                'inlineCompileExistingChildren' => false,
            ];
            // Full TcaDatabaseRecord is required here to have the list of connected uids $oldItemList
            $parentData = $this->formDataCompiler->compile($formDataCompilerInputForParent, GeneralUtility::makeInstance(TcaDatabaseRecord::class));
            $parentLanguageField = $parentData['processedTca']['ctrl']['languageField'];
            $parentLanguage = $parentData['databaseRow'][$parentLanguageField];
            $oldItemList = $parentData['databaseRow'][$parentFieldName];

            // DataHandler cannot handle arrays as field value
            if (is_array($parentLanguage)) {
                $parentLanguage = implode(',', $parentLanguage);
            }

            $cmd = [];
            // Localize a single file reference from default language of the inlineParent element
            if (MathUtility::canBeInterpretedAsInteger($type)) {
                $cmd[$inlineParent['table']][$inlineParent['uid']]['inlineLocalizeSynchronize'] = [
                    'field' => $inlineParent['field'],
                    'language' => $parentLanguage,
                    'ids' => [$type],
                ];
            } else {
                // Either localize or synchronize all file references from default language of the inlineParent element
                $cmd[$inlineParent['table']][$inlineParent['uid']]['inlineLocalizeSynchronize'] = [
                    'field' => $inlineParent['field'],
                    'language' => $parentLanguage,
                    'action' => $type,
                ];
            }

            $tce = GeneralUtility::makeInstance(DataHandler::class);
            $tce->start([], $cmd);
            $tce->process_cmdmap();

            $oldItems = $this->getFileReferenceUids((string)$oldItemList);

            $newItemList = (string)($tce->registerDBList[$inlineParent['table']][$inlineParent['uid']][$parentFieldName] ?? '');
            $newItems = $this->getFileReferenceUids($newItemList);

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
                    if ($message->getSeverity() === ContextualFeedbackSeverity::ERROR) {
                        $jsonArray['hasErrors'] = true;
                    }
                }
            }

            // Set the items that should be removed in the forms view:
            $removedItems = array_diff($oldItems, $newItems);
            $jsonArray['compilerInput']['delete'] = $removedItems;

            $localizedItems = array_diff($newItems, $oldItems);
            foreach ($localizedItems as $i => $localizedFileReferenceUid) {
                $fileReferenceData = $this->compileFileReference($request, $parentData, $parentFieldName, (int)$localizedFileReferenceUid, $inlineStructure);
                $fileReferenceData['inlineParentUid'] = (int)$inlineParent['uid'];
                $fileReferenceData['renderType'] = 'fileReferenceContainer';

                $jsonArray = $this->mergeFileReferenceResultIntoJsonResult(
                    $jsonArray,
                    $this->nodeFactory->create($fileReferenceData)->render()
                );

                // Get the name of the field used as foreign selector (if any):
                $selectedValue = $fileReferenceData['databaseRow']['uid_local'];
                if (is_array($selectedValue)) {
                    $selectedValue = $selectedValue[0];
                }

                $jsonArray['compilerInput']['localize'][$i] = [
                    'uid' => $localizedFileReferenceUid,
                    'selectedValue' => $selectedValue,
                ];

                // Remove possible virtual records in the form which showed that a file reference could be
                // localized:
                $transOrigPointerFieldName = $fileReferenceData['processedTca']['ctrl']['transOrigPointerField'];
                if (isset($fileReferenceData['databaseRow'][$transOrigPointerFieldName]) && $fileReferenceData['databaseRow'][$transOrigPointerFieldName]) {
                    $transOrigPointerFieldValue = $fileReferenceData['databaseRow'][$transOrigPointerFieldName];
                    if (is_array($transOrigPointerFieldValue)) {
                        $transOrigPointerFieldValue = $transOrigPointerFieldValue[0];
                        if (is_array($transOrigPointerFieldValue) && ($transOrigPointerFieldValue['uid'] ?? false)) {
                            $transOrigPointerFieldValue = $transOrigPointerFieldValue['uid'];
                        }
                    }
                    $jsonArray['compilerInput']['localize'][$i]['remove'] = $transOrigPointerFieldValue;
                }
            }
        }
        return $this->jsonResponse($jsonArray);
    }

    /**
     * Store status of file references' expand / collapse state in backend user UC.
     */
    public function expandOrCollapseAction(ServerRequestInterface $request): ResponseInterface
    {
        [$domObjectId, $expand, $collapse] = $request->getParsedBody()['ajax'];

        $inlineStructure = $this->inlineStackProcessor->getStructureFromString($domObjectId);
        $currentTable = $this->inlineStackProcessor->getUnstableStructureFromStructure($inlineStructure)['table'];
        $top = $this->inlineStackProcessor->getStructureLevelFromStructure($inlineStructure, 0);
        $stateArray = $this->getReferenceExpandCollapseStateArray();
        // Only do some action if the top record and the current record were saved before
        if (MathUtility::canBeInterpretedAsInteger($top['uid'])) {
            // Set records to be expanded
            foreach (GeneralUtility::trimExplode(',', $expand) as $uid) {
                $stateArray[$top['table']][$top['uid']][$currentTable][] = $uid;
            }
            // Set records to be collapsed
            foreach (GeneralUtility::trimExplode(',', $collapse) as $uid) {
                $stateArray[$top['table']][$top['uid']][$currentTable] = $this->removeFromArray(
                    $uid,
                    $stateArray[$top['table']][$top['uid']][$currentTable]
                );
            }
            // Save states back to database
            if (is_array($stateArray[$top['table']][$top['uid']][$currentTable] ?? false)) {
                $stateArray[$top['table']][$top['uid']][$currentTable] = array_unique($stateArray[$top['table']][$top['uid']][$currentTable]);
                $backendUser = $this->getBackendUserAuthentication();
                $backendUser->uc['inlineView'] = json_encode($stateArray);
                $backendUser->writeUC();
            }
        }
        return $this->jsonResponse();
    }

    protected function compileFileReference(ServerRequestInterface $request, array $parentData, $parentFieldName, $fileReferenceUid, array $inlineStructure): array
    {
        $inlineTopMostParent = $this->inlineStackProcessor->getStructureLevelFromStructure($inlineStructure, 0);
        return $this->formDataCompiler
            ->compile(
                [
                    'request' => $request,
                    'command' => 'edit',
                    'tableName' => self::FILE_REFERENCE_TABLE,
                    'vanillaUid' => (int)$fileReferenceUid,
                    'returnUrl' => $parentData['returnUrl'],
                    'isInlineChild' => true,
                    'inlineStructure' => $inlineStructure,
                    'inlineFirstPid' => $parentData['inlineFirstPid'],
                    'inlineParentConfig' => $parentData['processedTca']['columns'][$parentFieldName]['config'],
                    'isInlineAjaxOpeningContext' => true,
                    'inlineParentUid' => $parentData['databaseRow']['uid'] ?? $parentData['uid'],
                    'inlineParentTableName' => $parentData['tableName'],
                    'inlineParentFieldName' => $parentFieldName,
                    'inlineTopMostParentUid' => $inlineTopMostParent['uid'],
                    'inlineTopMostParentTableName' => $inlineTopMostParent['table'],
                    'inlineTopMostParentFieldName' => $inlineTopMostParent['field'],
                ],
                GeneralUtility::makeInstance(TcaDatabaseRecord::class)
            );
    }

    /**
     * Merge compiled file reference data into the json result array.
     */
    protected function mergeFileReferenceResultIntoJsonResult(array $jsonResult, array $fileReferenceData): array
    {
        /** @var JavaScriptItems $scriptItems */
        $scriptItems = $jsonResult['scriptItems'];

        $jsonResult['data'] .= $fileReferenceData['html'];
        $jsonResult['stylesheetFiles'] = [];
        foreach ($fileReferenceData['stylesheetFiles'] as $stylesheetFile) {
            $jsonResult['stylesheetFiles'][] = $this->getRelativePathToStylesheetFile($stylesheetFile);
        }
        if (!empty($fileReferenceData['inlineData'])) {
            $jsonResult['inlineData'] = $fileReferenceData['inlineData'];
        }
        if (!empty($fileReferenceData['additionalInlineLanguageLabelFiles'])) {
            $labels = [];
            foreach ($fileReferenceData['additionalInlineLanguageLabelFiles'] as $additionalInlineLanguageLabelFile) {
                ArrayUtility::mergeRecursiveWithOverrule(
                    $labels,
                    $this->getLabelsFromLocalizationFile($additionalInlineLanguageLabelFile)
                );
            }
            $scriptItems->addGlobalAssignment(['TYPO3' => ['lang' => $labels]]);
        }
        $this->addJavaScriptModulesToJavaScriptItems($fileReferenceData['javaScriptModules'] ?? [], $scriptItems);

        return $jsonResult;
    }

    /**
     * Gets an array with the uids of file references out of a list of items.
     */
    protected function getFileReferenceUids(string $itemList): array
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
     * Get expand / collapse state of inline items
     */
    protected function getReferenceExpandCollapseStateArray(): array
    {
        $backendUser = $this->getBackendUserAuthentication();
        if (empty($backendUser->uc['inlineView'])) {
            return [];
        }

        $state = json_decode($backendUser->uc['inlineView'], true);
        if (!is_array($state)) {
            $state = [];
        }

        return $state;
    }

    /**
     * Remove an element from an array.
     */
    protected function removeFromArray(mixed $needle, array $haystack, bool $strict = false): array
    {
        $pos = array_search($needle, $haystack, $strict);
        if ($pos !== false) {
            unset($haystack[$pos]);
        }
        return $haystack;
    }

    /**
     * Get inlineFirstPid from a given objectId string
     */
    protected function getInlineFirstPidFromDomObjectId(string $domObjectId): int|string|null
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
     */
    protected function extractSignedParentConfigFromRequest(string $contextString): array
    {
        if ($contextString === '') {
            throw new \RuntimeException('Empty context string given', 1664486783);
        }
        $context = json_decode($contextString, true);
        if (empty($context['config'])) {
            throw new \RuntimeException('Empty context config section given', 1664486790);
        }
        if (!hash_equals($this->hashService->hmac((string)$context['config'], 'FilesContext'), (string)$context['hmac'])) {
            throw new \RuntimeException('Hash does not validate', 1664486791);
        }
        return json_decode($context['config'], true);
    }

    protected function jsonResponse(array $json = []): ResponseInterface
    {
        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withBody($this->streamFactory->createStream((string)json_encode($json)));
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
