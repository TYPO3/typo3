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

namespace TYPO3\CMS\Recordlist\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Controller for handling the display column selection for records, typically executed from the list module.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class ColumnSelectorController
{
    private const PSEUDO_FIELDS = ['_REF_', '_PATH_'];

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

        if ($table === '' || !is_array($selectedColumns) || $selectedColumns === []) {
            return $this->jsonResponse([
                'success' => false,
                'message' => htmlspecialchars(
                    $this->getLanguageService()->sL('LLL:EXT:recordlist/Resources/Private/Language/locallang.xlf:updateColumnView.nothingUpdated')
                )
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
            'EXT:recordlist/Resources/Private/Templates/ColumnSelector.html'
        ));

        $view->assignMultiple([
            'table' => $table,
            'columns' => $this->getColumns($table, (int)($parsedBody['id'] ?? 0))
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
        $tsConfig = BackendUtility::getPagesTSconfig($pageId) ?? [];

        // Current fields selection
        $displayFields = $this->getBackendUserAuthentication()->getModuleData('list/displayFields')[$table] ?? [];

        // Request fields from table and add pseudo fields
        $fields = array_merge(
            GeneralUtility::makeInstance(DatabaseRecordList::class)->makeFieldList($table, false, true),
            self::PSEUDO_FIELDS
        );

        $columns = $specialColumns = $disabledColumns = [];
        foreach ($fields as $fieldName) {
            // Hide field if disabled
            if ($tsConfig['TCEFORM.'][$table . '.'][$fieldName . '.']['disabled'] ?? false) {
                continue;
            }

            // Determine if the column should be disabled (Meaning it is always selected and can not be turned off)
            $isDisabled = $fieldName === ($GLOBALS['TCA'][$table]['ctrl']['label'] ?? false);

            // Determine field label
            $label = BackendUtility::getItemLabel($table, $fieldName);
            if ($label) {
                if (!empty($tsConfig['TCEFORM.'][$table . '.'][$fieldName . '.']['label.'][$this->getLanguageService()->lang])) {
                    $label = $tsConfig['TCEFORM.'][$table . '.'][$fieldName . '.']['label.'][$this->getLanguageService()->lang];
                } elseif (!empty($tsConfig['TCEFORM.'][$table . '.'][$fieldName . '.']['label'])) {
                    $label = $tsConfig['TCEFORM.'][$table . '.'][$fieldName . '.']['label'];
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
