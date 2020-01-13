<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Controller\ContentElement;

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

use Doctrine\DBAL\Connection;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
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
    use PublicMethodDeprecationTrait;
    use PublicPropertyDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'getLabelForTableColumn' => 'Using ElementInformationController::getLabelForTableColumn() is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * Properties which have been moved to protected status from public
     *
     * @var array
     */
    private $deprecatedPublicProperties = [
        'table' => 'Using $table of class ElementInformationController from the outside is discouraged, as this variable is only used for internal storage.',
        'uid' => 'Using $uid of class ElementInformationController from the outside is discouraged, as this variable is only used for internal storage.',
        'access' => 'Using $access of class ElementInformationController from the outside is discouraged, as this variable is only used for internal storage.',
        'type' => 'Using $type of class ElementInformationController from the outside is discouraged, as this variable is only used for internal storage.',
        'pageInfo' => 'Using $pageInfo of class ElementInformationController from the outside is discouraged, as this variable is only used for internal storage.',
    ];

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

    /**
     * @var \TYPO3\CMS\Core\Resource\File
     */
    protected $fileObject;

    /**
     * @var Folder
     */
    protected $folderObject;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $GLOBALS['SOBE'] = $this;

        // @deprecated since TYPO3 v9, will be obsolete in TYPO3 v10.0 with removal of init()
        $request = $GLOBALS['TYPO3_REQUEST'];
        // @deprecated since TYPO3 v9, will be moved out of __construct() in TYPO3 v10.0
        $this->init($request);
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
        $this->main($request);
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Determines if table/uid point to database record or file and
     * if user has access to view information
     *
     * @param ServerRequestInterface|null $request
     */
    public function init(ServerRequestInterface $request = null): void
    {
        if ($request === null) {
            // Missing argument? This method must have been called from outside.
            // Method will be protected and $request mandatory in TYPO3 v10.0, giving core freedom to move stuff around
            // New v10 signature: "protected function init(ServerRequestInterface $request): void"
            // @deprecated since TYPO3 v9, method argument $request will be set to mandatory
            trigger_error('ElementInformationController->init() will be set to protected in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
            $request = $GLOBALS['TYPO3_REQUEST'];
        }

        $queryParams = $request->getQueryParams();

        $this->table = $queryParams['table'] ?? null;
        $this->uid = $queryParams['uid'] ?? null;

        $this->permsClause = $this->getBackendUser()->getPagePermsClause(Permission::PAGE_SHOW);
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->moduleTemplate->getDocHeaderComponent()->disable();

        if (isset($GLOBALS['TCA'][$this->table])) {
            $this->initDatabaseRecord();
        } elseif ($this->table === '_FILE' || $this->table === '_FOLDER' || $this->table === 'sys_file') {
            $this->initFileOrFolderRecord();
        }
    }

    /**
     * Init database records (table)
     */
    protected function initDatabaseRecord()
    {
        $this->type = 'db';
        $this->uid = (int)$this->uid;

        // Check permissions and uid value:
        if ($this->uid && $this->getBackendUser()->check('tables_select', $this->table)) {
            if ((string)$this->table === 'pages') {
                $this->pageInfo = BackendUtility::readPageAccess($this->uid, $this->permsClause);
                $this->access = is_array($this->pageInfo);
                $this->row = $this->pageInfo;
            } else {
                $this->row = BackendUtility::getRecordWSOL($this->table, $this->uid);
                if ($this->row) {
                    // Find the correct "pid" when a versionized record is given, otherwise "pid = -1" always fails
                    if (!empty($this->row['t3ver_oid'])) {
                        $t3OrigRow = BackendUtility::getRecord($this->table, (int)$this->row['t3ver_oid']);
                        $this->pageInfo = BackendUtility::readPageAccess((int)$t3OrigRow['pid'], $this->permsClause);
                    } else {
                        $this->pageInfo = BackendUtility::readPageAccess($this->row['pid'], $this->permsClause);
                    }
                    $this->access = is_array($this->pageInfo);
                }
            }
        }
    }

    /**
     * Init file/folder parameters
     */
    protected function initFileOrFolderRecord()
    {
        $fileOrFolderObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->uid);

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
            } catch (\Exception $e) {
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
    public function main(ServerRequestInterface $request = null): void
    {
        if ($request === null) {
            // Missing argument? This method must have been called from outside.
            // @deprecated since TYPO3 v9, method argument $request will be set to mandatory
            trigger_error('ElementInformationController->main() will be set to protected in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
            $request = $GLOBALS['TYPO3_REQUEST'];
        }

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
                $view->assign('returnUrl', GeneralUtility::sanitizeLocalUrl($request->getQueryParams()['returnUrl']));
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
            'title' => BackendUtility::getRecordTitle($this->table, $this->row, false)
        ];
        if ($this->type === 'folder') {
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
            /** @var \TYPO3\CMS\Core\Resource\Rendering\RendererRegistry $rendererRegistry */
            $rendererRegistry = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\Rendering\RendererRegistry::class);
            $fileRenderer = $rendererRegistry->getRenderer($this->fileObject);
            $fileExtension = $this->fileObject->getExtension();
            $preview['url'] = $this->fileObject->getPublicUrl(true);

            $width = '590m';
            $heigth = '400m';

            // Check if there is a FileRenderer
            if ($fileRenderer !== null) {
                $preview['fileRenderer'] = $fileRenderer->render(
                    $this->fileObject,
                    $width,
                    $heigth,
                    [],
                    true
                );

            // else check if we can create an Image preview
            } elseif (GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileExtension)) {
                $preview['fileObject'] = $this->fileObject;
                $preview['width'] = $width;
                $preview['heigth'] = $heigth;
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
        $propertiesForTable = [];
        $lang = $this->getLanguageService();

        $extraFields = [
            'uid' => htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:show_item.php.uid'))
        ];

        if (in_array($this->type, ['folder', 'file'], true)) {
            if ($this->type === 'file') {
                $extraFields['creation_date'] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.creationDate'));
                $extraFields['modification_date'] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.timestamp'));
                if ($this->fileObject->getType() === AbstractFile::FILETYPE_IMAGE) {
                    $extraFields['width'] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.width'));
                    $extraFields['height'] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.height'));
                }
            }
            $extraFields['storage'] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_file.storage'));
            $extraFields['folder'] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:folder'));
        } else {
            $extraFields['crdate'] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.creationDate'));
            $extraFields['cruser_id'] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.creationUserId'));
            $extraFields['tstamp'] = htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.timestamp'));

            // check if the special fields are defined in the TCA ctrl section of the table
            foreach ($extraFields as $fieldName => $fieldLabel) {
                if (isset($GLOBALS['TCA'][$this->table]['ctrl'][$fieldName])) {
                    $extraFields[$GLOBALS['TCA'][$this->table]['ctrl'][$fieldName]] = $fieldLabel;
                } elseif ($fieldName !== 'uid') {
                    unset($extraFields[$fieldName]);
                }
            }
        }

        foreach ($extraFields as $name => $fieldLabel) {
            $rowValue = '';
            $thisRow = [];
            if (!isset($this->row[$name])) {
                $resourceObject = $this->fileObject ?: $this->folderObject;
                if ($name === 'storage') {
                    $rowValue = $resourceObject->getStorage()->getName();
                } elseif ($name === 'folder') {
                    $rowValue = $resourceObject->getParentFolder()->getReadablePath();
                } elseif ($name === 'width') {
                    $rowValue = $this->fileObject->getProperty('width') . 'px';
                } elseif ($name === 'height') {
                    $rowValue = $this->fileObject->getProperty('height') . 'px';
                }
            } elseif ($name === 'creation_date' || $name === 'modification_date' || $name === 'tstamp' || $name === 'crdate') {
                $rowValue = BackendUtility::datetime($this->row[$name]);
            } else {
                $rowValue = BackendUtility::getProcessedValueExtra($this->table, $name, $this->row[$name]);
            }
            $thisRow['value'] = $rowValue;
            $thisRow['fieldLabel'] = rtrim($fieldLabel, ':');
            // show the backend username who created the issue
            if ($name === 'cruser_id' && $rowValue) {
                $creatorRecord = BackendUtility::getRecord('be_users', $rowValue);
                if ($creatorRecord) {
                    /** @var Avatar $avatar */
                    $avatar = GeneralUtility::makeInstance(Avatar::class);
                    $creatorRecord['icon'] = $avatar->render($creatorRecord);
                    $thisRow['creatorRecord'] = $creatorRecord;
                    $thisRow['value'] = '';
                }
            }
            $propertiesForTable['extraFields'][] = $thisRow;
        }

        // Traverse the list of fields to display for the record:
        $fieldList = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$this->table]['interface']['showRecordFieldList'], true);
        foreach ($fieldList as $name) {
            $thisRow = [];
            $name = trim($name);
            $uid = $this->row['uid'];

            if (!isset($GLOBALS['TCA'][$this->table]['columns'][$name])) {
                continue;
            }

            // Storage is already handled above
            if ($this->type === 'file' && $name === 'storage') {
                continue;
            }

            // format file size as bytes/kilobytes/megabytes
            if ($this->type === 'file' && $name === 'size') {
                $this->row[$name] = GeneralUtility::formatSize($this->row[$name], htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:byteSizeUnits')));
            }

            $isExcluded = !(!$GLOBALS['TCA'][$this->table]['columns'][$name]['exclude'] || $this->getBackendUser()->check('non_exclude_fields', $this->table . ':' . $name));
            if ($isExcluded) {
                continue;
            }

            $thisRow['fieldValue'] = BackendUtility::getProcessedValue($this->table, $name, $this->row[$name], 0, 0, false, $uid);
            $thisRow['fieldLabel'] = htmlspecialchars($lang->sL(BackendUtility::getItemLabel($this->table, $name)));
            $propertiesForTable['fields'][] = $thisRow;
        }
        return $propertiesForTable;
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
                $references['refLines'] = $this->makeRef($this->table, $this->row['uid'], $request);
                $references['refFromLines'] = $this->makeRefFrom($this->table, $this->row['uid'], $request);
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
    protected function getLabelForTableColumn($tableName, $fieldName)
    {
        if ($GLOBALS['TCA'][$tableName]['columns'][$fieldName]['label'] !== null) {
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
                    $uid => 'edit'
                ]
            ],
            'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri()
        ];
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $actions['recordEditUrl'] = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);

        // History button
        $urlParameters = [
            'element' => $table . ':' . $uid,
            'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri()
        ];
        $actions['recordHistoryUrl'] = (string)$uriBuilder->buildUriFromRoute('record_history', $urlParameters);

        if ($table === 'pages') {
            // Recordlist button
            $actions['webListUrl'] = (string)$uriBuilder->buildUriFromRoute('web_list', ['id' => $uid, 'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri()]);

            // View page button
            $actions['viewOnClick'] = BackendUtility::viewOnClick($uid, '', BackendUtility::BEgetRootLine($uid));
        }

        return $actions;
    }

    /**
     * Make reference display
     *
     * @param string $table Table name
     * @param string|\TYPO3\CMS\Core\Resource\File $ref Filename or uid
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function makeRef($table, $ref, ServerRequestInterface $request)
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
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
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
            $queryBuilder->expr()->eq(
                'deleted',
                $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
            )
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
            ->execute()
            ->fetchAll();

        // Compile information for title tag:
        foreach ($rows as $row) {
            if ($row['tablename'] === 'sys_file_reference') {
                $row = $this->transformFileReferenceToRecordReference($row);
                if ($row['tablename'] === null || $row['recuid'] === null) {
                    return;
                }
            }

            $line = [];
            $record = BackendUtility::getRecord($row['tablename'], $row['recuid']);
            if ($record) {
                BackendUtility::fixVersioningPid($row['tablename'], $record);
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
                            $row['recuid'] => 'edit'
                        ]
                    ],
                    'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri()
                ];
                /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
                $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
                $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                $line['url'] = $url;
                $line['icon'] = $this->iconFactory->getIconForRecord($row['tablename'], $record, Icon::SIZE_SMALL)->render();
                $line['row'] = $row;
                $line['record'] = $record;
                $line['recordTitle'] = BackendUtility::getRecordTitle($row['tablename'], $record, false, true);
                $line['parentRecordTitle'] = $parentRecordTitle;
                $line['title'] = $lang->sL($GLOBALS['TCA'][$row['tablename']]['ctrl']['title']);
                $line['labelForTableColumn'] = $this->getLabelForTableColumn($row['tablename'], $row['field']);
                $line['path'] = BackendUtility::getRecordPath($record['pid'], '', 0, 0);
                $line['actions'] = $this->getRecordActions($row['tablename'], $row['recuid'], $request);
            } else {
                $line['row'] = $row;
                $line['title'] = $lang->sL($GLOBALS['TCA'][$row['tablename']]['ctrl']['title']) ?: $row['tablename'];
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
     * @param string $ref Filename or uid
     * @param ServerRequestInterface $request
     * @return array
     */
    protected function makeRefFrom($table, $ref, ServerRequestInterface $request): array
    {
        $refFromLines = [];
        $lang = $this->getLanguageService();

        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
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
            )
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
            ->execute()
            ->fetchAll();

        // Compile information for title tag:
        foreach ($rows as $row) {
            $line = [];
            $record = BackendUtility::getRecord($row['ref_table'], $row['ref_uid']);
            if ($record) {
                BackendUtility::fixVersioningPid($row['ref_table'], $record);
                if (!$this->canAccessPage($row['ref_table'], $record)) {
                    continue;
                }
                $urlParameters = [
                    'edit' => [
                        $row['ref_table'] => [
                            $row['ref_uid'] => 'edit'
                        ]
                    ],
                    'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri()
                ];
                /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
                $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
                $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                $line['url'] = $url;
                $line['icon'] = $this->iconFactory->getIconForRecord($row['tablename'], $record, Icon::SIZE_SMALL)->render();
                $line['row'] = $row;
                $line['record'] = $record;
                $line['recordTitle'] = BackendUtility::getRecordTitle($row['ref_table'], $record, false, true);
                $line['title'] = $lang->sL($GLOBALS['TCA'][$row['ref_table']]['ctrl']['title']);
                $line['labelForTableColumn'] = $this->getLabelForTableColumn($table, $row['field']);
                $line['path'] = BackendUtility::getRecordPath($record['pid'], '', 0, 0);
                $line['actions'] = $this->getRecordActions($row['ref_table'], $row['ref_uid'], $request);
            } else {
                $line['row'] = $row;
                $line['title'] = $lang->sL($GLOBALS['TCA'][$row['ref_table']]['ctrl']['title']);
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
    protected function transformFileReferenceToRecordReference(array $referenceRecord)
    {
        /** @var \TYPO3\CMS\Core\Database\Query\QueryBuilder $queryBuilder */
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
            ->execute()
            ->fetch();

        return [
            'recuid' => $fileReference['uid_foreign'],
            'tablename' => $fileReference['tablenames'],
            'field' => $fileReference['fieldname'],
            'flexpointer' => '',
            'softref_key' => '',
            'sorting' => $fileReference['sorting_foreign']
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
     * Returns LanguageService
     *
     * @return \TYPO3\CMS\Core\Localization\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
