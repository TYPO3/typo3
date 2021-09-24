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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList;
use TYPO3\CMS\Recordlist\RecordList\DownloadRecordList;

/**
 * Controller for handling download of records, typically executed from the list module.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
class RecordDownloadController
{
    private const DOWNLOAD_FORMATS = [
        'csv' => [
            'options' => [
                'delimiter' => [
                    'comma' => ',',
                    'semicolon' => ';',
                    'pipe' => '|',
                ],
                'quote' => [
                    'doublequote' => '"',
                    'singlequote' => '\'',
                    'space' => ' ',
                ],
            ],
            'defaults' => [
                'delimiter' => ',',
                'quote' => '"',
            ],
        ],
        'json' => [
            'options' => [
                'meta' => [
                    'full' => 'full',
                    'prefix' => 'prefix',
                    'none' => 'none',
                ],
            ],
            'defaults' => [
                'meta' => 'prefix',
            ],
        ],
    ];

    protected int $id = 0;
    protected string $table = '';
    protected string $format = '';
    protected string $filename = '';
    protected array $modTSconfig = [];

    protected ResponseFactoryInterface $responseFactory;
    protected UriBuilder $uriBuilder;

    public function __construct(ResponseFactoryInterface $responseFactory, UriBuilder $uriBuilder)
    {
        $this->responseFactory = $responseFactory;
        $this->uriBuilder = $uriBuilder;
    }

    /**
     * Handle record download request by evaluating the provided arguments,
     * checking access, initializing the record list, fetching records and
     * finally calling the requested download format action (e.g. csv).
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function handleDownloadRequest(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();

        $this->table = (string)($parsedBody['table'] ?? '');
        if ($this->table === '') {
            throw new \RuntimeException('No table was given for downloading records', 1623941276);
        }
        $this->format = (string)($parsedBody['format'] ?? '');
        if ($this->format === '' || !isset(self::DOWNLOAD_FORMATS[$this->format])) {
            throw new \RuntimeException('No or an invalid download format given', 1624562166);
        }

        $this->filename = $this->generateFilename((string)($parsedBody['filename'] ?? ''));
        $this->id = (int)($parsedBody['id'] ?? 0);

        // Loading module configuration
        $this->modTSconfig = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_list.'] ?? [];

        // Loading current page record and checking access
        $backendUser = $this->getBackendUserAuthentication();
        $perms_clause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        $pageinfo = BackendUtility::readPageAccess($this->id, $perms_clause);
        $searchString = (string)($parsedBody['searchString'] ?? '');
        $searchLevels = (int)($parsedBody['searchLevels'] ?? 0);
        if (!is_array($pageinfo) && !($this->id === 0 && $searchString !== '' && $searchLevels !== 0)) {
            throw new AccessDeniedException('Insufficient permissions for accessing this download', 1623941361);
        }

        // Initialize database record list
        $recordList = GeneralUtility::makeInstance(DatabaseRecordList::class);
        $recordList->modTSconfig = $this->modTSconfig;
        $recordList->setFields[$this->table] = ($parsedBody['allColumns'] ?? false)
            ? BackendUtility::getAllowedFieldsForTable($this->table)
            : $backendUser->getModuleData('list/displayFields')[$this->table] ?? [];
        $recordList->setLanguagesAllowedForUser($this->getSiteLanguages($request));
        $recordList->start($this->id, $this->table, 0, $searchString, $searchLevels);

        $columnsToRender = $recordList->getColumnsToRender($this->table, false);
        $hideTranslations = ($this->modTSconfig['hideTranslations'] ?? '') === '*'
            || GeneralUtility::inList($this->modTSconfig['hideTranslations'] ?? '', $this->table);

        // Initialize the downloader
        $downloader = GeneralUtility::makeInstance(
            DownloadRecordList::class,
            $recordList,
            GeneralUtility::makeInstance(TranslationConfigurationProvider::class)
        );

        // Fetch and process the header row and the records
        $headerRow = $downloader->getHeaderRow($columnsToRender);
        $records = $downloader->getRecords(
            $this->table,
            $this->id,
            $columnsToRender,
            $this->getBackendUserAuthentication(),
            $hideTranslations,
            (bool)($parsedBody['rawValues'] ?? false)
        );

        $downloadAction = $this->format . 'DownloadAction';
        return $this->{$downloadAction}($request, $headerRow, $records);
    }

    /**
     * Generate settings form for the download request
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function downloadSettingsAction(ServerRequestInterface $request): ResponseInterface
    {
        $downloadArguments = $request->getQueryParams();

        $this->table = (string)($downloadArguments['table'] ?? '');
        if ($this->table === '') {
            throw new \RuntimeException('No table was given for downloading records', 1624551586);
        }

        $this->id = (int)($downloadArguments['id'] ?? 0);
        $this->modTSconfig = BackendUtility::getPagesTSconfig($this->id)['mod.']['web_list.'] ?? [];

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:recordlist/Resources/Private/Templates/RecordDownloadSettings.html'
        ));

        $view->assignMultiple([
            'formUrl' => $this->uriBuilder->buildUriFromRoute('record_download'),
            'table' => $this->table,
            'downloadArguments' => $downloadArguments,
            'formats' => array_keys(self::DOWNLOAD_FORMATS),
            'formatOptions' => $this->getFormatOptionsWithResolvedDefaults(),
        ]);

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8');

        $response->getBody()->write($view->render());
        return $response;
    }

    /**
     * Generating an download in CSV format
     *
     * @param ServerRequestInterface $request
     * @param array $headerRow
     * @param array $records
     * @return ResponseInterface
     */
    protected function csvDownloadAction(
        ServerRequestInterface $request,
        array $headerRow,
        array $records
    ): ResponseInterface {
        // Fetch csv related format options
        $csvDelimiter = $this->getFormatOption($request, 'delimiter');
        $csvQuote = $this->getFormatOption($request, 'quote');

        // Create result
        $result[] = CsvUtility::csvValues($headerRow, $csvDelimiter, $csvQuote);
        foreach ($records as $record) {
            $result[] = CsvUtility::csvValues($record, $csvDelimiter, $csvQuote);
        }

        return $this->generateDownloadResponse(implode(CRLF, $result));
    }

    /**
     * Generating an download in JSON format
     *
     * @param ServerRequestInterface $request
     * @param array $headerRow
     * @param array $records
     * @return ResponseInterface
     */
    protected function jsonDownloadAction(
        ServerRequestInterface $request,
        array $headerRow,
        array $records
    ): ResponseInterface {
        // Fetch and evaluate json related format option
        switch ($this->getFormatOption($request, 'meta')) {
            case 'prefix':
                $result = [$this->table . ':' . $this->id => $records];
                break;
            case 'full':
                $user = $this->getBackendUserAuthentication();
                $parsedBody = $request->getParsedBody();
                $result = [
                    'meta' => [
                        'table' => $this->table,
                        'page' => $this->id,
                        'timestamp' => GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('date', 'timestamp'),
                        'user' => $user->user[$user->username_column] ?? '',
                        'site' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?? '',
                        'options' => [
                            'columns' => array_values($headerRow),
                            'values' => ($parsedBody['rawvalues'] ?? false) ? 'raw' : 'processed',
                        ],
                    ],
                    'records' => $records,
                ];
                $searchString = (string)($parsedBody['searchString'] ?? '');
                $searchLevels = (int)($parsedBody['searchLevels'] ?? 0);
                if ($searchString !== '' || $searchLevels !== 0) {
                    $result['meta']['search'] = [
                        'searchTerm' => $searchString,
                        'searchLevels' => $searchLevels,
                    ];
                }
                break;
            case 'none':
            default:
                $result = $records;
                break;
        }

        return $this->generateDownloadResponse(json_encode($result) ?: '');
    }

    /**
     * Get site languages, available for the current backend user
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function getSiteLanguages(ServerRequestInterface $request): array
    {
        $site = $request->getAttribute('site');
        return $site->getAvailableLanguages($this->getBackendUserAuthentication(), false, $this->id);
    }

    /**
     * Return an evaluated and processed custom filename or a
     * default, if non or an invalid custom filename was provided.
     *
     * @param string $filename
     * @return string
     */
    protected function generateFilename(string $filename): string
    {
        $defaultFilename = $this->table . '_' . date('dmy-Hi') . '.' . $this->format;

        // Return default filename if given filename is empty or not valid
        if ($filename === '' || !preg_match('/^[0-9a-z._\-]+$/i', $filename)) {
            return $defaultFilename;
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        if ($extension === '') {
            // Add original extension in case alternative filename did not contain any
            $filename = rtrim($filename, '.') . '.' . $this->format;
        }

        // Check if given or resolved extension matches the original one
        return pathinfo($filename, PATHINFO_EXTENSION) === $this->format ? $filename : $defaultFilename;
    }

    /**
     * Return the format options with resolved default values from TSconfig
     *
     * @return array
     */
    protected function getFormatOptionsWithResolvedDefaults(): array
    {
        $formatOptions = self::DOWNLOAD_FORMATS;

        if ($this->modTSconfig === []) {
            return $formatOptions;
        }

        if ($this->modTSconfig['csvDelimiter'] ?? false) {
            $default = (string)$this->modTSconfig['csvDelimiter'];
            if (!in_array($default, $formatOptions['csv']['options']['delimiter'], true)) {
                // In case the user defined option is not yet available as format options, add it
                $formatOptions['csv']['options']['delimiter']['custom'] = $default;
            }
            $formatOptions['csv']['defaults']['delimiter'] = $default;
        }

        if ($this->modTSconfig['csvQuote'] ?? false) {
            $default = (string)$this->modTSconfig['csvQuote'];
            if (!in_array($default, $formatOptions['csv']['options']['quote'], true)) {
                // In case the user defined option is not yet available as format options, add it
                $formatOptions['csv']['options']['quote']['custom'] = $default;
            }
            $formatOptions['csv']['defaults']['quote'] = $default;
        }

        return $formatOptions;
    }

    protected function getFormatOptions(ServerRequestInterface $request): array
    {
        return $request->getParsedBody()[$this->format] ?? [];
    }

    protected function getFormatOption(ServerRequestInterface $request, string $option, $default = null)
    {
        return $this->getFormatOptions($request)[$option]
            ?? $this->getFormatOptionsWithResolvedDefaults()[$this->format]['defaults'][$option]
            ?? $default;
    }

    protected function generateDownloadResponse(string $result): ResponseInterface
    {
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Disposition', 'attachment; filename=' . $this->filename);
        $response->getBody()->write($result);

        return $response;
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
