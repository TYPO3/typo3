<?php

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

namespace TYPO3\CMS\Filelist;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\InaccessibleFolder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Resource\Utility\ListUtility;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Filelist\Dto\PaginationLink;
use TYPO3\CMS\Filelist\Dto\ResourceCollection;
use TYPO3\CMS\Filelist\Dto\ResourceView;
use TYPO3\CMS\Filelist\Dto\UserPermissions;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;
use TYPO3\CMS\Filelist\Pagination\ResourceCollectionPaginator;
use TYPO3\CMS\Filelist\Type\NavigationDirection;
use TYPO3\CMS\Filelist\Type\ViewMode;

/**
 * Class for rendering of File>Filelist (basically used in FileListController)
 * @see \TYPO3\CMS\Filelist\Controller\FileListController
 * @internal this is a concrete TYPO3 controller implementation and solely used for EXT:filelist and not part of TYPO3's Core API.
 */
class FileList
{
    public ViewMode $viewMode = ViewMode::TILES;

    /**
     * Default Max items shown
     */
    public int $itemsPerPage = 40;

    /**
     * Current Page
     */
    public int $currentPage = 1;

    /**
     * Total file size of the current selection
     */
    public int $totalbytes = 0;

    /**
     * Total count of folders and files
     */
    public int $totalItems = 0;

    /**
     * The field to sort by
     */
    public string $sort = '';

    /**
     * Reverse sorting flag
     */
    public bool $sortRev = true;

    /**
     * Thumbnails on records containing files (pictures)
     */
    public bool $thumbs = false;

    /**
     * Space icon used for alignment when no button is available
     */
    public string $spaceIcon;

    /**
     * Max length of strings
     */
    public int $maxTitleLength = 30;

    /**
     * Decides the columns shown. Filled with values that refers to the keys of the data-array. $this->fieldArray[0] is the title column.
     */
    public array $fieldArray = [];

    /**
     * Keys are fieldnames and values are td-css-classes to add in addElement();
     *
     * @var array<string, string>
     */
    public array $addElement_tdCssClass = [
        '_CONTROL_' => 'col-control',
        '_SELECTOR_' => 'col-selector',
        'icon' => 'col-icon',
        'name' => 'col-title col-responsive',
    ];

    /**
     * @var Folder
     */
    protected $folderObject;

    /**
     * @var Clipboard $clipObj
     */
    public $clipObj;

    protected ?FileSearchDemand $searchDemand = null;
    protected ?FileExtensionFilter $fileExtensionFilter = null;
    protected EventDispatcherInterface $eventDispatcher;
    protected ServerRequestInterface $request;
    protected IconFactory $iconFactory;
    protected ResourceFactory $resourceFactory;
    protected UriBuilder $uriBuilder;
    protected TranslationConfigurationProvider $translateTools;

    public function __construct(ServerRequestInterface $request)
    {
        $this->request = $request;

        // Setting the maximum length of the filenames to the user's settings or minimum 30 (= $this->maxTitleLength)
        $this->maxTitleLength = max($this->maxTitleLength, (int)($this->getBackendUser()->uc['titleLen'] ?? 1));
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $this->translateTools = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
        $this->itemsPerPage = MathUtility::forceIntegerInRange(
            $this->getBackendUser()->getTSConfig()['options.']['file_list.']['filesPerPage'] ?? $this->itemsPerPage,
            1
        );
        // Create clipboard object and initialize that
        $this->clipObj = GeneralUtility::makeInstance(Clipboard::class);
        $this->clipObj->initializeClipboard($request);
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $this->getLanguageService()->includeLLFile('EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf');
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_common.xlf');
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->spaceIcon = '<span class="btn btn-default disabled">' . $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
        // Initialize file extension filter, if configured
        $fileDownloadConfiguration = (array)($this->getBackendUser()->getTSConfig()['options.']['file_list.']['fileDownload.'] ?? []);
        if ($fileDownloadConfiguration !== []) {
            $this->fileExtensionFilter = GeneralUtility::makeInstance(FileExtensionFilter::class);
            $this->fileExtensionFilter->setAllowedFileExtensions(
                GeneralUtility::trimExplode(',', (string)($fileDownloadConfiguration['allowedFileExtensions'] ?? ''), true)
            );
            $this->fileExtensionFilter->setDisallowedFileExtensions(
                GeneralUtility::trimExplode(',', (string)($fileDownloadConfiguration['disallowedFileExtensions'] ?? ''), true)
            );
        }
    }

    /**
     * Initialization of class
     *
     * @param Folder $folderObject The folder to work on
     * @param int $currentPage The current page to render
     * @param string $sort Sorting column
     * @param bool $sortRev Sorting direction
     */
    public function start(Folder $folderObject, int $currentPage, $sort, $sortRev)
    {
        $this->folderObject = $folderObject;
        $this->totalbytes = 0;
        $this->sort = $sort;
        $this->sortRev = $sortRev;
        $this->currentPage = MathUtility::forceIntegerInRange($currentPage, 1, 100000);
        $this->fieldArray = [
            '_SELECTOR_', 'icon', 'name', '_CONTROL_', 'record_type', 'size', 'rw', '_REF_',
        ];
    }

    public function setColumnsToRender(array $additionalFields = []): void
    {
        $this->fieldArray = array_unique(array_merge($this->fieldArray, $additionalFields));
    }

    protected function renderTiles(ResourceCollectionPaginator $paginator, ViewInterface $view): string
    {
        // Prepare Resources for View
        $resourceViews = [];
        foreach ($paginator->getPaginatedItems() as $resource) {
            $resourceView = new ResourceView(
                $resource,
                $this->getUserPermissions($resource),
                $this->iconFactory->getIconForResource($resource, Icon::SIZE_SMALL)
            );
            $resourceView->moduleUri = $this->createModuleUriForResource($resource);
            $resourceView->editDataUri = $this->createEditDataUriForResource($resource);
            $resourceView->editContentUri = $this->createEditContentUriForResource($resource);
            $resourceView->replaceUri = $this->createReplaceUriForResource($resource);
            $resourceView->renameUri = $this->createRenameUriForResource($resource);
            $resourceViews[] = $resourceView;
        }

        $view->assign('displayThumbs', $this->thumbs);
        $view->assign('pagination', [
            'backward' => $this->getPaginationLinkForDirection($paginator, NavigationDirection::BACKWARD),
            'forward' => $this->getPaginationLinkForDirection($paginator, NavigationDirection::FORWARD),
        ]);
        $view->assign('resources', $resourceViews);

        return $view->render('Filelist/Tiles');
    }

    protected function renderList(ResourceCollectionPaginator $paginator, ViewInterface $view): string
    {
        $resources = $paginator->getPaginatedItems();
        $folders = $resources->getFolders();
        $files = $resources->getFiles();

        $output = $this->renderListForwardBackwardNavigation($paginator, NavigationDirection::BACKWARD);
        $output .= $this->formatDirList($folders);
        $output .= $this->formatFileList($files);
        $output .= $this->renderListForwardBackwardNavigation($paginator, NavigationDirection::FORWARD);

        // Header line is drawn
        $theData = [];
        foreach ($this->fieldArray as $fieldName) {
            if ($fieldName === '_SELECTOR_') {
                $theData[$fieldName] = $this->renderCheckboxActions();
            } elseif ($fieldName === '_CONTROL_') {
                // Special case: The control column header should not be wrapped into a sort link
                $theData[$fieldName] = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels._CONTROL_');
            } elseif ($specialLabel = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.' . $fieldName)) {
                $theData[$fieldName] = $this->linkWrapSort($fieldName, $specialLabel);
            } elseif ($customLabel = $this->getLanguageService()->getLL('c_' . $fieldName)) {
                $theData[$fieldName] = $this->linkWrapSort($fieldName, $customLabel);
            } elseif ($fieldName !== 'icon') {
                // Normal database field
                $theData[$fieldName] = $this->linkWrapSort($fieldName);
            }
        }

        $view->assign('tableHeaderHtml', $this->addElement($theData, [], true));
        $view->assign('tableBodyHtml', $output);

        return $view->render('Filelist/List');
    }

    protected function renderListForwardBackwardNavigation(
        ResourceCollectionPaginator $paginator,
        NavigationDirection $direction
    ): string {
        if (!$link = $this->getPaginationLinkForDirection($paginator, $direction)) {
            return '';
        }

        $iconIdentifier = null;
        switch ($direction) {
            case NavigationDirection::BACKWARD:
                $iconIdentifier = 'actions-move-up';
                break;
            case NavigationDirection::FORWARD:
                $iconIdentifier = 'actions-move-down';
                break;
        }

        $data = [];
        $data['_SELECTOR_'] = '<a href="' . htmlspecialchars($link->uri) . '">'
            . ($iconIdentifier !== null ? $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render() : '')
            . '<i>[' . $link->label . ']</i>'
            . '</a>';

        return $this->addElement($data);
    }

    public function render(?FileSearchDemand $searchDemand, ViewInterface $view): string
    {
        if ($searchDemand !== null) {
            $this->searchDemand = $searchDemand;
            $folders = [];
            $files = iterator_to_array($this->folderObject->searchFiles($this->searchDemand));
            // Add special "Path" field for the search result
            array_splice($this->fieldArray, 3, 0, '_PATH_');
        } else {
            $storage = $this->folderObject->getStorage();
            $storage->resetFileAndFolderNameFiltersToDefault();
            if (!$this->folderObject->getStorage()->isBrowsable()) {
                return '';
            }
            $folders = $storage->getFoldersInFolder($this->folderObject);
            $files = $this->folderObject->getFiles();
        }

        // Remove processing folders
        $folders = array_filter($folders, static function (Folder $folder) {
            return $folder->getRole() !== FolderInterface::ROLE_PROCESSING;
        });

        $resourceCollection = new ResourceCollection($folders + $files);
        $this->totalItems = $resourceCollection->getTotalCount();
        $this->totalbytes = $resourceCollection->getTotalBytes();

        // Sort the files before sending it to the renderer
        if (trim($this->sort) !== '') {
            $resourceCollection->setResources($this->sortResources($resourceCollection->getResources(), $this->sort));
        }

        $pagination = new ResourceCollectionPaginator($resourceCollection, $this->currentPage, $this->itemsPerPage);
        if ($this->viewMode === ViewMode::TILES) {
            return $this->renderTiles($pagination, $view);
        }

        return $this->renderList($pagination, $view);
    }

    /**
     * Returns a table-row with the content from the fields in the input data array.
     * OBS: $this->fieldArray MUST be set! (represents the list of fields to display)
     *
     * @param array $data Is the data array, record with the fields. Notice: These fields are (currently) NOT htmlspecialchar'ed before being wrapped in <td>-tags
     * @param array $attributes Attributes for the table row. Values will be htmlspecialchar'ed!
     * @param bool $isTableHeader Whether the element to be added is a table header
     *
     * @return string HTML content for the table row
     */
    public function addElement(array $data, array $attributes = [], bool $isTableHeader = false): string
    {
        // Initialize rendering.
        $cols = [];
        $colType = $isTableHeader ? 'th' : 'td';
        $colspan = '';
        $colspanCounter = 0;
        $lastField = '';
        // Traverse field array which contains the data to present:
        foreach ($this->fieldArray as $fieldName) {
            if (isset($data[$fieldName])) {
                if ($lastField && isset($data[$lastField])) {
                    $cssClass = $this->addElement_tdCssClass[$lastField] ?? '';
                    $cols[] = '<' . $colType . ' class="' . $cssClass . '"' . $colspan . '>' . $data[$lastField] . '</' . $colType . '>';
                }
                $lastField = $fieldName;
                $colspanCounter = 1;
            } else {
                if (!$lastField) {
                    $lastField = $fieldName;
                }
                $colspanCounter++;
            }
            $colspan = ($colspanCounter > 1) ? ' colspan="' . $colspanCounter . '"' : '';
        }
        if ($lastField) {
            $cssClass = $this->addElement_tdCssClass[$lastField] ?? '';
            $cols[] = '<' . $colType . ' class="' . $cssClass . '"' . $colspan . '>' . $data[$lastField] . '</' . $colType . '>';
        }

        // Add the table row
        return '
            <tr ' . GeneralUtility::implodeAttributes($attributes, true) . '>
                ' . implode(PHP_EOL, $cols) . '
            </tr>';
    }

    /**
     * Gets the number of files and total size of a folder
     */
    public function getFolderInfo(): string
    {
        if ($this->totalItems == 1) {
            $fileLabel = $this->getLanguageService()->getLL('file');
        } else {
            $fileLabel = $this->getLanguageService()->getLL('files');
        }
        return $this->totalItems . ' ' . htmlspecialchars($fileLabel) . ', ' . GeneralUtility::formatSize($this->totalbytes, htmlspecialchars($this->getLanguageService()->getLL('byteSizeUnits')));
    }

    /**
     * This returns tablerows for the directories in the array $items['sorting'].
     *
     * @param Folder[] $folders
     */
    protected function formatDirList(array $folders): string
    {
        $out = '';
        foreach (ListUtility::resolveSpecialFolderNames($folders) as $folderName => $folderObject) {
            $role = $folderObject->getRole();
            if ($role !== FolderInterface::ROLE_DEFAULT) {
                $displayName = '<strong>' . htmlspecialchars($folderName) . '</strong>';
            } else {
                $displayName = htmlspecialchars($folderName);
            }

            $isLocked = $folderObject instanceof InaccessibleFolder;
            $isWritable = $folderObject->checkActionPermission('write');

            // The icon - will be linked later on, if not locked
            $theIcon = $this->getFileOrFolderIcon($folderName, $folderObject);

            // Preparing and getting the data-array
            $theData = [];

            // Preparing table row attributes
            $attributes = [
                'data-type' => 'folder',
                'data-identifier' => $folderObject->getCombinedIdentifier(),
                'data-name' => $folderObject->getName(),
                'data-folder-identifier' => $folderObject->getIdentifier(),
                'data-multi-record-selection-element' => 'true',
                'data-filelist-draggable' => 'true',
                'data-filelist-draggable-container' => 'true',
                'data-state-identifier' => $folderObject->getStorage()->getUid() . '_' . GeneralUtility::md5int($folderObject->getIdentifier()),
                'draggable' => $folderObject->checkActionPermission('move') ? 'true' : 'false',
            ];
            if ($isLocked) {
                foreach ($this->fieldArray as $field) {
                    $theData[$field] = '';
                }
                $theData['icon'] = $theIcon;
                $theData['name'] = $displayName;
            } else {
                foreach ($this->fieldArray as $field) {
                    switch ($field) {
                        case 'size':
                            try {
                                $numFiles = $folderObject->getFileCount();
                            } catch (InsufficientFolderAccessPermissionsException $e) {
                                $numFiles = 0;
                            }
                            $theData[$field] = $numFiles . ' ' . htmlspecialchars($this->getLanguageService()->getLL(($numFiles === 1 ? 'file' : 'files')));
                            break;
                        case 'rw':
                            $theData[$field] = '<strong class="text-danger">' . htmlspecialchars($this->getLanguageService()->getLL('read')) . '</strong>' . (!$isWritable ? '' : '<strong class="text-danger">' . htmlspecialchars($this->getLanguageService()->getLL('write')) . '</strong>');
                            break;
                        case 'record_type':
                            $theData[$field] = htmlspecialchars($this->getLanguageService()->getLL('folder'));
                            break;
                        case 'icon':
                            $theData[$field] = BackendUtility::wrapClickMenuOnIcon($theIcon, 'sys_file', $folderObject->getCombinedIdentifier());
                            break;
                        case 'name':
                            $theData[$field] = $this->linkWrapDir($displayName, $folderObject);
                            break;
                        case '_CONTROL_':
                            $theData[$field] = $this->makeEdit($folderObject);
                            break;
                        case '_SELECTOR_':
                            $theData[$field] = $this->makeCheckbox($folderObject);
                            break;
                        case '_REF_':
                            $theData[$field] = '-';
                            break;
                        case '_PATH_':
                            $theData[$field] = $this->makePath($folderObject);
                            break;
                        default:
                            $theData[$field] = GeneralUtility::fixed_lgd_cs($theData[$field] ?? '', $this->maxTitleLength);
                    }
                }
            }
            $out .= $this->addElement($theData, $attributes);
        }
        return $out;
    }

    /**
     * Wraps the directory-titles
     *
     * @param string $title String to be wrapped in links
     * @param Folder $folderObject Folder to work on
     */
    protected function linkWrapDir(string $title, Folder $folderObject): string
    {
        $href = $this->createModuleUriForResource($folderObject);
        // Sometimes $code contains plain HTML tags. In such a case the string should not be modified!
        if ($title === strip_tags($title)) {
            return '<a href="' . htmlspecialchars($href) . '" title="' . htmlspecialchars($title) . '" data-filelist-draggable>' . $title . '</a>';
        }
        return '<a href="' . htmlspecialchars($href) . '" data-filelist-draggable>' . $title . '</a>';
    }

    /**
     * Wraps filenames in links which opens the metadata editor.
     *
     * @param string $code String to be wrapped in links
     * @param File $fileObject File to be linked
     */
    protected function linkWrapFile(string $code, File $fileObject): string
    {
        try {
            if ($this->isEditMetadataAllowed($fileObject)
                && ($metaDataUid = $fileObject->getMetaData()->offsetGet('uid'))
            ) {
                $urlParameters = [
                    'edit' => [
                        'sys_file_metadata' => [
                            $metaDataUid => 'edit',
                        ],
                    ],
                    'returnUrl' => $this->createModuleUri(),
                ];
                $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                $title = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editMetadata'));
                $code = '<a class="responsive-title" href="' . htmlspecialchars($url) . '" title="' . $title . '" data-filelist-draggable>' . $code . '</a>';
            }
        } catch (\Exception $e) {
            // intentional fall-through
        }
        return $code;
    }

    /**
     * This returns tablerows for the files in the array $items['sorting'].
     *
     * @param File[] $files File items
     */
    protected function formatFileList(array $files): string
    {
        $out = '';
        foreach ($files as $fileObject) {
            $ext = $fileObject->getExtension();
            $fileUid = $fileObject->getUid();
            $fileName = trim($fileObject->getName());
            // Preparing and getting the data-array
            $theData = [];
            // Preparing table row attributes
            $attributes = [
                'data-type' => 'file',
                'data-identifier' => $fileObject->getCombinedIdentifier(),
                'data-name' => $fileObject->getName(),
                'data-file-uid' => $fileUid,
                'data-filelist-draggable' => 'true',
                'data-filelist-draggable-container' => 'true',
                'data-multi-record-selection-element' => 'true',
                'draggable' => $fileObject->checkActionPermission('move') ? 'true' : 'false',
            ];
            if ($this->isEditMetadataAllowed($fileObject)
                && ($metaDataUid = $fileObject->getMetaData()->offsetGet('uid'))
            ) {
                $attributes['data-metadata-uid'] = (string)$metaDataUid;
            }
            foreach ($this->fieldArray as $field) {
                switch ($field) {
                    case 'size':
                        $theData[$field] = GeneralUtility::formatSize((int)$fileObject->getSize(), htmlspecialchars($this->getLanguageService()->getLL('byteSizeUnits')));
                        break;
                    case 'rw':
                        $theData[$field] = '' . (!$fileObject->checkActionPermission('read') ? ' ' : '<strong class="text-danger">' . htmlspecialchars($this->getLanguageService()->getLL('read')) . '</strong>') . (!$fileObject->checkActionPermission('write') ? '' : '<strong class="text-danger">' . htmlspecialchars($this->getLanguageService()->getLL('write')) . '</strong>');
                        break;
                    case 'record_type':
                        $theData[$field] = htmlspecialchars($this->getLanguageService()->getLL('file') . ($ext ? ' (' . strtoupper($ext) . ')' : ''));
                        break;
                    case '_CONTROL_':
                        $theData[$field] = $this->makeEdit($fileObject);
                        break;
                    case '_SELECTOR_':
                        $theData[$field] = $this->makeCheckbox($fileObject);
                        break;
                    case '_REF_':
                        $theData[$field] = $this->makeRef($fileObject);
                        break;
                    case '_PATH_':
                        $theData[$field] = $this->makePath($fileObject);
                        break;
                    case 'icon':
                        $theData[$field] = BackendUtility::wrapClickMenuOnIcon($this->getFileOrFolderIcon($fileName, $fileObject), 'sys_file', $fileObject->getCombinedIdentifier());
                        break;
                    case 'name':
                        // Edit metadata of file
                        $theData[$field] = $this->linkWrapFile(htmlspecialchars($fileName), $fileObject);

                        if ($fileObject->isMissing()) {
                            $theData[$field] .= '<span class="badge badge-danger badge-space-left">'
                                . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing'))
                                . '</span>';
                        // Thumbnails?
                        } elseif ($this->thumbs && ($fileObject->isImage() || $fileObject->isMediaFile())) {
                            $processedFile = $fileObject->process(
                                ProcessedFile::CONTEXT_IMAGEPREVIEW,
                                [
                                    'width' => (int)($this->getBackendUser()->getTSConfig()['options.']['file_list.']['thumbnail.']['width'] ?? 64),
                                    'height' => (int)($this->getBackendUser()->getTSConfig()['options.']['file_list.']['thumbnail.']['height'] ?? 64),
                                ]
                            );
                            $theData[$field] .= '<br /><img src="' . htmlspecialchars($processedFile->getPublicUrl() ?? '') . '" ' .
                                'width="' . htmlspecialchars($processedFile->getProperty('width')) . '" ' .
                                'height="' . htmlspecialchars($processedFile->getProperty('height')) . '" ' .
                                'title="' . htmlspecialchars($fileName) . '" alt="" ' .
                                'data-filelist-draggable />';
                        }
                        break;
                    case 'crdate':
                        $crdate = $fileObject->getCreationTime();
                        $theData[$field] = $crdate ? BackendUtility::datetime($crdate) : '-';
                        break;
                    case 'tstamp':
                        $tstamp = $fileObject->getModificationTime();
                        $theData[$field] = $tstamp ? BackendUtility::datetime($tstamp) : '-';
                        break;
                    default:
                        $theData[$field] = '';
                        if ($fileObject->hasProperty($field)) {
                            if ($field === 'storage') {
                                // Fetch storage name of the current file
                                $storage = GeneralUtility::makeInstance(StorageRepository::class)->findByUid((int)$fileObject->getProperty($field));
                                if ($storage !== null) {
                                    $theData[$field] = htmlspecialchars($storage->getName());
                                }
                            } else {
                                $theData[$field] = htmlspecialchars(
                                    (string)BackendUtility::getProcessedValueExtra(
                                        $this->getConcreteTableName($field),
                                        $field,
                                        $fileObject->getProperty($field),
                                        $this->maxTitleLength,
                                        $fileObject->getMetaData()->offsetGet('uid')
                                    )
                                );
                            }
                        }
                }
            }
            $out .= $this->addElement($theData, $attributes);
        }
        return $out;
    }

    /**
     * Fetch the translations for a sys_file_metadata record
     *
     * @param array $metaDataRecord
     * @return array<int, array<string, mixed>> keys are the site language ids, values are the $rows
     */
    protected function getTranslationsForMetaData($metaDataRecord)
    {
        $languageField = $GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField'] ?? '';
        $languageParentField = $GLOBALS['TCA']['sys_file_metadata']['ctrl']['transOrigPointerField'] ?? '';

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->getRestrictions()->removeAll();
        $translationRecords = $queryBuilder->select('*')
            ->from('sys_file_metadata')
            ->where(
                $queryBuilder->expr()->eq(
                    $languageParentField,
                    $queryBuilder->createNamedParameter($metaDataRecord['uid'] ?? 0, Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    $languageField,
                    $queryBuilder->createNamedParameter(0, Connection::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $translations = [];
        foreach ($translationRecords as $record) {
            $languageId = $record[$languageField];
            $translations[$languageId] = $record;
        }
        return $translations;
    }

    /**
     * Wraps a field label for the header row into a link to the filelist with sorting commands
     *
     * @param string $fieldName The field to sort
     * @param string $label The label to be wrapped - will be determined if not given
     */
    public function linkWrapSort(string $fieldName, string $label = ''): string
    {
        // Determine label if not given
        if ($label === '') {
            $lang = $this->getLanguageService();
            $concreteTableName = $this->getConcreteTableName($fieldName);
            $label = BackendUtility::getItemLabel($concreteTableName, $fieldName);
            // In case global TSconfig exists we have to check if the label is overridden there
            $tsConfig = BackendUtility::getPagesTSconfig(0);
            $label = $lang->translateLabel(
                $tsConfig['TCEFORM.'][$concreteTableName . '.'][$fieldName . '.']['label.'] ?? [],
                $tsConfig['TCEFORM.'][$concreteTableName . '.'][$fieldName . '.']['label'] ?? $label
            );
        }

        $params = ['sort' => $fieldName, 'currentPage' => 0];

        if ($this->sort === $fieldName) {
            // Check reverse sorting
            $params['reverse'] = ($this->sortRev ? '0' : '1');
            $sortArrow = $this->iconFactory->getIcon('status-status-sorting-' . ($this->sortRev ? 'desc' : 'asc'), Icon::SIZE_SMALL)->render();
        } else {
            $params['reverse'] = 0;
            $sortArrow = '';
        }
        $href = $this->createModuleUri($params);

        return '<a href="' . htmlspecialchars($href) . '">' . htmlspecialchars($label) . ' ' . $sortArrow . '</a>';
    }

    /**
     * Creates the clipboard actions
     *
     * @param File|Folder $fileOrFolderObject Array with information about the file/directory for which to make the clipboard actions for the listing.
     */
    public function makeClip($fileOrFolderObject): array
    {
        if (!$fileOrFolderObject->checkActionPermission('read')) {
            return [];
        }
        $actions = [];
        $fullIdentifier = $fileOrFolderObject->getCombinedIdentifier();
        $fullName = $fileOrFolderObject->getName();
        $md5 = md5($fullIdentifier);

        // Add copy/cut buttons in "normal" mode:
        if ($this->clipObj->current === 'normal') {
            $isSel = $this->clipObj->isSelected('_FILE', $md5);

            if ($fileOrFolderObject->checkActionPermission('copy')) {
                $copyTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.' . ($isSel === 'copy' ? 'copyrelease' : 'copy'));
                $copyUrl = $this->clipObj->selUrlFile($fullIdentifier, true, $isSel === 'copy');
                $actions['copy'] = '
                    <a class="btn btn-default" href="' . htmlspecialchars($copyUrl) . '" title="' . htmlspecialchars($copyTitle) . '">
                        ' . $this->iconFactory->getIcon($isSel === 'copy' ? 'actions-edit-copy-release' : 'actions-edit-copy', Icon::SIZE_SMALL)->render() . '
                    </a>';
            }

            if ($fileOrFolderObject->checkActionPermission('move')) {
                $cutTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.' . ($isSel === 'cut' ? 'cutrelease' : 'cut'));
                $cutUrl = $this->clipObj->selUrlFile($fullIdentifier, false, $isSel === 'cut');
                $actions['cut'] = '
                    <a class="btn btn-default" href="' . htmlspecialchars($cutUrl) . '" title="' . htmlspecialchars($cutTitle) . '">
                        ' . $this->iconFactory->getIcon($isSel === 'cut' ? 'actions-edit-cut-release' : 'actions-edit-cut', Icon::SIZE_SMALL)->render() . '
                    </a>';
            }
        }

        $elFromTable = $this->clipObj->elFromTable('_FILE');
        $addPasteButton = $this->folderObject->checkActionPermission(
            ($this->clipObj->clipData[$this->clipObj->current]['mode'] ?? '') === 'copy' ? 'copy' : 'move'
        );
        if (!$fileOrFolderObject instanceof Folder
            || $elFromTable === []
            || !$addPasteButton
            || !$fileOrFolderObject->checkActionPermission('write')
        ) {
            //early return actions, in case paste should not be displayed
            return $actions;
        }

        $elToConfirm = [];
        foreach ($elFromTable as $key => $element) {
            $clipBoardElement = $this->resourceFactory->retrieveFileOrFolderObject($element);
            if ($clipBoardElement instanceof Folder
                && $clipBoardElement->getStorage()->isWithinFolder($clipBoardElement, $fileOrFolderObject)
            ) {
                // In case folder is already present in the target folder, return actions without paste button
                return $actions;
            }
            $elToConfirm[$key] = $clipBoardElement->getName();
        }

        $pasteUrl = $this->clipObj->pasteUrl('_FILE', $fullIdentifier);
        $pasteTitle = $this->getLanguageService()->getLL('clip_pasteInto');
        $pasteContent = $this->clipObj->confirmMsgText('_FILE', $fullName, 'into', $elToConfirm);
        $actions[] = '
                <a class="btn btn-default t3js-modal-trigger" data-severity="warning"  href="' . htmlspecialchars($pasteUrl) . '" data-bs-content="' . htmlspecialchars($pasteContent) . '" data-title="' . htmlspecialchars($pasteTitle) . '" title="' . htmlspecialchars($pasteTitle) . '">
                    ' . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render() . '
                </a>';

        return $actions;
    }

    /**
     * Adds the checkbox to select a file/folder in the listing
     *
     * @param File|Folder $fileOrFolderObject
     */
    protected function makeCheckbox($fileOrFolderObject): string
    {
        if (!$fileOrFolderObject->checkActionPermission('read')) {
            return '';
        }

        $fullIdentifier = $fileOrFolderObject->getCombinedIdentifier();
        $md5 = md5($fullIdentifier);
        $identifier = '_FILE|' . $md5;

        return '
            <span class="form-check form-toggle">
                <input class="form-check-input t3js-multi-record-selection-check" type="checkbox" name="CBC[' . $identifier . ']" value="' . htmlspecialchars($fullIdentifier) . '"/>
            </span>';
    }

    /**
     * Creates the edit control section
     *
     * @param File|Folder $fileOrFolderObject Array with information about the file/directory for which to make the edit control section for the listing.
     */
    public function makeEdit($fileOrFolderObject): string
    {
        $cells = [];
        $fullIdentifier = $fileOrFolderObject->getCombinedIdentifier();

        // Edit file content (if editable)
        if ($fileOrFolderObject instanceof File && $fileOrFolderObject->checkActionPermission('write') && $fileOrFolderObject->isTextFile()) {
            $attributes = [
                'href' => $this->createEditContentUriForResource($fileOrFolderObject),
                'title' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editcontent'),
            ];
            $cells['edit'] = '<a class="btn btn-default" ' . GeneralUtility::implodeAttributes($attributes, true) . '>'
                . $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render()
                . '</a>';
        } else {
            $cells['edit'] = $this->spaceIcon;
        }

        // Edit metadata of file
        if ($fileOrFolderObject instanceof File
            && $this->isEditMetadataAllowed($fileOrFolderObject)
            && $fileOrFolderObject->getMetaData()->offsetGet('uid')
        ) {
            $url = $this->createEditDataUriForResource($fileOrFolderObject);
            $title = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editMetadata'));
            $cells['metadata'] = '<a class="btn btn-default" href="' . htmlspecialchars($url) . '" title="' . $title . '">' . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>';
        }

        // Get translation actions
        if ($fileOrFolderObject instanceof File && ($translations = $this->makeTranslations($fileOrFolderObject))) {
            $cells['translations'] = $translations;
        }

        // document view
        if ($fileOrFolderObject instanceof File) {
            $fileUrl = $fileOrFolderObject->getPublicUrl();
            if ($fileUrl) {
                $cells['view'] = '<a href="' . htmlspecialchars($fileUrl) . '" target="_blank" class="btn btn-default" title="' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.view') . '">' . $this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL)->render() . '</a>';
            } else {
                $cells['view'] = $this->spaceIcon;
            }
        } else {
            $cells['view'] = $this->spaceIcon;
        }

        // replace file
        if ($fileOrFolderObject instanceof File && $fileOrFolderObject->checkActionPermission('replace')) {
            $attributes = [
                'href' => $this->createReplaceUriForResource($fileOrFolderObject),
                'title' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.replace'),
            ];
            $cells['replace'] = '<a class="btn btn-default" ' . GeneralUtility::implodeAttributes($attributes, true) . '>' . $this->iconFactory->getIcon('actions-edit-replace', Icon::SIZE_SMALL)->render() . '</a>';
        }

        // rename the file
        if ($fileOrFolderObject->checkActionPermission('rename')) {
            $attributes = [
                'href' => $this->createRenameUriForResource($fileOrFolderObject),
                'title' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.rename'),
            ];
            $cells['rename'] = '<a class="btn btn-default" ' . GeneralUtility::implodeAttributes($attributes, true) . '>' . $this->iconFactory->getIcon('actions-edit-rename', Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $cells['rename'] = $this->spaceIcon;
        }

        // file download
        if ($fileOrFolderObject->checkActionPermission('read') && $this->fileDownloadEnabled()) {
            if ($fileOrFolderObject instanceof File
                && ($this->fileExtensionFilter === null || $this->fileExtensionFilter->isAllowed($fileOrFolderObject->getExtension()))
            ) {
                $fileUrl = $fileOrFolderObject->getPublicUrl();
                if ($fileUrl) {
                    $cells['download'] = '<a href="' . htmlspecialchars($fileUrl) . '" download="' . htmlspecialchars($fileOrFolderObject->getName()) . '" class="btn btn-default" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:download')) . '">' . $this->iconFactory->getIcon('actions-download', Icon::SIZE_SMALL)->render() . '</a>';
                }
            // Folder download
            } elseif ($fileOrFolderObject instanceof Folder) {
                $cells['download'] = '<button type="button" data-folder-download="' . htmlspecialchars($this->uriBuilder->buildUriFromRoute('file_download')) . '" data-folder-identifier="' . htmlspecialchars($fileOrFolderObject->getCombinedIdentifier()) . '" class="btn btn-default" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:download')) . '">' . $this->iconFactory->getIcon('actions-download', Icon::SIZE_SMALL)->render() . '</button>';
            }
        }

        // upload files
        if ($fileOrFolderObject->getStorage()->checkUserActionPermission('add', 'File') && $fileOrFolderObject->checkActionPermission('write')) {
            if ($fileOrFolderObject instanceof Folder) {
                $attributes = [
                    'href' => (string)$this->uriBuilder->buildUriFromRoute('file_upload', ['target' => $fullIdentifier, 'returnUrl' => $this->createModuleUri()]),
                    'title' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.upload'),
                ];
                $cells['upload'] = '<a class="btn btn-default" ' . GeneralUtility::implodeAttributes($attributes, true) . '>' . $this->iconFactory->getIcon('actions-edit-upload', Icon::SIZE_SMALL)->render() . '</a>';
            }
        }

        if ($fileOrFolderObject->checkActionPermission('read')) {
            $attributes = [
                'title' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.info'),
                'data-filelist-show-item-type' => $fileOrFolderObject instanceof File ? '_FILE' : '_FOLDER',
                'data-filelist-show-item-identifier' => $fullIdentifier,
            ];
            $cells['info'] = '<a href="#" class="btn btn-default" ' . GeneralUtility::implodeAttributes($attributes, true) . '>'
                . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $cells['info'] = $this->spaceIcon;
        }

        // delete the file
        if ($fileOrFolderObject->checkActionPermission('delete')) {
            $recordInfo = $fileOrFolderObject->getName();

            if ($fileOrFolderObject instanceof Folder) {
                $identifier = $fileOrFolderObject->getIdentifier();
                $referenceCountText = BackendUtility::referenceCount('_FILE', $identifier, LF . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToFolder'));
                $deleteType = 'delete_folder';
                if ($this->getBackendUser()->shallDisplayDebugInformation()) {
                    $recordInfo .= ' [' . $identifier . ']';
                }
            } else {
                $referenceCountText = BackendUtility::referenceCount('sys_file', (string)$fileOrFolderObject->getUid(), LF . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToFile'));
                $deleteType = 'delete_file';
                if ($this->getBackendUser()->shallDisplayDebugInformation()) {
                    $recordInfo .= ' [sys_file:' . $fileOrFolderObject->getUid() . ']';
                }
            }

            if ($this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE)) {
                $confirmationCheck = '1';
            } else {
                $confirmationCheck = '0';
            }

            $deleteUrl = (string)$this->uriBuilder->buildUriFromRoute('tce_file');
            $confirmationMessage = sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.delete'), trim($recordInfo)) . $referenceCountText;
            $title = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete');
            $cells['delete'] = '<a href="#" class="btn btn-default t3js-filelist-delete" data-bs-content="' . htmlspecialchars($confirmationMessage)
                . '" data-check="' . $confirmationCheck
                . '" data-delete-url="' . htmlspecialchars($deleteUrl)
                . '" data-title="' . htmlspecialchars($title)
                . '" data-identifier="' . htmlspecialchars($fileOrFolderObject->getCombinedIdentifier())
                . '" data-delete-type="' . $deleteType
                . '" title="' . htmlspecialchars($title) . '">'
                . $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $cells['delete'] = $this->spaceIcon;
        }

        // Get clipboard actions
        $clipboardActions = $this->makeClip($fileOrFolderObject);
        if ($clipboardActions !== []) {
            // Add divider in case at least one clipboard action is displayed
            $cells['divider'] = '<hr class="dropdown-divider">';
        }
        // Merge the clipboard actions into the existing cells
        $cells = array_merge($cells, $clipboardActions);

        $event = new ProcessFileListActionsEvent($fileOrFolderObject, $cells);
        $event = $this->eventDispatcher->dispatch($event);
        $cells = $event->getActionItems();

        // Compile items into a dropdown
        $cellOutput = '';
        $output = '';
        $primaryActions = ['view', 'metadata', 'translations', 'delete'];
        $userTsConfig = $this->getBackendUser()->getTSConfig();
        if ($userTsConfig['options.']['file_list.']['primaryActions'] ?? false) {
            $primaryActions = GeneralUtility::trimExplode(',', $userTsConfig['options.']['file_list.']['primaryActions']);

            // Always add "translations" as this action has an own dropdown container and therefore cannot be a secondary action
            if (!in_array('translations', $primaryActions, true)) {
                $primaryActions[] = 'translations';
            }
        }
        foreach ($cells as $key => $action) {
            if (in_array($key, $primaryActions, true)) {
                $output .= $action;
                continue;
            }
            if ($action === $this->spaceIcon || $action === null) {
                continue;
            }
            // This is a backwards-compat layer for the existing hook items, which will be removed in TYPO3 v12.
            $action = str_replace('btn btn-default', 'dropdown-item dropdown-item-spaced', $action);
            $title = [];
            preg_match('/title="([^"]*)"/', $action, $title);
            if (empty($title)) {
                preg_match('/aria-label="([^"]*)"/', $action, $title);
            }
            if (!empty($title[1])) {
                $action = str_replace(
                    [
                        '</a>',
                        '</button>', ],
                    [
                        ' ' . $title[1] . '</a>',
                        ' ' . $title[1] . '</button>',
                    ],
                    $action
                );
                // In case we added the title as tag content, we can remove the attribute,
                // since this is duplicated and would trigger a tooltip with the same content.
                if (!empty($title[0])) {
                    $action = str_replace($title[0], '', $action);
                }
                $cellOutput .= '<li>' . $action . '</li>';
            }
        }

        if ($cellOutput !== '') {
            $icon = $this->iconFactory->getIcon('actions-menu-alternative', Icon::SIZE_SMALL);
            $title = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.more');
            $output .= '<div class="btn-group dropdown position-static" title="' . htmlspecialchars($title) . '" >' .
                '<a href="#actions_' . $fileOrFolderObject->getHashedIdentifier() . '" class="btn btn-default dropdown-toggle dropdown-toggle-no-chevron" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false">' . $icon->render() . '</a>' .
                '<ul id="actions_' . $fileOrFolderObject->getHashedIdentifier() . '" class="dropdown-menu">' . $cellOutput . '</ul>' .
                '</div>';
        } else {
            $output .= $this->spaceIcon;
        }

        return '<div class="btn-group position-static">' . $output . '</div>';
    }

    /**
     * Make reference count. Wraps the count into a button to
     * open the element information in case references exists.
     */
    public function makeRef(File $file): string
    {
        $referenceCount = $this->getFileReferenceCount($file);
        if (!$referenceCount) {
            return '-';
        }

        $attributes = [
            'type' => 'button',
            'class' => 'btn btn-link p-0',
            'data-filelist-show-item-type' => '_FILE',
            'data-filelist-show-item-identifier' => $file->getCombinedIdentifier(),
            'title' => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:show_references') . ' (' . $referenceCount . ')',
        ];

        return '
            <button ' . GeneralUtility::implodeAttributes($attributes, true) . '>
                ' . $referenceCount . '
            </button>';
    }

    /**
     * Generate readable path
     */
    protected function makePath(ResourceInterface $resource): string
    {
        $folder = null;
        $method = 'getReadablePath';

        if ($resource instanceof FileInterface) {
            $folder = $resource->getParentFolder();
        } elseif ($resource instanceof FolderInterface) {
            $folder = $resource;
        }

        if ($folder === null || !is_callable([$folder, $method])) {
            return '';
        }

        return htmlspecialchars($folder->$method());
    }

    /**
     * Creates the file metadata translation dropdown. Each item links
     * to the corresponding metadata translation, while depending on
     * the current state, either a new translation can be created or
     * an existing translation can be edited.
     */
    protected function makeTranslations(File $file): string
    {
        $backendUser = $this->getBackendUser();

        // Fetch all system languages except "default (0)" and "all languages (-1)"
        $systemLanguages = array_filter(
            $this->translateTools->getSystemLanguages(),
            static fn (array $languageRecord): bool => $languageRecord['uid'] > 0 && $backendUser->checkLanguageAccess($languageRecord['uid'])
        );

        if ($systemLanguages === []
            || !($GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField'] ?? false)
            || !$file->isIndexed()
            || !$file->checkActionPermission('editMeta')
            || !$backendUser->check('tables_modify', 'sys_file_metadata')
        ) {
            // Early return in case no system languages exists or metadata
            // of this file can not be created / edited by the current user.
            return '';
        }

        $translations = [];
        $metaDataRecord = $file->getMetaData()->get();
        $existingTranslations = $this->getTranslationsForMetaData($metaDataRecord);

        foreach ($systemLanguages as $languageId => $language) {
            if (!isset($existingTranslations[$languageId]) && !($metaDataRecord['uid'] ?? false)) {
                // Skip if neither a translation nor the metadata uid exists
                continue;
            }

            if (isset($existingTranslations[$languageId])) {
                // Set options for edit action of an existing translation
                $title = sprintf($this->getLanguageService()->getLL('editMetadataForLanguage'), $language['title']);
                $actionType = 'edit';
                $url = (string)$this->uriBuilder->buildUriFromRoute(
                    'record_edit',
                    [
                        'edit' => [
                            'sys_file_metadata' => [
                                $existingTranslations[$languageId]['uid'] => 'edit',
                            ],
                        ],
                        'returnUrl' => $this->createModuleUri(),
                    ]
                );
            } else {
                // Set options for "create new" action of a new translation
                $title = sprintf($this->getLanguageService()->getLL('createMetadataForLanguage'), $language['title']);
                $actionType = 'new';
                $metaDataRecordId = (int)($metaDataRecord['uid'] ?? 0);
                $url = BackendUtility::getLinkToDataHandlerAction(
                    '&cmd[sys_file_metadata][' . $metaDataRecordId . '][localize]=' . $languageId,
                    (string)$this->uriBuilder->buildUriFromRoute(
                        'record_edit',
                        [
                            'justLocalized' => 'sys_file_metadata:' . $metaDataRecordId . ':' . $languageId,
                            'returnUrl' => $this->createModuleUri(),
                        ]
                    )
                );
            }

            $translations[] = '
                <li>
                    <a href="' . htmlspecialchars($url) . '" class="dropdown-item" title="' . htmlspecialchars($title) . '">
                        <span class="dropdown-item-columns">
                            <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                                ' . $this->iconFactory->getIcon($language['flagIcon'], Icon::SIZE_SMALL, 'overlay-' . $actionType)->render() . '
                            </span>
                            <span class="dropdown-item-column dropdown-item-column-title">
                                ' . htmlspecialchars($title) . '
                            </span>
                        </span>
                    </a>
                </li>';
        }

        return $translations !== [] ? '
            <div class="btn-group dropdown position-static" title="' . htmlspecialchars($this->getLanguageService()->getLL('translateMetadata')) . '">
                <button class="btn btn-default dropdown-toggle dropdown-toggle-no-chevron" type="button" id="translations_' . $file->getHashedIdentifier() . '" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false">
                    ' . $this->iconFactory->getIcon('actions-translate', Icon::SIZE_SMALL)->render() . '
                </button>
                <ul  class="dropdown-menu dropdown-list" aria-labelledby="translations_' . $file->getHashedIdentifier() . '">
                    ' . implode(LF, $translations) . '
                </ul>
            </div>' : '';
    }

    protected function isEditMetadataAllowed(File $file): bool
    {
        return $file->isIndexed()
            && $file->checkActionPermission('editMeta')
            && $this->getUserPermissions($file)->editMetaData;
    }

    /**
     * Get the icon for a file or folder object
     *
     * @param string $title The icon title
     * @param File|Folder $fileOrFolderObject
     * @return string The wrapped icon for the file or folder
     */
    protected function getFileOrFolderIcon(string $title, $fileOrFolderObject): string
    {
        return '
            <span title="' . htmlspecialchars($title) . '">
                ' . $this->iconFactory->getIconForResource($fileOrFolderObject, Icon::SIZE_SMALL)->render() . '
            </span>';
    }

    /**
     * Render convenience actions, such as "check all"
     *
     * @return string HTML markup for the checkbox actions
     */
    protected function renderCheckboxActions(): string
    {
        // Early return in case there are no items
        if (!$this->totalItems) {
            return '';
        }

        $lang = $this->getLanguageService();

        $dropdownItems['checkAll'] = '
            <li>
                <button type="button" class="dropdown-item disabled" data-multi-record-selection-check-action="check-all" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.checkAll')) . '">
                    <span class="dropdown-item-columns">
                        <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                            ' . $this->iconFactory->getIcon('actions-check-square', Icon::SIZE_SMALL)->render() . '
                        </span>
                        <span class="dropdown-item-column dropdown-item-column-title">
                            ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.checkAll')) . '
                        </span>
                    </span>
                </button>
            </li>';

        $dropdownItems['checkNone'] = '
            <li>
                <button type="button" class="dropdown-item disabled" data-multi-record-selection-check-action="check-none" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.uncheckAll')) . '">
                    <span class="dropdown-item-columns">
                        <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                            ' . $this->iconFactory->getIcon('actions-square', Icon::SIZE_SMALL)->render() . '
                        </span>
                        <span class="dropdown-item-column dropdown-item-column-title">
                            ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.uncheckAll')) . '
                        </span>
                    </span>
                </button>
            </li>';

        $dropdownItems['toggleSelection'] = '
            <li>
                <button type="button" class="dropdown-item" data-multi-record-selection-check-action="toggle" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.toggleSelection')) . '">
                    <span class="dropdown-item-columns">
                        <span class="dropdown-item-column dropdown-item-column-icon" aria-hidden="true">
                        ' . $this->iconFactory->getIcon('actions-document-select', Icon::SIZE_SMALL)->render() . '
                        </span>
                        <span class="dropdown-item-column dropdown-item-column-title">
                            ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.toggleSelection')) . '
                        </span>
                    </span>
                </button>
            </li>';

        return '
            <div class="btn-group dropdown position-static">
                <button type="button" class="btn btn-borderless dropdown-toggle t3js-multi-record-selection-check-actions-toggle" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false">
                    ' . $this->iconFactory->getIcon('content-special-div', Icon::SIZE_SMALL) . '
                </button>
                <ul class="dropdown-menu t3js-multi-record-selection-check-actions">
                    ' . implode(PHP_EOL, $dropdownItems) . '
                </ul>
            </div>';
    }

    /**
     * Determine the concrete table name by checking if
     * the field exists, while sys_file takes precedence.
     */
    protected function getConcreteTableName(string $fieldName): string
    {
        return ($GLOBALS['TCA']['sys_file']['columns'][$fieldName] ?? false) ? 'sys_file' : 'sys_file_metadata';
    }

    /**
     * Whether file download is enabled for the user
     */
    protected function fileDownloadEnabled(): bool
    {
        return (bool)($this->getBackendUser()->getTSConfig()['options.']['file_list.']['fileDownload.']['enabled'] ?? true);
    }

    protected function getPaginationLinkForDirection(ResourceCollectionPaginator $paginator, NavigationDirection $direction): ?PaginationLink
    {
        $currentPagination = new SimplePagination($paginator);
        $targetPage = null;
        switch ($direction) {
            case NavigationDirection::BACKWARD:
                $targetPage = $currentPagination->getPreviousPageNumber();
                break;
            case NavigationDirection::FORWARD:
                $targetPage = $currentPagination->getNextPageNumber();
                break;
        }

        return $this->getPaginationLinkForPage($paginator, $targetPage);
    }

    protected function getPaginationLinkForPage(ResourceCollectionPaginator $paginator, ?int $targetPage = null): ?PaginationLink
    {
        if ($targetPage === null) {
            return null;
        }
        if ($targetPage > $paginator->getNumberOfPages()) {
            return null;
        }
        if ($targetPage < 1) {
            return null;
        }

        $targetPaginator = $paginator->withCurrentPageNumber($targetPage);
        $targetPagination = new SimplePagination($targetPaginator);

        return new PaginationLink(
            $targetPagination->getStartRecordNumber() . '-' . $targetPagination->getEndRecordNumber(),
            $this->createModuleUri(['currentPage' => $targetPage])
        );
    }

    /**
     * Returns list URL; This is the URL of the current script with id and imagemode parameters, that's all.
     */
    public function createModuleUri(array $params = []): string
    {
        $params = array_replace_recursive([
            'currentPage' => $this->currentPage,
            'id' => $this->folderObject->getCombinedIdentifier(),
            'searchTerm' => $this->searchDemand ? $this->searchDemand->getSearchTerm() : '',
        ], $params);

        $params = array_filter($params, static function ($value) {
            return $value !== null && trim($value) !== '';
        });

        return (string)$this->uriBuilder->buildUriFromRoute('file_FilelistList', $params);
    }

    protected function createEditDataUriForResource(ResourceInterface $resource): ?string
    {
        if ($resource instanceof File
            && $this->isEditMetadataAllowed($resource)
            && ($metaDataUid = $resource->getMetaData()->offsetGet('uid'))
        ) {
            $parameter = [
                'edit' => ['sys_file_metadata' => [$metaDataUid => 'edit']],
                'returnUrl' => $this->createModuleUri(),
            ];
            return (string)$this->uriBuilder->buildUriFromRoute('record_edit', $parameter);
        }

        return null;
    }

    protected function createEditContentUriForResource(ResourceInterface $resource): ?string
    {
        if ($resource instanceof File
            && $resource->checkActionPermission('write')
            && $resource->isTextFile()
        ) {
            $parameter = [
                'target' => $resource->getCombinedIdentifier(),
                'returnUrl' => $this->createModuleUri(),
            ];
            return (string)$this->uriBuilder->buildUriFromRoute('file_edit', $parameter);
        }

        return null;
    }

    protected function createModuleUriForResource(ResourceInterface $resource): ?string
    {
        if ($resource instanceof Folder) {
            $parameter = [
                'id' => $resource->getCombinedIdentifier(),
                'searchTerm' => '',
                'currentPage' => 1,
            ];
            return (string)$this->createModuleUri($parameter);
        }

        if ($resource instanceof File) {
            return $this->createEditDataUriForResource($resource);
        }

        return null;
    }

    protected function createReplaceUriForResource(ResourceInterface $resource): ?string
    {
        if ($resource instanceof File
            && $resource->checkActionPermission('replace')
        ) {
            $parameter = [
                'target' => $resource->getCombinedIdentifier(),
                'uid' => $resource->getUid(),
                'returnUrl' => $this->createModuleUri(),
            ];
            return (string)$this->uriBuilder->buildUriFromRoute('file_replace', $parameter);
        }
        return null;
    }

    protected function createRenameUriForResource(ResourceInterface $resource): ?string
    {
        if (($resource instanceof File || $resource instanceof Folder)
            && $resource->checkActionPermission('rename')
        ) {
            $parameter = [
                'target' => $resource->getCombinedIdentifier(),
                'returnUrl' => $this->createModuleUri(),
            ];
            return (string)$this->uriBuilder->buildUriFromRoute('file_rename', $parameter);
        }

        return null;
    }

    /**
     * @return ResourceInterface[]
     */
    protected function sortResources(array $resources, string $sortField): array
    {
        usort($resources, function (ResourceInterface $resource1, ResourceInterface $resource2) use ($sortField, $resources) {
            // Folders are always priotized above files
            if ($resource1 instanceof File && $resource2 instanceof Folder) {
                return 1;
            }
            if ($resource1 instanceof Folder && $resource2 instanceof File) {
                return -1;
            }
            return strnatcasecmp(
                $this->getSortingValue($resource1, $sortField) . array_search($resource1, $resources),
                $this->getSortingValue($resource2, $sortField) . array_search($resource2, $resources)
            );
        });

        if ($this->sortRev) {
            $resources = array_reverse($resources);
        }

        return $resources;
    }

    protected function getSortingValue(ResourceInterface $resource, string $sortField): string
    {
        if ($resource instanceof File) {
            return $this->getSortingValueForFile($resource, $sortField);
        }
        if ($resource instanceof Folder) {
            return $this->getSortingValueForFolder($resource, $sortField);
        }

        return '';
    }

    protected function getSortingValueForFile(File $resource, string $sortField): string
    {
        switch ($sortField) {
            case 'fileext':
                return $resource->getExtension();
            case 'size':
                return $resource->getSize() . 's';
            case 'rw':
                return ($resource->checkActionPermission('read') ? 'R' : '')
                    . ($resource->checkActionPermission('write') ? 'W' : '');
            case '_REF_':
                return $this->getFileReferenceCount($resource) . 'ref';
            case 'tstamp':
                return $resource->getModificationTime() . 't';
            case 'crdate':
                return $resource->getCreationTime() . 'c';
            default:
                return $resource->hasProperty($sortField) ? (string)$resource->getProperty($sortField) : '';
        }
    }

    protected function getSortingValueForFolder(Folder $resource, string $sortField): string
    {
        switch ($sortField) {
            case 'size':
                try {
                    $fileCount = $resource->getFileCount();
                } catch (InsufficientFolderAccessPermissionsException $e) {
                    $fileCount = 0;
                }
                return '0' . $fileCount . 's';
            case 'rw':
                return ($resource->checkActionPermission('read') ? 'R' : '')
                    . ($resource->checkActionPermission('write') ? 'W' : '');
            case 'name':
                return $resource->getName();
            default:
                return '';
        }
    }

    /**
     * Counts how often the given file is referenced. This is done by
     * looking up the file in the "sys_refindex" table, while excluding
     * sys_file_metadata relations as these are no such references.
     */
    protected function getFileReferenceCount(File $file): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
        return (int)$queryBuilder
            ->count('*')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq(
                    'ref_table',
                    $queryBuilder->createNamedParameter('sys_file')
                ),
                $queryBuilder->expr()->eq(
                    'ref_uid',
                    $queryBuilder->createNamedParameter($file->getUid(), Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->neq(
                    'tablename',
                    $queryBuilder->createNamedParameter('sys_file_metadata')
                )
            )
            ->executeQuery()
            ->fetchOne();
    }

    protected function getUserPermissions(Folder|File $resource): UserPermissions
    {
        return new UserPermissions(
            moveResource: $resource->checkActionPermission('move'),
            editMetaData: $this->getBackendUser()->check('tables_modify', 'sys_file_metadata')
        );
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
