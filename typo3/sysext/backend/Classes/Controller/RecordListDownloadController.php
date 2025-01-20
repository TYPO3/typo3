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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Exception\AccessDeniedException;
use TYPO3\CMS\Backend\RecordList\DatabaseRecordList;
use TYPO3\CMS\Backend\RecordList\DownloadRecordList;
use TYPO3\CMS\Backend\RecordList\Event\BeforeRecordDownloadIsExecutedEvent;
use TYPO3\CMS\Backend\RecordList\Event\BeforeRecordDownloadPresetsAreDisplayedEvent;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\CsvUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller for handling download of records, typically executed from the list module.
 *
 * @internal This class is a specific Backend controller implementation and is not part of the TYPO3's Core API.
 */
#[AsController]
class RecordListDownloadController
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

    public function __construct(
        protected readonly ResponseFactoryInterface $responseFactory,
        protected readonly BackendViewFactory $backendViewFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Handle record download request by evaluating the provided arguments,
     * checking access, initializing the record list, fetching records and
     * finally calling the requested download format action (e.g. csv).
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

        // Loading TCEFORM for the table
        $tsConfig = BackendUtility::getPagesTSconfig($this->id)['TCEFORM.'][$this->table . '.'] ?? null;
        $tsConfig = is_array($tsConfig) ? $tsConfig : null;

        // Loading current page record and checking access
        $backendUser = $this->getBackendUserAuthentication();
        $perms_clause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        $pageinfo = BackendUtility::readPageAccess($this->id, $perms_clause);
        $searchString = (string)($parsedBody['searchString'] ?? '');
        $searchLevels = (int)($parsedBody['searchLevels'] ?? $this->modTSconfig['searchLevel.']['default'] ?? 0);
        if (!is_array($pageinfo) && !($this->id === 0 && $searchString !== '' && $searchLevels !== 0)) {
            throw new AccessDeniedException('Insufficient permissions for accessing this download', 1623941361);
        }
        $rawValues = (bool)($parsedBody['rawValues'] ?? false);

        // Initialize database record list
        $recordList = GeneralUtility::makeInstance(DatabaseRecordList::class);
        $recordList->setRequest($request);
        $recordList->modTSconfig = $this->modTSconfig;
        $recordList->setLanguagesAllowedForUser($this->getSiteLanguages($request));
        $recordList->start($this->id, $this->table, 0, $searchString, $searchLevels);
        $selectedPreset = (string)($parsedBody['preset'] ?? '');
        if (($parsedBody['allColumns'] ?? false) || $selectedPreset !== '') {
            // Overwrite setFields in case all allowed columns should be included,
            // or a preset is selected (that is only allowed to pick from the maximum
            // allowed set of columns).
            $recordList->setFields[$this->table] = BackendUtility::getAllowedFieldsForTable($this->table);
        }
        $columnsToRender = $recordList->getColumnsToRender($this->table, false, $selectedPreset);

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
        if (!$rawValues) {
            foreach ($headerRow as &$headerField) {
                $label = BackendUtility::getItemLabel($this->table, $headerField);
                if ($label !== null) {
                    $headerField = rtrim(trim($this->getLanguageService()->translateLabel($tsConfig[$headerField . '.']['label.'] ?? [], $tsConfig[$headerField . '.']['label'] ?? $label)), ':');
                } elseif ($specialLabel = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.' . $headerField)) {
                    // Special label exists for this field (Probably a management field, e.g. sorting)
                    $headerField = $specialLabel;
                }
            }
            unset($headerField);
        }
        $records = $downloader->getRecords(
            $this->table,
            $columnsToRender,
            $this->getBackendUserAuthentication(),
            $hideTranslations,
            $rawValues
        );

        $event = $this->eventDispatcher->dispatch(
            new BeforeRecordDownloadIsExecutedEvent(
                $headerRow,
                $records,
                $request,
                $this->table,
                $this->format,
                $this->filename,
                $this->id,
                $this->modTSconfig,
                $columnsToRender,
                $hideTranslations,
            )
        );

        $downloadAction = $this->format . 'DownloadAction';
        return $this->{$downloadAction}($request, $event->getHeaderRow(), $event->getRecords());
    }

    /**
     * Generate settings form for the download request
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

        $presets = $this->eventDispatcher->dispatch(
            new BeforeRecordDownloadPresetsAreDisplayedEvent(
                $this->table,
                $this->modTSconfig['downloadPresets.'][$this->table . '.'] ?? [],
                $request,
                $this->id,
            )
        )->getPresets();

        $view = $this->backendViewFactory->create($request);
        $view->assignMultiple([
            'table' => $this->table,
            'downloadArguments' => $downloadArguments,
            'formats' => array_keys(self::DOWNLOAD_FORMATS),
            'formatOptions' => $this->getFormatOptionsWithResolvedDefaults(),
            'presets' => $presets,
        ]);

        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8');

        $response->getBody()->write($view->render('RecordDownloadSettings'));
        return $response;
    }

    /**
     * Generating an download in CSV format
     */
    protected function csvDownloadAction(
        ServerRequestInterface $request,
        array $headerRow,
        array $records
    ): ResponseInterface {
        // Fetch csv related format options
        $csvDelimiter = (string)$this->getFormatOption($request, 'delimiter');
        $csvQuote = (string)$this->getFormatOption($request, 'quote');

        // Create result
        $result[] = CsvUtility::csvValues($headerRow, $csvDelimiter, $csvQuote);
        foreach ($records as $record) {
            $result[] = CsvUtility::csvValues($record, $csvDelimiter, $csvQuote);
        }

        return $this->generateDownloadResponse(implode(CRLF, $result));
    }

    /**
     * Generating an download in JSON format
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
                        'user' => $user->getUserName() ?? '',
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
     */
    protected function getSiteLanguages(ServerRequestInterface $request): array
    {
        $site = $request->getAttribute('site');
        return $site->getAvailableLanguages($this->getBackendUserAuthentication(), false, $this->id);
    }

    /**
     * Return an evaluated and processed custom filename or a
     * default, if non or an invalid custom filename was provided.
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

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
