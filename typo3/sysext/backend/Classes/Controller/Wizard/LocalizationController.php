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

namespace TYPO3\CMS\Backend\Controller\Wizard;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Controller\Event\AfterPageColumnsSelectedForLocalizationEvent;
use TYPO3\CMS\Backend\Controller\Event\AfterRecordSummaryForLocalizationEvent;
use TYPO3\CMS\Backend\Domain\Repository\Localization\LocalizationRepository;
use TYPO3\CMS\Backend\Localization\LocalizationHandlerInterface;
use TYPO3\CMS\Backend\Localization\LocalizationHandlerRegistry;
use TYPO3\CMS\Backend\Localization\LocalizationInstructions;
use TYPO3\CMS\Backend\Localization\LocalizationMode;
use TYPO3\CMS\Backend\Localization\LocalizationResult;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendLayoutView;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Versioning\VersionState;

/**
 * LocalizationController handles the AJAX requests for the localization wizard.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Autoconfigure(public: true)]
readonly class LocalizationController
{
    public function __construct(
        protected IconFactory $iconFactory,
        protected LocalizationRepository $localizationRepository,
        protected EventDispatcherInterface $eventDispatcher,
        protected LocalizationHandlerInterface $localizationHandler,
        protected LocalizationHandlerRegistry $localizationHandlerRegistry,
        protected TcaSchemaFactory $schemaFactory,
        protected TranslationConfigurationProvider $translationConfigurationProvider,
        protected BackendLayoutView $backendLayoutView,
    ) {}

    /**
     * Get record information for localization wizard
     */
    public function getRecord(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (!isset($params['recordType'], $params['recordUid'])) {
            return new JsonResponse(null, 400);
        }

        $recordType = $params['recordType'];
        $recordUid = (int)$params['recordUid'];

        $record = BackendUtility::getRecord($recordType, $recordUid);
        if (!$record) {
            return new JsonResponse(null, 404);
        }

        $schema = $this->schemaFactory->get($recordType);
        $recordInfo = [
            'uid' => $record['uid'],
            'title' => BackendUtility::getRecordTitle($recordType, $record),
            'icon' => $this->iconFactory->getIconForRecord($recordType, $record, IconSize::SMALL)->getIdentifier(),
            'type' => $recordType,
            'typeName' => $schema->getTitle($this->getLanguageService()->sL(...)),
        ];

        return new JsonResponse($recordInfo);
    }

    /**
     * Get available localization handlers
     *
     * Returns handlers filtered by the localization context
     */
    public function getHandlers(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();

        try {
            $localizationInstructions = LocalizationInstructions::create($params);
        } catch (\ValueError) {
            return new JsonResponse(['error' => 'Invalid localization mode'], 400);
        } catch (\InvalidArgumentException) {
            // Validate required parameters
            return new JsonResponse(null, 400);
        }

        // Get available handlers from registry
        $handlers = $this->localizationHandlerRegistry->getAvailableHandlers($localizationInstructions);

        // Prepare handlers for JSON response with translated labels
        $result = [];
        foreach ($handlers as $handler) {
            $result[] = [
                'identifier' => $handler->getIdentifier(),
                'label' => $this->getLanguageService()->sL($handler->getLabel()),
                'description' => $this->getLanguageService()->sL($handler->getDescription()),
                'iconIdentifier' => $handler->getIconIdentifier(),
            ];
        }

        return new JsonResponse($result);
    }

    /**
     * Get available localization modes
     */
    public function getModes(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (!isset($params['recordType'], $params['recordUid'], $params['targetLanguage'])) {
            return new JsonResponse(null, 400);
        }

        $recordType = $params['recordType'];
        $recordUid = (int)$params['recordUid'];
        $targetLanguage = (int)$params['targetLanguage'];

        // For pages, use the recordUid directly as the page
        // For other record types, find the parent page
        if ($recordType === 'pages') {
            $page = $recordUid;
        } else {
            // Get the record to find its parent page
            $record = BackendUtility::getRecord($recordType, $recordUid);
            if (!$record) {
                return new JsonResponse(null, 404);
            }
            $page = (int)$record['pid'];
        }

        // Get page record for permission checks
        $pageRecord = BackendUtility::readPageAccess($page, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        if (!$pageRecord) {
            return new JsonResponse(null, 403);
        }

        // Get available modes based on PageTSconfig
        $pageTsConfig = BackendUtility::getPagesTSconfig($page);
        $schema = $this->schemaFactory->get($recordType);
        if (!$schema->hasCapability(TcaSchemaCapability::Language)) {
            // Table is not language-aware
            return new JsonResponse(null, 400);
        }
        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);

        $availableModes = array_filter(
            LocalizationMode::cases(),
            static function (LocalizationMode $mode) use ($pageTsConfig, $languageCapability): bool {
                return match ($mode) {
                    LocalizationMode::COPY => ($pageTsConfig['mod.']['web_layout.']['localization.']['enableCopy'] ?? true) && $languageCapability->hasTranslationSourceField(),
                    LocalizationMode::TRANSLATE => (bool)($pageTsConfig['mod.']['web_layout.']['localization.']['enableTranslate'] ?? true),
                };
            }
        );

        // Check if there are existing translations in the target language
        // If so, we need to ensure we don't mix localization modes
        // This check is only relevant for pages and tt_content records
        if ($recordType === 'pages' || $recordType === 'tt_content') {
            $existingMode = $this->detectExistingLocalizationMode($page, $targetLanguage);
            if ($existingMode !== null) {
                // Filter to only allow the existing mode
                $availableModes = array_filter(
                    $availableModes,
                    static fn(LocalizationMode $mode): bool => $mode === $existingMode
                );
            }
        }

        // Sort by priority (highest first)
        usort($availableModes, static fn(LocalizationMode $a, LocalizationMode $b): int => $b->getPriority() <=> $a->getPriority());

        $modes = array_map(
            fn(LocalizationMode $mode): array => [
                'key' => $mode->value,
                'label' => $this->getLanguageService()->sL($mode->getLabel()),
                'description' => $this->getLanguageService()->sL($mode->getDescription()),
                'iconIdentifier' => $mode->getIconIdentifier(),
            ],
            $availableModes
        );

        return new JsonResponse($modes);
    }

    /**
     * Get all target languages available for translation (excluding default language)
     */
    public function getTargets(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (!isset($params['recordType'], $params['recordUid'])) {
            return new JsonResponse(null, 400);
        }

        $recordType = $params['recordType'];
        $recordUid = (int)$params['recordUid'];

        // For pages, use the recordUid directly as the page
        // For other record types, find the parent page
        if ($recordType === 'pages') {
            $page = $recordUid;
        } else {
            // Get the record to find its parent page
            $record = BackendUtility::getRecord($recordType, $recordUid);
            if (!$record) {
                return new JsonResponse(null, 404);
            }
            $page = (int)$record['pid'];
        }

        // Get page record for permission checks
        $pageRecord = BackendUtility::readPageAccess($page, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        if (!$pageRecord) {
            return new JsonResponse(null, 403);
        }

        $systemLanguages = $this->translationConfigurationProvider->getSystemLanguages($page);
        $availableLanguages = [];
        foreach ($systemLanguages as $languageUid => $language) {
            // Exclude "All languages" (-1) and default language (0) for target language selection
            if ($languageUid !== -1 && $languageUid !== 0) {
                $availableLanguages[] = $language;
            }
        }

        return new JsonResponse($availableLanguages);
    }

    /**
     * Get source languages that have content and can be used as translation base
     */
    public function getSources(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (!isset($params['recordType'], $params['recordUid'], $params['targetLanguage'])) {
            return new JsonResponse(null, 400);
        }

        $recordType = (string)$params['recordType'];
        $recordUid = (int)$params['recordUid'];
        $targetLanguage = (int)$params['targetLanguage'];

        // For pages, use the recordUid directly as the page
        // For other record types, find the parent page
        if ($recordType === 'pages') {
            $page = $recordUid;
        } else {
            // Get the record to find its parent page
            $record = BackendUtility::getRecord($recordType, $recordUid);
            if (!$record) {
                return new JsonResponse(null, 404);
            }
            $page = (int)$record['pid'];
        }

        // Get page record for permission checks
        $pageRecord = BackendUtility::readPageAccess($page, $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW));
        if (!$pageRecord) {
            return new JsonResponse(null, 403);
        }

        $systemLanguages = $this->translationConfigurationProvider->getSystemLanguages($page);
        $availableLanguages = [];

        // Find all existing translations of the record
        $record = BackendUtility::getRecord($recordType, $recordUid);
        if ($record) {
            $existingLanguageUids = [0]; // Always include default language

            // Check each system language to see if a translation exists
            foreach (array_keys($systemLanguages) as $languageUid) {
                if ($languageUid > 0) { // Skip default language (0) and "All languages" (-1)
                    $translation = $this->localizationRepository->getRecordTranslation($recordType, $record, (int)$languageUid);
                    if ($translation !== null) {
                        $existingLanguageUids[] = $languageUid;
                    }
                }
            }

            foreach ($existingLanguageUids as $languageUid) {
                if ($languageUid !== $targetLanguage && isset($systemLanguages[$languageUid])) {
                    $availableLanguages[] = $systemLanguages[$languageUid];
                }
            }

            // For pages with existing translations in the target language, we need to restrict source languages
            // to prevent mixed translation origins (e.g., some content from language A, some from language B)
            if ($recordType === 'pages') {
                $availableLanguages = $this->filterSourceLanguagesForPage($recordUid, $targetLanguage, $availableLanguages);
            }
        }

        // Language "All" should not appear as a source of translations (see bug 92757) and keys should be sequential
        $availableLanguages = array_values(
            array_filter($availableLanguages, static function (array $languageRecord): bool {
                return (int)$languageRecord['uid'] !== -1;
            })
        );

        return new JsonResponse($availableLanguages);
    }

    /**
     * Get page layout and records for localization
     */
    public function getContent(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (!isset($params['pageUid'], $params['targetLanguage'], $params['sourceLanguage'])) {
            return new JsonResponse(null, 400);
        }

        $pageUid = (int)$params['pageUid'];
        $targetLanguage = (int)$params['targetLanguage'];
        $sourceLanguage = (int)$params['sourceLanguage'];

        $records = [];
        $result = $this->localizationRepository->getRecordsToCopyDatabaseResult(
            $pageUid,
            $targetLanguage,
            $sourceLanguage,
            $this->getBackendUser()->workspace
        );

        $flatRecords = [];
        while ($row = $result->fetchAssociative()) {
            BackendUtility::workspaceOL('tt_content', $row, $this->getBackendUser()->workspace, true);
            if (!$row || VersionState::tryFrom($row['t3ver_state'] ?? 0) === VersionState::DELETE_PLACEHOLDER) {
                continue;
            }
            $colPos = $row['colPos'];

            if (!$this->backendLayoutView->isCTypeAllowedInColPosByPage($row['CType'], $colPos, $pageUid)) {
                continue;
            }

            if (!isset($records[$colPos])) {
                $records[$colPos] = [];
            }
            $records[$colPos][] = [
                'icon' => $this->iconFactory->getIconForRecord('tt_content', $row, IconSize::SMALL)->getIdentifier(),
                'title' => BackendUtility::getRecordTitle('tt_content', $row),
                'uid' => $row['uid'],
            ];
            $flatRecords[] = $row;
        }

        $columns = $this->getPageColumns($pageUid, $flatRecords, $params);
        $event = new AfterRecordSummaryForLocalizationEvent($records, $columns);
        $this->eventDispatcher->dispatch($event);

        // Get the backend layout structure for visual representation
        $backendLayout = $this->backendLayoutView->getBackendLayoutForPage($pageUid);
        $layoutStructure = $this->buildLayoutStructure($backendLayout, $event->getColumns(), $event->getRecords());

        return new JsonResponse([
            'layout' => $layoutStructure,
        ]);
    }

    public function localize(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody();

        if (!isset(
            $params['recordType'],
            $params['recordUid'],
            $params['data']['sourceLanguage'],
            $params['data']['targetLanguage'],
            $params['data']['localizationMode']
        )) {
            return new JsonResponse(null, 400);
        }

        $recordType = $params['recordType'];
        $recordUid = (int)$params['recordUid'];
        $sourceLanguage = (int)$params['data']['sourceLanguage'];
        $targetLanguage = (int)$params['data']['targetLanguage'];
        $modeIdentifier = $params['data']['localizationMode'];
        $handlerIdentifier = $params['data']['localizationHandler'] ?? 'manual';

        // Prepare Additional Data
        $additionalData = $params['data'];
        unset($additionalData['sourceLanguage']);
        unset($additionalData['targetLanguage']);
        unset($additionalData['localizationMode']);
        unset($additionalData['localizationHandler']);

        // Validate that the mode exists
        $mode = LocalizationMode::tryFrom($modeIdentifier);
        if ($mode === null) {
            $response = new Response('php://temp', 400, ['Content-Type' => 'application/json; charset=utf-8']);
            $response->getBody()->write('Invalid localization mode "' . $modeIdentifier . '" called.');
            return $response;
        }

        try {
            $localizationInstructions = new LocalizationInstructions(
                $recordType,
                $recordUid,
                $sourceLanguage,
                $targetLanguage,
                $mode,
                $additionalData
            );
        } catch (\ValueError) {
            return new JsonResponse(['error' => 'Invalid localization mode'], 400);
        } catch (\InvalidArgumentException) {
            // Validate required parameters
            return new JsonResponse(null, 400);
        }

        // Process the localization using the handler
        try {
            // Get the handler from the registry or fall back to the default handler
            if ($this->localizationHandlerRegistry->hasHandler($handlerIdentifier)) {
                $handler = $this->localizationHandlerRegistry->getHandler($handlerIdentifier);
            } else {
                $handler = $this->localizationHandler;
            }

            // Use the handler to process the localization with the selected mode
            $result = $handler->processLocalization($localizationInstructions);
        } catch (\Exception $e) {
            $result = LocalizationResult::error([$e->getMessage()]);
        }
        return new JsonResponse($result->jsonSerialize());
    }

    private function getPageColumns(int $page, array $flatRecords, array $params): array
    {
        $columns = [];
        $backendLayout = $this->backendLayoutView->getBackendLayoutForPage($page);

        foreach ($backendLayout->getUsedColumns() as $columnPos => $columnLabel) {
            $columns[$columnPos] = $this->getLanguageService()->sL($columnLabel);
        }

        $event = new AfterPageColumnsSelectedForLocalizationEvent($columns, [], $backendLayout, $flatRecords, $params);
        $this->eventDispatcher->dispatch($event);

        return $event->getColumns();
    }

    private function buildLayoutStructure($backendLayout, array $columns, array $records): array
    {
        // Calculate total elements across all columns
        $elementCount = 0;
        foreach ($records as $colPos => $columnRecords) {
            if (is_array($columnRecords)) {
                $elementCount += count($columnRecords);
            }
        }

        if (!$backendLayout) {
            // Create a simple single-row layout when no backend layout is available
            $layoutColumns = [];
            foreach ($columns as $colPos => $columnLabel) {
                $layoutColumns[] = [
                    'position' => (int)$colPos,
                    'label' => $columnLabel,
                    'records' => $records[$colPos] ?? [],
                    'colspan' => 1,
                    'rowspan' => 1,
                    'identifier' => null,
                ];
            }

            return [
                'type' => 'layout',
                'title' => 'Default Layout',
                'identifier' => 'default',
                'colCount' => count($columns),
                'rowCount' => 1,
                'elementCount' => $elementCount,
                'rows' => [
                    [
                        'columns' => $layoutColumns,
                    ],
                ],
            ];
        }

        $structure = $backendLayout->getStructure();
        $layoutRows = [];

        if (!empty($structure['__config']['backend_layout.']['rows.'])) {
            $rows = $structure['__config']['backend_layout.']['rows.'];
            ksort($rows);

            foreach ($rows as $row) {
                $layoutColumns = [];

                if (!empty($row['columns.'])) {
                    foreach ($row['columns.'] as $column) {
                        if (!isset($column['colPos'])) {
                            continue;
                        }

                        $colPos = (int)$column['colPos'];
                        $layoutColumns[] = [
                            'position' => $colPos,
                            'label' => $columns[$colPos] ?? $column['name'],
                            'records' => $records[$colPos] ?? [],
                            'colspan' => (int)($column['colspan'] ?? 1),
                            'rowspan' => (int)($column['rowspan'] ?? 1),
                            'identifier' => $column['identifier'] ?? null,
                        ];
                    }
                }

                $layoutRows[] = [
                    'columns' => $layoutColumns,
                ];
            }
        }

        return [
            'type' => 'layout',
            'title' => $backendLayout->getTitle(),
            'identifier' => $backendLayout->getIdentifier(),
            'colCount' => $backendLayout->getColCount(),
            'rowCount' => $backendLayout->getRowCount(),
            'elementCount' => $elementCount,
            'rows' => $layoutRows,
        ];
    }

    /**
     * Filter available source languages for page translations based on existing content
     *
     * For pages, when translations already exist in the target language with content assigned,
     * we need to ensure that new content is only translated from the same source language(s)
     * as the existing content to avoid creating mixed translations in terms of language origin.
     *
     * @param int $pageUid The page UID being translated
     * @param int $targetLanguage The target language ID
     * @param array $availableLanguages All available source languages (to be filtered)
     * @return array Filtered available language configurations
     */
    private function filterSourceLanguagesForPage(int $pageUid, int $targetLanguage, array $availableLanguages): array
    {
        // Check if a page translation exists in the target language
        $pageTranslation = $this->localizationRepository->getPageTranslations($pageUid, [$targetLanguage], $this->getBackendUser()->workspace);
        if ($pageTranslation === []) {
            return $availableLanguages;
        }

        // Get source languages used by existing content in the target language
        // Note: Content elements are stored on the original page with sys_language_uid set to the target language
        $usedSourceLanguages = $this->getUsedSourceLanguagesForPage($pageUid, $targetLanguage);

        if (empty($usedSourceLanguages)) {
            return $availableLanguages;
        }

        // Filter to only allow source languages already in use
        return array_filter(
            $availableLanguages,
            static fn(array $language): bool => isset($usedSourceLanguages[(int)$language['uid']])
        );
    }

    /**
     * Get the source languages used by existing content on a page
     *
     * @param int $pageUid The page UID
     * @param int $targetLanguage The target language ID
     * @return array<int, true> Map of source language UIDs that are in use
     */
    private function getUsedSourceLanguagesForPage(int $pageUid, int $targetLanguage): array
    {
        $schema = $this->schemaFactory->get('tt_content');
        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);

        if (!$languageCapability->hasTranslationSourceField()) {
            return [];
        }

        $languageField = $languageCapability->getLanguageField()->getName();
        $translationSourceField = $languageCapability->getTranslationSourceField()->getName();

        // Get all l10n_source UIDs from translated content
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));

        $result = $queryBuilder
            ->select($translationSourceField)
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageUid, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $languageField,
                    $queryBuilder->createNamedParameter($targetLanguage, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    $translationSourceField,
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->executeQuery();

        // Collect unique source UIDs
        $sourceUids = [];
        while ($row = $result->fetchAssociative()) {
            $sourceUid = (int)$row[$translationSourceField];
            $sourceUids[$sourceUid] = $sourceUid;
        }

        if (empty($sourceUids)) {
            return [];
        }

        // Get the language of all source records
        $sourceQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');
        $sourceQueryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $sourceResult = $sourceQueryBuilder
            ->select($languageField)
            ->from('tt_content')
            ->where(
                $sourceQueryBuilder->expr()->in(
                    'uid',
                    $sourceQueryBuilder->createNamedParameter($sourceUids, Connection::PARAM_INT_ARRAY)
                )
            )
            ->groupBy($languageField)
            ->executeQuery();

        $usedSourceLanguages = [];
        while ($row = $sourceResult->fetchAssociative()) {
            $sourceLanguageUid = (int)$row[$languageField];
            $usedSourceLanguages[$sourceLanguageUid] = true;
        }

        return $usedSourceLanguages;
    }

    /**
     * Detect the localization mode used by existing translations on a page
     *
     * This method checks if there are existing content elements in the target language
     * and determines whether they were created using COPY (free) or TRANSLATE (connected) mode.
     * This prevents mixing different localization modes on the same page, which would lead to
     * inconsistent translation workflows.
     *
     * Note: Pages themselves are always created in connected mode (using 'localize' command),
     * so we check the content elements on the page to determine the actual localization mode.
     *
     * The distinction is made by checking the translation origin pointer field obtained from the
     * schema's language capability:
     * - TRANSLATE mode (connected): Records have translation origin pointer > 0 (linked to source language)
     * - COPY mode (free): Records have translation origin pointer = 0 (independent copies)
     *
     * @param int $pageId The page ID to check
     * @param int $targetLanguage The target language ID
     * @return LocalizationMode|null The detected mode, or null if no translations exist
     */
    private function detectExistingLocalizationMode(int $pageId, int $targetLanguage): ?LocalizationMode
    {
        // Get the TCA schema to determine the correct field names
        $schema = $this->schemaFactory->get('tt_content');

        if (!$schema->hasCapability(TcaSchemaCapability::Language)) {
            // Table is not language-aware
            return null;
        }

        $languageCapability = $schema->getCapability(TcaSchemaCapability::Language);
        $transOrigPointerField = $languageCapability->getTranslationOriginPointerField()->getName();
        $languageField = $languageCapability->getLanguageField()->getName();

        // Check content elements on the page, as pages themselves are always connected
        // but their content determines the actual localization mode being used
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tt_content');

        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, $this->getBackendUser()->workspace));

        // Select only the translation pointer field to determine the mode
        $result = $queryBuilder
            ->select($transOrigPointerField)
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    $languageField,
                    $queryBuilder->createNamedParameter($targetLanguage, Connection::PARAM_INT)
                )
            )
            ->executeQuery();

        $hasRecords = false;
        $hasConnected = false;

        // Iterate through results to detect the mode
        while ($row = $result->fetchAssociative()) {
            $hasRecords = true;
            $transOrigPointer = (int)($row[$transOrigPointerField] ?? 0);

            // If we find any connected record (transOrigPointer > 0), return TRANSLATE immediately
            // This prevents mixing modes even if there are also free mode records
            if ($transOrigPointer > 0) {
                $hasConnected = true;
                break;
            }
        }

        // No records found
        if (!$hasRecords) {
            return null;
        }

        // If any connected record exists, return TRANSLATE mode
        // Otherwise, all records are free mode, return COPY
        return $hasConnected ? LocalizationMode::TRANSLATE : LocalizationMode::COPY;
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
