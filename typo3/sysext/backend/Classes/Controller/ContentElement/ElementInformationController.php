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

namespace TYPO3\CMS\Backend\Controller\ContentElement;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\History\RecordHistory;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\Rendering\RendererRegistry;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Modal rendering detail about a record. Reached by "Display information" on click menu and list module.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[Controller]
class ElementInformationController
{
    /**
     * Type of element: "db", "file" or "folder"
     */
    protected string $type = 'db';

    protected array $row = [];
    protected ?string $table = null;
    protected ?File $fileObject = null;
    protected ?Folder $folderObject = null;

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly ResourceFactory $resourceFactory,
    ) {}

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $backendUser = $this->getBackendUser();
        $view = $this->moduleTemplateFactory->create($request);
        $view->getDocHeaderComponent()->disable();
        $queryParams = $request->getQueryParams();
        $this->table = $queryParams['table'] ?? null;
        $uid = $queryParams['uid'] ?? '';
        $permsClause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        // Determines if table/uid point to database record or file and if user has access to view information
        $accessAllowed = false;
        if (isset($GLOBALS['TCA'][$this->table])) {
            $uid = (int)$uid;
            // Check permissions and uid value:
            if ($uid && $backendUser->check('tables_select', $this->table)) {
                if ((string)$this->table === 'pages') {
                    $this->row = BackendUtility::readPageAccess($uid, $permsClause) ?: [];
                    $accessAllowed = $this->row !== [];
                } else {
                    $this->row = BackendUtility::getRecordWSOL($this->table, $uid);
                    if ($this->row) {
                        if (isset($this->row['_ORIG_uid'])) {
                            // Make $uid the uid of the versioned record, while $this->row['uid'] is live record uid
                            $uid = (int)$this->row['_ORIG_uid'];
                        }
                        $pageInfo = BackendUtility::readPageAccess((int)$this->row['pid'], $permsClause) ?: [];
                        $accessAllowed = $pageInfo !== [];
                    }
                }
            }
        } elseif ($this->table === '_FILE' || $this->table === '_FOLDER' || $this->table === 'sys_file') {
            $fileOrFolderObject = $this->resourceFactory->retrieveFileOrFolderObject($uid);
            if ($fileOrFolderObject instanceof Folder) {
                $this->folderObject = $fileOrFolderObject;
                $accessAllowed = $this->folderObject->checkActionPermission('read');
                $this->type = 'folder';
            } elseif ($fileOrFolderObject instanceof File) {
                $this->fileObject = $fileOrFolderObject;
                $accessAllowed = $this->fileObject->checkActionPermission('read');
                $this->type = 'file';
                $this->table = 'sys_file';
                $this->row = BackendUtility::getRecordWSOL($this->table, $fileOrFolderObject->getUid());
            }
        }

        // Rendering of the output via fluid
        $view->assign('accessAllowed', $accessAllowed);
        $view->assign('hookContent', '');
        if (!$accessAllowed) {
            return $view->renderResponse('ContentElement/ElementInformation');
        }

        // render type by user func
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/show_item.php']['typeRendering'] ?? [] as $className) {
            $typeRenderObj = GeneralUtility::makeInstance($className);
            if (is_object($typeRenderObj) && method_exists($typeRenderObj, 'isValid') && method_exists($typeRenderObj, 'render')) {
                if ($typeRenderObj->isValid($this->type, $this)) {
                    $view->assign('hookContent', $typeRenderObj->render($this->type, $this));
                    return $view->renderResponse('ContentElement/ElementInformation');
                }
            }
        }

        $pageTitle = $this->getPageTitle();
        $view->setTitle($pageTitle['table'] . ': ' . $pageTitle['title']);
        $view->assignMultiple($pageTitle);
        $view->assignMultiple($this->getPreview());
        $view->assignMultiple($this->getPropertiesForTable($request));
        $view->assignMultiple($this->getReferences($request, $uid));
        $view->assign('returnUrl', GeneralUtility::sanitizeLocalUrl($request->getQueryParams()['returnUrl'] ?? ''));
        $view->assign('maxTitleLength', $this->getBackendUser()->uc['titleLen'] ?? 20);

        return $view->renderResponse('ContentElement/ElementInformation');
    }

    /**
     * Get page title with icon, table title and record title
     */
    protected function getPageTitle(): array
    {
        $pageTitle = [
            'title' => BackendUtility::getRecordTitle($this->table, $this->row),
        ];
        if ($this->type === 'folder') {
            $pageTitle['title'] = htmlspecialchars($this->folderObject->getName());
            $pageTitle['table'] = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:folder');
            $pageTitle['icon'] = $this->iconFactory->getIconForResource($this->folderObject, Icon::SIZE_SMALL)->render();
        } elseif ($this->type === 'file') {
            $pageTitle['table'] = $this->getLanguageService()->sL($GLOBALS['TCA'][$this->table]['ctrl']['title']);
            $pageTitle['icon'] = $this->iconFactory->getIconForResource($this->fileObject, Icon::SIZE_SMALL)->render();
        } else {
            $pageTitle['table'] = $this->getLanguageService()->sL($GLOBALS['TCA'][$this->table]['ctrl']['title']);
            $pageTitle['icon'] = $this->iconFactory->getIconForRecord($this->table, $this->row, Icon::SIZE_SMALL);
        }
        return $pageTitle;
    }

    /**
     * Get preview for current record
     */
    protected function getPreview(): array
    {
        $preview = [];
        // Perhaps @todo in future: Also display preview for records - without fileObject
        if (!$this->fileObject) {
            return $preview;
        }

        // check if file is marked as missing
        if ($this->fileObject->isMissing()) {
            $preview['missingFile'] = $this->fileObject->getName();
        } else {
            $rendererRegistry = GeneralUtility::makeInstance(RendererRegistry::class);
            $fileRenderer = $rendererRegistry->getRenderer($this->fileObject);
            $preview['url'] = $this->fileObject->getPublicUrl() ?? '';

            $width = min(590, $this->fileObject->getMetaData()['width'] ?? 590) . 'm';
            $height = min(400, $this->fileObject->getMetaData()['height'] ?? 400) . 'm';

            // Check if there is a FileRenderer
            if ($fileRenderer !== null) {
                $preview['fileRenderer'] = $fileRenderer->render($this->fileObject, $width, $height);
                // else check if we can create an Image preview
            } elseif ($this->fileObject->isImage()) {
                $preview['fileObject'] = $this->fileObject;
                $preview['width'] = $width;
                $preview['height'] = $height;
            }
        }
        return $preview;
    }

    /**
     * Get property array for html table
     */
    protected function getPropertiesForTable(ServerRequestInterface $request): array
    {
        $lang = $this->getLanguageService();
        $propertiesForTable = [];
        $propertiesForTable['extraFields'] = $this->getExtraFields();

        // Traverse the list of fields to display for the record:
        $fieldList = $this->getFieldList($request, $this->table, (int)($this->row['uid'] ?? 0));

        foreach ($fieldList as $name) {
            $name = trim($name);
            $uid = $this->row['uid'] ?? 0;

            if (!isset($GLOBALS['TCA'][$this->table]['columns'][$name])) {
                continue;
            }

            // @todo Add meaningful information for mfa field. For the time being we don't display anything at all.
            if ($this->type === 'db' && $name === 'mfa' && in_array($this->table, ['be_users', 'fe_users'], true)) {
                continue;
            }

            // not a real field -> skip
            if ($this->type === 'file' && $name === 'fileinfo') {
                continue;
            }

            // Field does not exist (e.g. having type=none) -> skip
            if (!array_key_exists($name, $this->row)) {
                continue;
            }

            $isExcluded = !(!($GLOBALS['TCA'][$this->table]['columns'][$name]['exclude'] ?? false) || $this->getBackendUser()->check('non_exclude_fields', $this->table . ':' . $name));
            if ($isExcluded) {
                continue;
            }
            $label = $lang->sL(BackendUtility::getItemLabel($this->table, $name));
            $label = $label ?: $name;

            $propertiesForTable['fields'][] = [
                'fieldValue' => BackendUtility::getProcessedValue($this->table, $name, $this->row[$name], 0, false, false, $uid),
                'fieldLabel' => htmlspecialchars($label),
            ];
        }

        // additional information for folders and files
        if ($this->folderObject instanceof Folder || $this->fileObject instanceof File) {
            // storage
            if ($this->folderObject instanceof Folder) {
                $propertiesForTable['fields']['storage'] = [
                    'fieldValue' => $this->folderObject->getStorage()->getName(),
                    'fieldLabel' => htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.storage')),
                ];
            }

            // folder
            $resourceObject = $this->fileObject ?: $this->folderObject;
            $parentFolder = $resourceObject->getParentFolder();
            $propertiesForTable['fields']['folder'] = [
                'fieldValue' => $parentFolder instanceof Folder ? $parentFolder->getReadablePath() : '',
                'fieldLabel' => htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:folder')),
            ];

            if ($this->fileObject instanceof File) {
                // show file dimensions for images
                if ($this->fileObject->getType() === AbstractFile::FILETYPE_IMAGE) {
                    $propertiesForTable['fields']['width'] = [
                        'fieldValue' => $this->fileObject->getProperty('width') . 'px',
                        'fieldLabel' => htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.width')),
                    ];
                    $propertiesForTable['fields']['height'] = [
                        'fieldValue' => $this->fileObject->getProperty('height') . 'px',
                        'fieldLabel' => htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.height')),
                    ];
                }

                // file size
                $propertiesForTable['fields']['size'] = [
                    'fieldValue' => GeneralUtility::formatSize((int)$this->fileObject->getProperty('size'), htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:byteSizeUnits'))),
                    'fieldLabel' => $lang->sL(BackendUtility::getItemLabel($this->table, 'size')),
                ];

                // show the metadata of a file as well
                $table = 'sys_file_metadata';
                $metaDataRepository = GeneralUtility::makeInstance(MetaDataRepository::class);
                /** @var array<string, string> $metaData */
                $metaData = $metaDataRepository->findByFileUid($this->row['uid'] ?? 0);

                // If there is no metadata record, skip it
                if ($metaData !== []) {
                    $allowedFields = $this->getFieldList($request, $table, (int)$metaData['uid']);

                    foreach ($metaData as $name => $value) {
                        if (in_array($name, $allowedFields, true)) {
                            if (!isset($GLOBALS['TCA'][$table]['columns'][$name])) {
                                continue;
                            }

                            $isExcluded = !(!($GLOBALS['TCA'][$table]['columns'][$name]['exclude'] ?? false) || $this->getBackendUser()->check('non_exclude_fields', $table . ':' . $name));
                            if ($isExcluded) {
                                continue;
                            }

                            $label = $lang->sL(BackendUtility::getItemLabel($table, $name));
                            $label = $label ?: $name;

                            $propertiesForTable['fields'][] = [
                                'fieldValue' => BackendUtility::getProcessedValue($table, $name, $metaData[$name], 0, false, false, (int)$metaData['uid']),
                                'fieldLabel' => htmlspecialchars($label),
                            ];
                        }
                    }
                }
            }
        }

        return $propertiesForTable;
    }

    /**
     * Get the list of fields that should be shown for the given table
     */
    protected function getFieldList(ServerRequestInterface $request, string $table, int $uid): array
    {
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class);
        $formDataCompilerInput = [
            'request' => $request,
            'command' => 'edit',
            'tableName' => $table,
            'vanillaUid' => $uid,
        ];
        try {
            $result = $formDataCompiler->compile($formDataCompilerInput, GeneralUtility::makeInstance(TcaDatabaseRecord::class));
            $fieldList = array_unique(array_values($result['columnsToProcess']));

            $ctrlKeysOfUnneededFields = ['origUid', 'transOrigPointerField', 'transOrigDiffSourceField'];
            foreach ($ctrlKeysOfUnneededFields as $field) {
                if (isset($GLOBALS['TCA'][$table]['ctrl'][$field]) && ($key = array_search($GLOBALS['TCA'][$table]['ctrl'][$field], $fieldList, true)) !== false) {
                    unset($fieldList[$key]);
                }
            }
        } catch (\Exception $exception) {
            $fieldList = [];
        }

        $searchFields = GeneralUtility::trimExplode(',', ($GLOBALS['TCA'][$table]['ctrl']['searchFields'] ?? ''));

        return array_unique(array_merge($fieldList, $searchFields));
    }

    /**
     * Get the extra fields (uid, timestamps, creator) for the table
     */
    protected function getExtraFields(): array
    {
        $lang = $this->getLanguageService();
        $keyLabelPair = [];
        if (in_array($this->type, ['folder', 'file'], true)) {
            if ($this->type === 'file') {
                $keyLabelPair['uid'] = [
                    'value' => (int)$this->row['uid'],
                ];
                $keyLabelPair['creation_date'] = [
                    'value' => BackendUtility::datetime($this->row['creation_date']),
                    'fieldLabel' => htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.creationDate')),
                    'isDatetime' => true,
                ];
                $keyLabelPair['modification_date'] = [
                    'value' => BackendUtility::datetime($this->row['modification_date']),
                    'fieldLabel' => htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.timestamp')),
                    'isDatetime' => true,
                ];
            }
        } else {
            $keyLabelPair['uid'] = [
                'value' => BackendUtility::getProcessedValueExtra($this->table, 'uid', $this->row['uid']),
                'fieldLabel' => rtrim(htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:show_item.php.uid')), ':'),
            ];
            foreach (['crdate' => 'creationDate', 'tstamp' => 'timestamp'] as $field => $label) {
                if (isset($GLOBALS['TCA'][$this->table]['ctrl'][$field])) {
                    $keyLabelPair[$field] = [
                        'value' => BackendUtility::datetime($this->row[$GLOBALS['TCA'][$this->table]['ctrl'][$field]]),
                        'fieldLabel' => rtrim(htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.' . $label)), ':'),
                        'isDatetime' => true,
                    ];
                }
            }
            // Show the user who created the record
            $recordHistory = GeneralUtility::makeInstance(RecordHistory::class);
            $ownerInformation = $recordHistory->getCreationInformationForRecord($this->table, $this->row);
            $ownerUid = (int)(is_array($ownerInformation) && $ownerInformation['usertype'] === 'BE' ? $ownerInformation['userid'] : 0);
            if ($ownerUid) {
                $creatorRecord = BackendUtility::getRecord('be_users', $ownerUid);
                if ($creatorRecord) {
                    $keyLabelPair['creatorRecord'] = [
                        'value' => $creatorRecord,
                        'fieldLabel' => rtrim(htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.creationUserId')), ':'),
                    ];
                }
            }
        }
        return $keyLabelPair;
    }

    /**
     * Get references section (references from and references to current record)
     */
    protected function getReferences(ServerRequestInterface $request, int|string $uid): array
    {
        $references = [];
        switch ($this->type) {
            case 'db': {
                $references['refLines'] = $this->makeRef($this->table, $uid, $request);
                $references['refFromLines'] = $this->makeRefFrom($this->table, $uid, $request);
                break;
            }
            case 'file': {
                if ($this->fileObject && $this->fileObject->isIndexed()) {
                    $references['refLines'] = $this->makeRef('_FILE', $this->fileObject, $request);
                }
                break;
            }
        }
        return $references;
    }

    /**
     * Get field name for specified table/column name
     *
     * @param string $tableName Table name
     * @param string $fieldName Column name
     * @return string label
     */
    protected function getLabelForTableColumn($tableName, $fieldName): string
    {
        if (($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['label'] ?? null) !== null) {
            $field = $this->getLanguageService()->sL($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['label']);
            if (trim($field) === '') {
                $field = $fieldName;
            }
        } else {
            $field = $fieldName;
        }
        return $field;
    }

    /**
     * Returns the record actions
     *
     * @param string $table
     * @param int $uid
     * @throws RouteNotFoundException
     */
    protected function getRecordActions($table, $uid, ServerRequestInterface $request): array
    {
        if ($table === '' || $uid < 0) {
            return [];
        }

        $actions = [];
        // Edit button
        $urlParameters = [
            'edit' => [
                $table => [
                    $uid => 'edit',
                ],
            ],
            'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
        ];
        $actions['recordEditUrl'] = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);

        // History button
        $urlParameters = [
            'element' => $table . ':' . $uid,
            'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
        ];
        $actions['recordHistoryUrl'] = (string)$this->uriBuilder->buildUriFromRoute('record_history', $urlParameters);

        if ($table === 'pages') {
            // Recordlist button
            $actions['webListUrl'] = (string)$this->uriBuilder->buildUriFromRoute('web_list', ['id' => $uid, 'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri()]);

            $previewUriBuilder = PreviewUriBuilder::create((int)$uid)
                ->withRootLine(BackendUtility::BEgetRootLine($uid));
            // View page button
            $actions['previewUrlAttributes'] = $previewUriBuilder->serializeDispatcherAttributes();
        }

        return $actions;
    }

    /**
     * Make reference display
     *
     * @param string $table Table name
     * @param int|File $ref Filename or uid
     * @throws RouteNotFoundException
     */
    protected function makeRef($table, $ref, ServerRequestInterface $request): array
    {
        $refLines = [];
        $lang = $this->getLanguageService();
        // Files reside in sys_file table
        if ($table === '_FILE') {
            $selectTable = 'sys_file';
            $selectUid = $ref->getUid();
        } else {
            $selectTable = $table;
            $selectUid = $ref;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_refindex');

        $predicates = [
            $queryBuilder->expr()->eq(
                'ref_table',
                $queryBuilder->createNamedParameter($selectTable)
            ),
            $queryBuilder->expr()->eq(
                'ref_uid',
                $queryBuilder->createNamedParameter($selectUid, Connection::PARAM_INT)
            ),
        ];

        $backendUser = $this->getBackendUser();
        if (!$backendUser->isAdmin()) {
            $allowedSelectTables = GeneralUtility::trimExplode(',', $backendUser->groupData['tables_select']);
            $predicates[] = $queryBuilder->expr()->in(
                'tablename',
                $queryBuilder->createNamedParameter($allowedSelectTables, Connection::PARAM_STR_ARRAY)
            );
        }

        $rows = $queryBuilder
            ->select('*')
            ->from('sys_refindex')
            ->where(...$predicates)
            ->executeQuery()
            ->fetchAllAssociative();

        // Compile information for title tag:
        foreach ($rows as $row) {
            if ($row['tablename'] === 'sys_file_reference') {
                $row = $this->transformFileReferenceToRecordReference($row);
                if ($row === null) {
                    return [];
                }
            }

            $line = [];
            $record = BackendUtility::getRecordWSOL($row['tablename'], $row['recuid']);
            if ($record) {
                if (!$this->canAccessPage($row['tablename'], $record)) {
                    continue;
                }
                $parentRecord = BackendUtility::getRecord('pages', $record['pid']);
                $parentRecordTitle = is_array($parentRecord)
                    ? BackendUtility::getRecordTitle('pages', $parentRecord)
                    : '';
                $urlParameters = [
                    'edit' => [
                        $row['tablename'] => [
                            $row['recuid'] => 'edit',
                        ],
                    ],
                    'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                ];
                $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                $line['url'] = $url;
                $line['icon'] = $this->iconFactory->getIconForRecord($row['tablename'], $record, Icon::SIZE_SMALL)->render();
                $line['row'] = $row;
                $line['record'] = $record;
                $line['recordTitle'] = BackendUtility::getRecordTitle($row['tablename'], $record, false, true);
                $line['parentRecord'] = $parentRecord;
                $line['parentRecordTitle'] = $parentRecordTitle;
                $line['title'] = $lang->sL($GLOBALS['TCA'][$row['tablename']]['ctrl']['title']);
                $line['labelForTableColumn'] = $this->getLabelForTableColumn($row['tablename'], $row['field']);
                $line['path'] = BackendUtility::getRecordPath($record['pid'], '', 0, 0);
                $line['actions'] = $this->getRecordActions($row['tablename'], $row['recuid'], $request);
            } else {
                $line['row'] = $row;
                $line['title'] = $lang->sL($GLOBALS['TCA'][$row['tablename']]['ctrl']['title'] ?? '') ?: $row['tablename'];
                $line['labelForTableColumn'] = $this->getLabelForTableColumn($row['tablename'], $row['field']);
            }
            $refLines[] = $line;
        }
        return $refLines;
    }

    /**
     * Make reference display (what this elements points to)
     *
     * @param string $table Table name
     * @param int $ref Filename or uid
     */
    protected function makeRefFrom($table, $ref, ServerRequestInterface $request): array
    {
        $refFromLines = [];
        $lang = $this->getLanguageService();

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_refindex');

        $predicates = [
            $queryBuilder->expr()->eq(
                'tablename',
                $queryBuilder->createNamedParameter($table)
            ),
            $queryBuilder->expr()->eq(
                'recuid',
                $queryBuilder->createNamedParameter($ref, Connection::PARAM_INT)
            ),
        ];

        $backendUser = $this->getBackendUser();
        if (!$backendUser->isAdmin()) {
            $allowedSelectTables = GeneralUtility::trimExplode(',', $backendUser->groupData['tables_select']);
            $predicates[] = $queryBuilder->expr()->in(
                'ref_table',
                $queryBuilder->createNamedParameter($allowedSelectTables, Connection::PARAM_STR_ARRAY)
            );
        }

        $rows = $queryBuilder
            ->select('*')
            ->from('sys_refindex')
            ->where(...$predicates)
            ->executeQuery()
            ->fetchAllAssociative();

        // Compile information for title tag:
        foreach ($rows as $row) {
            $line = [];
            $record = BackendUtility::getRecordWSOL($row['ref_table'], $row['ref_uid']);
            if ($record) {
                if (!$this->canAccessPage($row['ref_table'], $record)) {
                    continue;
                }
                $urlParameters = [
                    'edit' => [
                        $row['ref_table'] => [
                            $row['ref_uid'] => 'edit',
                        ],
                    ],
                    'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
                ];
                $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                $line['url'] = $url;
                $line['icon'] = $this->iconFactory->getIconForRecord($row['ref_table'], $record, Icon::SIZE_SMALL)->render();
                $line['row'] = $row;
                $line['record'] = $record;
                $line['recordTitle'] = BackendUtility::getRecordTitle($row['ref_table'], $record, false, true);
                $line['title'] = $lang->sL($GLOBALS['TCA'][$row['ref_table']]['ctrl']['title'] ?? '');
                $line['labelForTableColumn'] = $this->getLabelForTableColumn($table, $row['field']);
                $line['path'] = BackendUtility::getRecordPath($record['pid'], '', 0, 0);
                $line['actions'] = $this->getRecordActions($row['ref_table'], $row['ref_uid'], $request);
            } else {
                $line['row'] = $row;
                $line['title'] = $lang->sL($GLOBALS['TCA'][$row['ref_table']]['ctrl']['title'] ?? '');
                $line['labelForTableColumn'] = $this->getLabelForTableColumn($table, $row['field']);
            }
            $refFromLines[] = $line;
        }
        return $refFromLines;
    }

    /**
     * Convert FAL file reference (sys_file_reference) to reference index (sys_refindex) table format
     */
    protected function transformFileReferenceToRecordReference(array $referenceRecord): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_file_reference');
        $queryBuilder->getRestrictions()->removeAll();
        $fileReference = $queryBuilder
            ->select('*')
            ->from('sys_file_reference')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($referenceRecord['recuid'], Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        return $fileReference ? [
            'recuid' => $fileReference['uid_foreign'],
            'tablename' => $fileReference['tablenames'],
            'field' => $fileReference['fieldname'],
            'flexpointer' => '',
            'softref_key' => '',
            'sorting' => $fileReference['sorting_foreign'],
        ] : null;
    }

    /**
     * @param string $tableName Name of the table
     * @param array $record Record to be checked (ensure pid is resolved for workspaces)
     */
    protected function canAccessPage(string $tableName, array $record): bool
    {
        $recordPid = (int)($tableName === 'pages' ? $record['uid'] : $record['pid']);
        return $this->getBackendUser()->isInWebMount($tableName === 'pages' ? $record : $record['pid'])
            || $recordPid === 0 && !empty($GLOBALS['TCA'][$tableName]['ctrl']['security']['ignoreRootLevelRestriction']);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
