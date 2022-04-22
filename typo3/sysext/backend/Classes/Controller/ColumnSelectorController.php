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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Controller for handling the display column selection for records, typically executed from list modules.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class ColumnSelectorController
{
    private const PSEUDO_FIELDS = ['_REF_', '_PATH_'];
    private const EXCLUDE_FILE_FIELDS = [
        'pid', // Not relevant as all records are on pid=0
        'identifier', // Handled manually in listing
        'name', // Handled manually in listing
        'metadata', // The reference to the meta data is not relevant
        'file', // The reference to the file is not relevant
        'sys_language_uid', // Not relevant in listing since only default is displayed
        'l10n_parent', // Not relevant in listing
        't3ver_state', // Not relevant in listing
        't3ver_wsid', // Not relevant in listing
        't3ver_oid', // Not relevant in listing
    ];

    protected ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Update the columns to be displayed for the given table
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function updateVisibleColumnsAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $table = (string)($parsedBody['table'] ?? '');
        $selectedColumns = $parsedBody['selectedColumns'] ?? [];

        if ($table === '' || !is_array($selectedColumns)) {
            return $this->jsonResponse([
                'success' => false,
                'message' => htmlspecialchars(
                    $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_column_selector.xlf:updateColumnView.nothingUpdated')
                ),
           ]);
        }

        $backendUser = $this->getBackendUserAuthentication();
        $displayFields = $backendUser->getModuleData('list/displayFields');
        $displayFields[$table] = $selectedColumns;
        $backendUser->pushModuleData('list/displayFields', $displayFields);

        return $this->jsonResponse(['success' => true]);
    }

    /**
     * Generate the show columns selector form
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function showColumnsSelectorAction(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $table = (string)($queryParams['table'] ?? '');

        if ($table === '') {
            throw new \RuntimeException('No table was given for selecting columns', 1625169125);
        }

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:backend/Resources/Private/Templates/ColumnSelector.html'
        ));

        $view->assignMultiple([
            'table' => $table,
            'columns' => $this->getColumns($table, (int)($queryParams['id'] ?? 0)),
        ]);

        return $this->htmlResponse($view);
    }

    /**
     * Retrieve all columns for the table, which can be selected
     *
     * @param string $table
     * @param int $pageId
     * @return array
     */
    protected function getColumns(string $table, int $pageId): array
    {
        $tsConfig = BackendUtility::getPagesTSconfig($pageId);

        // Current fields selection
        $displayFields = $this->getBackendUserAuthentication()->getModuleData('list/displayFields')[$table] ?? [];

        if ($table === '_FILE') {
            // Special handling for _FILE (merging sys_file and sys_file_metadata together)
            $fields = $this->getFileFields();
        } else {
            // Request fields from table and add pseudo fields
            $fields = array_merge(BackendUtility::getAllowedFieldsForTable($table), self::PSEUDO_FIELDS);
        }

        $columns = $specialColumns = $disabledColumns = [];
        foreach ($fields as $fieldName) {
            $concreteTableName = $table;

            // In case we deal with _FILE, the field name is prefixed with the
            // concrete table name, which is either sys_file or sys_file_metadata.
            if ($table === '_FILE') {
                [$concreteTableName, $fieldName] = explode('|', $fieldName);
            }

            // Hide field if disabled
            if ($tsConfig['TCEFORM.'][$concreteTableName . '.'][$fieldName . '.']['disabled'] ?? false) {
                continue;
            }

            // Determine if the column should be disabled (Meaning it is always selected and can not be turned off)
            $isDisabled = $fieldName === ($GLOBALS['TCA'][$concreteTableName]['ctrl']['label'] ?? false);

            // Determine field label
            $label = BackendUtility::getItemLabel($concreteTableName, $fieldName);
            if ($label) {
                if (!empty($tsConfig['TCEFORM.'][$concreteTableName . '.'][$fieldName . '.']['label.'][$this->getLanguageService()->lang])) {
                    $label = $tsConfig['TCEFORM.'][$concreteTableName . '.'][$fieldName . '.']['label.'][$this->getLanguageService()->lang];
                } elseif (!empty($tsConfig['TCEFORM.'][$concreteTableName . '.'][$fieldName . '.']['label'])) {
                    $label = $tsConfig['TCEFORM.'][$concreteTableName . '.'][$fieldName . '.']['label'];
                }
            } elseif ($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.' . $fieldName)) {
                $label = 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.' . $fieldName;
            } else {
                $label = '';
            }

            // Add configuration for this column
            $columnConfiguration = [
                'name' => $fieldName,
                'selected' => $isDisabled || in_array($fieldName, $displayFields, true),
                'disabled' => $isDisabled,
                'pseudo' => in_array($fieldName, self::PSEUDO_FIELDS, true),
                'label' => $this->getLanguageService()->sL($label),
            ];

            // Add column configuration to the correct group
            if ($columnConfiguration['disabled']) {
                $disabledColumns[] = $columnConfiguration;
            } elseif (!$columnConfiguration['label']) {
                $specialColumns[] = $columnConfiguration;
            } else {
                $columns[] = $columnConfiguration;
            }
        }

        // Sort standard columns by their resolved label
        usort($columns, static fn ($a, $b) => $a['label'] <=> $b['label']);

        // Disabled columns go first, followed by standard columns
        // and special columns, which do not have a label.
        return array_merge($disabledColumns, $columns, $specialColumns);
    }

    /**
     * Get file related fields by merging sys_file and sys_file_metadata together
     * and adding the corresponding table as prefix (needed for labels processing).
     *
     * @return array
     */
    protected function getFileFields(): array
    {
        // Get all sys_file fields expect excluded ones
        $fileFields = array_filter(
            BackendUtility::getAllowedFieldsForTable('sys_file'),
            static fn (string $field): bool => !in_array($field, self::EXCLUDE_FILE_FIELDS, true)
        );

        // Always add crdate and tstamp fields for files
        $fileFields = array_unique(array_merge($fileFields, ['crdate', 'tstamp']));

        // Update the exclude fields with the fields, already added through sys_file, since those take precedence
        $excludeFields = array_merge($fileFields, self::EXCLUDE_FILE_FIELDS);

        // Get all sys_file_metadata fields expect excluded ones
        $fileMetaDataFields = array_filter(
            BackendUtility::getAllowedFieldsForTable('sys_file_metadata'),
            static fn (string $field): bool => !in_array($field, $excludeFields, true)
        );

        // Merge sys_file and sys_file_metadata fields together, while adding the table name as prefix
        return array_merge(
            array_map(static fn (string $value): string => 'sys_file|' . $value, $fileFields),
            array_map(static fn (string $value): string => 'sys_file_metadata|' . $value, $fileMetaDataFields),
        );
    }

    protected function htmlResponse(ViewInterface $view): ResponseInterface
    {
        $response = $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8');

        $response->getBody()->write($view->render());
        return $response;
    }

    protected function jsonResponse(array $data): ResponseInterface
    {
        $response = $this->responseFactory
            ->createResponse()
            ->withAddedHeader('Content-Type', 'application/json; charset=utf-8');

        $response->getBody()->write(json_encode($data));
        return $response;
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
