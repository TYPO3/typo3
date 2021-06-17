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
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\RecordList\CsvExportRecordList;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;

/**
 * Controller for handling exports of records, typically executed from the list module.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class RecordExportController
{
    protected int $id = 0;
    protected array $modTSconfig = [];
    protected ResponseFactoryInterface $responseFactory;

    public function __construct(ResponseFactoryInterface $responseFactory)
    {
        $this->responseFactory = $responseFactory;
    }

    /**
     * Handle record export request
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $backendUser = $this->getBackendUserAuthentication();

        $table = (string)($queryParams['table'] ?? '');
        if ($table === '') {
            throw new InvalidTableException('No table was given for exporting records', 1623941276);
        }

        $this->id = (int)($queryParams['id'] ?? 0);
        $search_field = (string)($queryParams['search_field'] ?? '');
        $search_levels = (int)($queryParams['search_levels'] ?? 0);

        // Loading module configuration
        $this->modTSconfig = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_list.'] ?? [];

        // Loading current page record and checking access
        $perms_clause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        $pageinfo = BackendUtility::readPageAccess($this->id, $perms_clause);

        $hasAccess = is_array($pageinfo) || ($this->id === 0 && $search_levels !== 0 && $search_field !== '');
        if ($hasAccess === false) {
            throw new AccessDeniedException('Insufficient permissions for accessing this export', 1623941361);
        }

        $recordList = GeneralUtility::makeInstance(DatabaseRecordList::class);
        $recordList->modTSconfig = $this->modTSconfig;
        $recordList->setLanguagesAllowedForUser($this->getSiteLanguages($request));
        $recordList->start($this->id, $table, 0, $search_field, $search_levels);

        // Currently only CSV is supported for export. As soon as Core adds additional
        // formats, this should be changed to e.g. a switch case on the requested $format
        return $this->csvExportAction($recordList, $table);
    }

    protected function getSiteLanguages(ServerRequestInterface $request): array
    {
        $site = $request->getAttribute('site');
        return $site->getAvailableLanguages($this->getBackendUserAuthentication(), false, $this->id);
    }

    protected function csvExportAction(DatabaseRecordList $recordList, string $table): ResponseInterface
    {
        $user = $this->getBackendUserAuthentication();
        $csvExporter = GeneralUtility::makeInstance(
            CsvExportRecordList::class,
            $recordList,
            GeneralUtility::makeInstance(TranslationConfigurationProvider::class)
        );
        // Ensure the fields chosen by the backend editor are selected / displayed
        $recordList->setFields = $user->getModuleData('list/displayFields');
        $columnsToRender = $recordList->getColumnsToRender($table, false);
        $headerRow = $csvExporter->getHeaderRow($columnsToRender);
        $hideTranslations = ($this->modTSconfig['hideTranslations'] ?? '') === '*' || GeneralUtility::inList($this->modTSconfig['hideTranslations'] ?? '', $table);
        $records = $csvExporter->getRecords($table, $this->id, $columnsToRender, $user, $hideTranslations);
        return $this->csvResponse(
            $table . '_' . date('dmy-Hi') . '.csv',
            $records,
            $headerRow
        );
    }

    protected function csvResponse($fileName, array $data, array $headerRow = []): ResponseInterface
    {
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename=' . $fileName);

        $csvDelimiter = $this->modTSconfig['csvDelimiter'] ?? ',';
        $csvQuote = $this->modTSconfig['csvQuote'] ?? '"';
        $result[] = CsvUtility::csvValues($headerRow, $csvDelimiter, $csvQuote);
        foreach ($data as $csvRow) {
            $result[] = CsvUtility::csvValues($csvRow, $csvDelimiter, $csvQuote);
        }
        $response->getBody()->write(implode(CRLF, $result));
        return $response;
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
