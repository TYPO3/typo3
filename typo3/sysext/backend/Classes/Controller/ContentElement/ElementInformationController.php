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

use Doctrine\DBAL\Connection;
use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\HtmlResponse;
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
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script Class for showing information about an item.
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class ElementInformationController
{
    /**
     * Record table name
     *
     * @var string
     */
    protected $table;

    /**
     * Record uid
     *
     * @var int
     */
    protected $uid;

    /**
     * @var string
     */
    protected $permsClause;

    /**
     * @var bool
     */
    protected $access = false;

    /**
     * Which type of element:
     * - "file"
     * - "db"
     *
     * @var string
     */
    protected $type = '';

    /**
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * For type "db": Set to page record of the parent page of the item set
     * (if type="db")
     *
     * @var array
     */
    protected $pageInfo;

    /**
     * Database records identified by table/uid
     *
     * @var array
     */
    protected $row;

    protected ?File $fileObject = null;
    protected ?Folder $folderObject = null;

    protected IconFactory $iconFactory;
    protected UriBuilder $uriBuilder;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        IconFactory $iconFactory,
        UriBuilder $uriBuilder,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Injects the request object for the current request or subrequest
     * As this controller goes only through the main() method, it is rather simple for now
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        $this->main($request);
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Determines if table/uid point to database record or file and
     * if user has access to view information
     *
     * @param ServerRequestInterface $request
     */
    protected function init(ServerRequestInterface $request): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->moduleTemplate->getDocHeaderComponent()->disable();
        $queryParams = $request->getQueryParams();

        $this->table = $queryParams['table'] ?? null;
        $this->uid = $queryParams['uid'] ?? null;

        $this->permsClause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);

        if (isset($GLOBALS['TCA'][$this->table])) {
            $this->initDatabaseRecord();
        } elseif ($this->table === '_FILE' || $this->table === '_FOLDER' || $this->table === 'sys_file') {
            $this->initFileOrFolderRecord();
        }
    }

    /**
     * Init database records (table)
     */
    protected function initDatabaseRecord(): void
    {
        $this->type = 'db';
        $this->uid = (int)$this->uid;

        // Check permissions and uid value:
        if ($this->uid && $this->getBackendUser()->check('tables_select', $this->table)) {
            if ((string)$this->table === 'pages') {
                $this->pageInfo = BackendUtility::readPageAccess($this->uid, $this->permsClause) ?: [];
                $this->access = $this->pageInfo !== [];
                $this->row = $this->pageInfo;
            } else {
                $this->row = BackendUtility::getRecordWSOL($this->table, $this->uid);
                if ($this->row) {
                    if (!empty($this->row['t3ver_oid'])) {
                        // Make $this->uid the uid of the versioned record, while $this->row['uid'] is live record uid
                        $this->uid = (int)$this->row['_ORIG_uid'];
                    }
                    $this->pageInfo = BackendUtility::readPageAccess((int)$this->row['pid'], $this->permsClause) ?: [];
                    $this->access = $this->pageInfo !== [];
                }
            }
        }
    }

    /**
     * Init file/folder parameters
     */
    protected function initFileOrFolderRecord(): void
    {
        $fileOrFolderObject = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($this->uid);

        if ($fileOrFolderObject instanceof Folder) {
            $this->folderObject = $fileOrFolderObject;
            $this->access = $this->folderObject->checkActionPermission('read');
            $this->type = 'folder';
        } elseif ($fileOrFolderObject instanceof File) {
            $this->fileObject = $fileOrFolderObject;
            $this->access = $this->fileObject->checkActionPermission('read');
            $this->type = 'file';
            $this->table = 'sys_file';

            try {
                $this->row = BackendUtility::getRecordWSOL($this->table, $fileOrFolderObject->getUid());
            } catch (Exception $e) {
                $this->row = [];
            }
        }
    }

    /**
     * Compiles the whole content to be outputted, which is then set as content to the moduleTemplate
     * There is a hook to do a custom rendering of a record.
     *
     * @param ServerRequestInterface $request
     */
    protected function main(ServerRequestInterface $request): void
    {
        $content = '';

        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:backend/Resources/Private/Templates/ContentElement/ElementInformation.html'
        ));

        if ($this->access) {
            // render type by user func
            $typeRendered = false;
            foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/show_item.php']['typeRendering'] ?? [] as $className) {
                $typeRenderObj = GeneralUtility::makeInstance($className);
                if (is_object($typeRenderObj) && method_exists($typeRenderObj, 'isValid') && method_exists($typeRenderObj, 'render')) {
                    if ($typeRenderObj->isValid($this->type, $this)) {
                        $content .= $typeRenderObj->render($this->type, $this);
                        $typeRendered = true;
                        break;
                    }
                }
            }

            if (!$typeRendered) {
                $view->assign('accessAllowed', true);
                $view->assignMultiple($this->getPageTitle());
                $view->assignMultiple($this->getPreview());
                $view->assignMultiple($this->getPropertiesForTable());
                $view->assignMultiple($this->getReferences($request));
                $view->assign('returnUrl', GeneralUtility::sanitizeLocalUrl($request->getQueryParams()['returnUrl'] ?? ''));
                $view->assign('maxTitleLength', $this->getBackendUser()->uc['titleLen'] ?? 20);
                $content .= $view->render();
            }
        } else {
            $content .= $view->render();
        }

        $this->moduleTemplate->setContent($content);
    }

    /**
     * Get page title with icon, table title and record title
     *
     * @return array
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
        $this->moduleTemplate->setTitle($pageTitle['table'] . ': ' . $pageTitle['title']);
        return $pageTitle;
    }

    /**
     * Get preview for current record
     *
     * @return array
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
     *
     * @return array
     */
    protected function getPropertiesForTable(): array
    {
        $lang = $this->getLanguageService();
        $propertiesForTable = [];
        $propertiesForTable['extraFields'] = $this->getExtraFields();

        // Traverse the list of fields to display for the record:
        $fieldList = $this->getFieldList($this->table, (int)($this->row['uid'] ?? 0));

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
            $propertiesForTable['fields']['folder'] = [
                'fieldValue' => $resourceObject->getParentFolder()->getReadablePath(),
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
                $metaData = $metaDataRepository->findByFileUid($this->row['uid']);
                $allowedFields = $this->getFieldList($table, (int)$metaData['uid']);

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

        return $propertiesForTable;
    }

    /**
     * Get the list of fields that should be shown for the given table
     *
     * @param string $table
     * @param int $uid
     * @return array
     */
    protected function getFieldList(string $table, int $uid): array
    {
        $formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
        $formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $formDataCompilerInput = [
            'command' => 'edit',
            'tableName' => $table,
            'vanillaUid' => $uid,
        ];
        try {
            $result = $formDataCompiler->compile($formDataCompilerInput);
            $fieldList = array_unique(array_values($result['columnsToProcess']));

            $ctrlKeysOfUnneededFields = ['origUid', 'transOrigPointerField', 'transOrigDiffSourceField'];
            foreach ($ctrlKeysOfUnneededFields as $field) {
                if (isset($GLOBALS['TCA'][$table]['ctrl'][$field]) && ($key = array_search($GLOBALS['TCA'][$table]['ctrl'][$field], $fieldList, true)) !== false) {
                    unset($fieldList[$key]);
                }
            }
        } catch (Exception $exception) {
            $fieldList = [];
        }

        $searchFields = GeneralUtility::trimExplode(',', ($GLOBALS['TCA'][$table]['ctrl']['searchFields'] ?? ''));

        return array_unique(array_merge($fieldList, $searchFields));
    }

    /**
     * Get the extra fields (uid, timestamps, creator) for the table
     *
     * @return array
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
            foreach (['crdate' => 'creationDate', 'tstamp' => 'timestamp', 'cruser_id' => 'creationUserId'] as $field => $label) {
                if (isset($GLOBALS['TCA'][$this->table]['ctrl'][$field])) {
                    if ($field === 'crdate' || $field === 'tstamp') {
                        $keyLabelPair[$field] = [
                            'value' => BackendUtility::datetime($this->row[$GLOBALS['TCA'][$this->table]['ctrl'][$field]]),
                            'fieldLabel' => rtrim(htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.' . $label)), ':'),
                            'isDatetime' => true,
                        ];
                    }
                    if ($field === 'cruser_id') {
                        $rowValue = BackendUtility::getProcessedValueExtra($this->table, $GLOBALS['TCA'][$this->table]['ctrl'][$field], $this->row[$GLOBALS['TCA'][$this->table]['ctrl'][$field]]);
                        if ($rowValue) {
                            $creatorRecord = BackendUtility::getRecord('be_users', (int)$rowValue);
                            if ($creatorRecord) {
                                $avatar = GeneralUtility::makeInstance(Avatar::class);
                                $creatorRecord['icon'] = $avatar->render($creatorRecord);
                                $rowValue = $creatorRecord;
                                $keyLabelPair['creatorRecord'] = [
                                    'value' => $rowValue,
                                    'fieldLabel' => rtrim(htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.' . $label)), ':'),
                                ];
                            }
                        }
                    }
                }
            }
        }
        return $keyLabelPair;
    }

    /**
     * Get references section (references from and references to current record)
     *
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function getReferences(ServerRequestInterface $request): array
    {
        $references = [];
        switch ($this->type) {
            case 'db': {
                $references['refLines'] = $this->makeRef($this->table, $this->uid, $request);
                $references['refFromLines'] = $this->makeRefFrom($this->table, $this->uid, $request);
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
     * @param ServerRequestInterface $request
     * @return array
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
     * @param int|\TYPO3\CMS\Core\Resource\File $ref Filename or uid
     * @param ServerRequestInterface $request
     * @return array
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
                $queryBuilder->createNamedParameter($selectTable, \PDO::PARAM_STR)
            ),
            $queryBuilder->expr()->eq(
                'ref_uid',
                $queryBuilder->createNamedParameter($selectUid, \PDO::PARAM_INT)
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
                if ($row['tablename'] === null || $row['recuid'] === null) {
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
     * @param ServerRequestInterface $request
     * @return array
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
                $queryBuilder->createNamedParameter($table, \PDO::PARAM_STR)
            ),
            $queryBuilder->expr()->eq(
                'recuid',
                $queryBuilder->createNamedParameter($ref, \PDO::PARAM_INT)
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
                $line['icon'] = $this->iconFactory->getIconForRecord($row['tablename'], $record, Icon::SIZE_SMALL)->render();
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
     *
     * @param array $referenceRecord
     * @return array
     */
    protected function transformFileReferenceToRecordReference(array $referenceRecord): array
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
                    $queryBuilder->createNamedParameter($referenceRecord['recuid'], \PDO::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAssociative();

        return [
            'recuid' => $fileReference['uid_foreign'],
            'tablename' => $fileReference['tablenames'],
            'field' => $fileReference['fieldname'],
            'flexpointer' => '',
            'softref_key' => '',
            'sorting' => $fileReference['sorting_foreign'],
        ];
    }

    /**
     * @param string $tableName Name of the table
     * @param array $record Record to be checked (ensure pid is resolved for workspaces)
     * @return bool
     */
    protected function canAccessPage(string $tableName, array $record): bool
    {
        $recordPid = (int)($tableName === 'pages' ? $record['uid'] : $record['pid']);
        return $this->getBackendUser()->isInWebMount($tableName === 'pages' ? $record : $record['pid'])
            || $recordPid === 0 && !empty($GLOBALS['TCA'][$tableName]['ctrl']['security']['ignoreRootLevelRestriction']);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
