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
use TYPO3\CMS\Backend\ElementBrowser\Event\IsFileSelectableEvent;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\Buttons\ButtonInterface;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItem;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDownButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\GenericButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\InputButton;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Pagination\SimplePagination;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Filelist\Dto\PaginationLink;
use TYPO3\CMS\Filelist\Dto\ResourceCollection;
use TYPO3\CMS\Filelist\Dto\ResourceView;
use TYPO3\CMS\Filelist\Dto\UserPermissions;
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;
use TYPO3\CMS\Filelist\Matcher\Matcher;
use TYPO3\CMS\Filelist\Matcher\ResourceFileExtensionMatcher;
use TYPO3\CMS\Filelist\Matcher\ResourceFolderTypeMatcher;
use TYPO3\CMS\Filelist\Pagination\ResourceCollectionPaginator;
use TYPO3\CMS\Filelist\Type\Mode;
use TYPO3\CMS\Filelist\Type\NavigationDirection;
use TYPO3\CMS\Filelist\Type\ViewMode;

/**
 * Class for rendering of File>Filelist (basically used in FileListController)
 * @see \TYPO3\CMS\Filelist\Controller\FileListController
 * @internal this is a concrete TYPO3 controller implementation and solely used for EXT:filelist and not part of TYPO3's Core API.
 */
class FileList
{
    public Mode $mode = Mode::MANAGE;
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
        '_SELECTOR_' => 'col-checkbox',
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

    // Evaluates if a resource can be downloaded
    protected ?Matcher $resourceDownloadMatcher = null;
    // Evaluates if a resource can be displayed
    protected ?Matcher $resourceDisplayMatcher = null;
    // Evaluates if a resource can be selected
    protected ?Matcher $resourceSelectableMatcher = null;
    // Evaluates if a resource is currently selected
    protected ?Matcher $resourceSelectedMatcher = null;

    protected ?FileSearchDemand $searchDemand = null;
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
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);

        // Initialize Resource Download
        $this->resourceDownloadMatcher = GeneralUtility::makeInstance(Matcher::class);
        $this->resourceDownloadMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFolderTypeMatcher::class));

        // Create filter for file extensions
        $fileExtensionMatcher = GeneralUtility::makeInstance(ResourceFileExtensionMatcher::class);
        $fileDownloadConfiguration = (array)($this->getBackendUser()->getTSConfig()['options.']['file_list.']['fileDownload.'] ?? []);
        if ($fileDownloadConfiguration !== []) {
            $allowedExtensions = GeneralUtility::trimExplode(',', (string)($fileDownloadConfiguration['allowedFileExtensions'] ?? ''), true);
            $disallowedExtensions = GeneralUtility::trimExplode(',', (string)($fileDownloadConfiguration['disallowedFileExtensions'] ?? ''), true);
            $fileExtensionMatcher = GeneralUtility::makeInstance(ResourceFileExtensionMatcher::class);
            $fileExtensionMatcher->setExtensions($allowedExtensions);
            $fileExtensionMatcher->setIgnoredExtensions($disallowedExtensions);
        } else {
            $fileExtensionMatcher->addExtension('*');
        }
        $this->resourceDownloadMatcher->addMatcher($fileExtensionMatcher);
    }

    public function setResourceDownloadMatcher(?Matcher $matcher): self
    {
        $this->resourceDownloadMatcher = $matcher;
        return $this;
    }

    public function setResourceDisplayMatcher(?Matcher $matcher): self
    {
        $this->resourceDisplayMatcher = $matcher;
        return $this;
    }

    public function setResourceSelectableMatcher(?Matcher $matcher): self
    {
        $this->resourceSelectableMatcher = $matcher;
        return $this;
    }

    public function setResourceSelectedMatcher(?Matcher $matcher): self
    {
        $this->resourceSelectedMatcher = $matcher;
        return $this;
    }

    /**
     * Initialization of class
     *
     * @param Folder $folderObject The folder to work on
     * @param int $currentPage The current page to render
     * @param string $sort Sorting column
     * @param bool $sortRev Sorting direction
     * @param Mode $mode Mode of the file list
     */
    public function start(Folder $folderObject, int $currentPage, string $sort, bool $sortRev, Mode $mode = Mode::MANAGE)
    {
        $this->folderObject = $folderObject;
        $this->currentPage = MathUtility::forceIntegerInRange($currentPage, 1, 100000);
        $this->sort = $sort;
        $this->sortRev = $sortRev;
        $this->totalbytes = 0;
        $this->resourceDownloadMatcher = null;
        $this->resourceDisplayMatcher = null;
        $this->resourceSelectableMatcher = null;
        $this->setMode($mode);
    }

    public function setMode(Mode $mode)
    {
        $this->mode = $mode;
        $this->fieldArray = $mode->fieldArray();
    }

    public function setColumnsToRender(array $additionalFields = []): void
    {
        $this->fieldArray = array_unique(array_merge($this->fieldArray, $additionalFields));
    }

    /**
     * @param ResourceView[] $resourceViews
     */
    protected function renderTiles(ResourceCollectionPaginator $paginator, array $resourceViews, ViewInterface $view): string
    {
        $view->assign('displayThumbs', $this->thumbs);
        $view->assign('displayCheckbox', $this->resourceSelectableMatcher ? true : false);
        $view->assign('pagination', [
            'backward' => $this->getPaginationLinkForDirection($paginator, NavigationDirection::BACKWARD),
            'forward' => $this->getPaginationLinkForDirection($paginator, NavigationDirection::FORWARD),
        ]);
        $view->assign('resources', $resourceViews);

        return $view->render('Filelist/Tiles');
    }

    /**
     * @param ResourceView[] $resourceViews
     */
    protected function renderList(ResourceCollectionPaginator $paginator, array $resourceViews, ViewInterface $view): string
    {
        $view->assign('tableHeader', $this->renderListTableHeader());
        $view->assign('tableBackwardNavigation', $this->renderListTableForwardBackwardNavigation($paginator, NavigationDirection::BACKWARD));
        $view->assign('tableBody', $this->renderListTableBody($resourceViews));
        $view->assign('tableForwardNavigation', $this->renderListTableForwardBackwardNavigation($paginator, NavigationDirection::FORWARD));

        return $view->render('Filelist/List');
    }

    public function render(?FileSearchDemand $searchDemand, ViewInterface $view): string
    {
        $storage = $this->folderObject->getStorage();
        $storage->resetFileAndFolderNameFiltersToDefault();
        if (!$this->folderObject->getStorage()->isBrowsable()) {
            return '';
        }

        if ($searchDemand !== null) {
            if ($searchDemand->getSearchTerm() && $searchDemand->getSearchTerm() !== '') {
                $folders = [];
                // Add special "Path" field for the search result
                array_splice($this->fieldArray, 3, 0, '_PATH_');
            } else {
                $folders = $storage->getFoldersInFolder($this->folderObject);
            }
            $files = iterator_to_array($this->folderObject->searchFiles($searchDemand));
        } else {
            $folders = $storage->getFoldersInFolder($this->folderObject);
            $files = $this->folderObject->getFiles();
        }

        // Cleanup field array
        $this->fieldArray = array_filter($this->fieldArray, function (string $fieldName) {
            if ($fieldName === '_SELECTOR_' && $this->resourceSelectableMatcher === null) {
                return false;
            }
            return true;
        });

        // Remove processing folders
        $folders = array_filter($folders, function (Folder $folder) {
            return $folder->getRole() !== FolderInterface::ROLE_PROCESSING;
        });

        // Apply filter
        $resources = array_filter($folders + $files, function (ResourceInterface $resource) {
            return $this->resourceDisplayMatcher === null || $this->resourceDisplayMatcher->match($resource);
        });

        $resourceCollection = new ResourceCollection($resources);
        $this->totalItems = $resourceCollection->getTotalCount();
        $this->totalbytes = $resourceCollection->getTotalBytes();

        // Sort the files before sending it to the renderer
        if (trim($this->sort) !== '') {
            $resourceCollection->setResources($this->sortResources($resourceCollection->getResources(), $this->sort));
        }

        $paginator = new ResourceCollectionPaginator($resourceCollection, $this->currentPage, $this->itemsPerPage);

        // Prepare Resources for View
        $resourceViews = [];
        $userPermissions = $this->getUserPermissions();
        foreach ($paginator->getPaginatedItems() as $resource) {
            $resourceView = new ResourceView(
                $resource,
                $userPermissions,
                $this->iconFactory->getIconForResource($resource, Icon::SIZE_SMALL)
            );
            $resourceView->moduleUri = $this->createModuleUriForResource($resource);
            $resourceView->editDataUri = $this->createEditDataUriForResource($resource);
            $resourceView->editContentUri = $this->createEditContentUriForResource($resource);
            $resourceView->replaceUri = $this->createReplaceUriForResource($resource);

            $resourceView->isDownloadable = $this->resourceDownloadMatcher !== null && $this->resourceDownloadMatcher->match($resource);
            $resourceView->isSelectable = $this->resourceSelectableMatcher !== null && $this->resourceSelectableMatcher->match($resource);
            if ($this->mode === Mode::BROWSE && $resource instanceof File) {
                $resourceView->isSelectable = $this->eventDispatcher->dispatch(new IsFileSelectableEvent($resource))->isFileSelectable();
            }
            $resourceView->isSelected = $this->resourceSelectedMatcher !== null && $this->resourceSelectedMatcher->match($resource);

            $resourceViews[] = $resourceView;
        }

        if ($this->viewMode === ViewMode::TILES) {
            return $this->renderTiles($paginator, $resourceViews, $view);
        }

        return $this->renderList($paginator, $resourceViews, $view);
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
        // Traverse field array which contains the data to present:
        foreach ($this->fieldArray as $fieldName) {
            $cellAttributes = [];
            $cellAttributes['class'] = $this->addElement_tdCssClass[$fieldName] ?? 'col-nowrap';

            // Special handling to combine icon and name column
            if ($isTableHeader && $fieldName === 'icon') {
                continue;
            }
            if ($isTableHeader && $fieldName === 'name') {
                $cellAttributes['colspan'] = 2;
            }

            $cols[] = '<' . $colType . ' ' . GeneralUtility::implodeAttributes($cellAttributes, true) . '>' . ($data[$fieldName] ?? '') . '</' . $colType . '>';
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
            $fileLabel = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:file');
        } else {
            $fileLabel = $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:files');
        }
        return $this->totalItems . ' ' . htmlspecialchars($fileLabel) . ', ' . GeneralUtility::formatSize(
            $this->totalbytes,
            htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:byteSizeUnits'))
        );
    }

    protected function renderListTableHeader(): string
    {
        $data = [];
        foreach ($this->fieldArray as $field) {
            switch ($field) {
                case 'icon':
                    $data[$field] = '';
                    break;
                case '_SELECTOR_':
                    $data[$field] = $this->renderCheckboxActions();
                    break;
                default:
                    $data[$field] = $this->renderListTableFieldHeader($field);
                    break;
            }
        }

        return $this->addElement($data, [], true);
    }

    protected function renderListTableFieldHeader(string $field): string
    {
        $label = $this->getFieldLabel($field);
        if (in_array($field, ['_SELECTOR_', '_CONTROL_', '_PATH_'])) {
            return $label;
        }

        $params = ['sort' => $field, 'currentPage' => 0];
        if ($this->sort === $field) {
            // Check reverse sorting
            $params['reverse'] = ($this->sortRev ? '0' : '1');
        } else {
            $params['reverse'] = 0;
        }

        $icon = $this->sort === $field
            ? $this->iconFactory->getIcon('actions-sort-amount-' . ($this->sortRev ? 'down' : 'up'), Icon::SIZE_SMALL)->render()
            : $this->iconFactory->getIcon('actions-sort-amount', Icon::SIZE_SMALL)->render();

        $attributes = [
            'class' => 'table-sorting-button ' . ($this->sort === $field ? 'table-sorting-button-active' : ''),
            'href' => $this->createModuleUri($params),
        ];

        return '<a ' . GeneralUtility::implodeAttributes($attributes, true) . '>
            <span class="table-sorting-label">' . htmlspecialchars($label) . '</span>
            <span class="table-sorting-icon">' . $icon . '</span>
            </a>';

    }

    /**
     * @param ResourceView[] $resourceViews
     */
    protected function renderListTableBody(array $resourceViews): string
    {
        $output = '';
        foreach ($resourceViews as $resourceView) {
            $data = [];
            $attributes = [
                'class' => $resourceView->isSelected ? 'selected' : '',
                'data-filelist-element' => 'true',
                'data-filelist-type' => $resourceView->getType(),
                'data-filelist-identifier' => $resourceView->getIdentifier(),
                'data-filelist-state-identifier' => $resourceView->getStateIdentifier(),
                'data-filelist-name' => htmlspecialchars($resourceView->getName()),
                'data-filelist-thumbnail' => $resourceView->getThumbnailUri(),
                'data-filelist-uid' => $resourceView->getUid(),
                'data-filelist-meta-uid' => $resourceView->getMetaDataUid(),
                'data-filelist-selectable' => $resourceView->isSelectable ? 'true' : 'false',
                'data-filelist-selected' => $resourceView->isSelected ? 'true' : 'false',
                'data-multi-record-selection-element' => 'true',
                'draggable' => $resourceView->canMove() ? 'true' : 'false',
            ];
            foreach ($this->fieldArray as $field) {
                switch ($field) {
                    case 'icon':
                        $data[$field] = $this->renderIcon($resourceView);
                        break;
                    case 'name':
                        $data[$field] = $this->renderName($resourceView)
                            . $this->renderThumbnail($resourceView);
                        break;
                    case 'size':
                        $data[$field] = $this->renderSize($resourceView);
                        break;
                    case 'rw':
                        $data[$field] = $this->renderPermission($resourceView);
                        break;
                    case 'record_type':
                        $data[$field] = $this->renderType($resourceView);
                        break;
                    case 'crdate':
                        $data[$field] = $this->renderCreationTime($resourceView);
                        break;
                    case 'tstamp':
                        $data[$field] = $this->renderModificationTime($resourceView);
                        break;
                    case '_SELECTOR_':
                        $data[$field] = $this->renderSelector($resourceView);
                        break;
                    case '_PATH_':
                        $data[$field] = $this->renderPath($resourceView);
                        break;
                    case '_REF_':
                        $data[$field] = $this->renderReferenceCount($resourceView);
                        break;
                    case '_CONTROL_':
                        $data[$field] = $this->renderControl($resourceView);
                        break;
                    default:
                        $data[$field] = $this->renderField($resourceView, $field);
                }
            }
            $output .= $this->addElement($data, $attributes);
        }

        return $output;
    }

    protected function renderListTableForwardBackwardNavigation(
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

        $markup = [];
        $markup[] = '<tr>';
        $markup[] = '  <td colspan="' . count($this->fieldArray) . '">';
        $markup[] = '    <a href="' . htmlspecialchars($link->uri) . '">';
        $markup[] = '      ' . ($iconIdentifier !== null ? $this->iconFactory->getIcon($iconIdentifier, Icon::SIZE_SMALL)->render() : '');
        $markup[] = '      <i>[' . $link->label . ']</i>';
        $markup[] = '    </a>';
        $markup[] = '  </td>';
        $markup[] = '</tr>';

        return implode(PHP_EOL, $markup);
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
     * Render icon
     */
    protected function renderIcon(ResourceView $resourceView): string
    {
        return BackendUtility::wrapClickMenuOnIcon($resourceView->getIconSmall()->render(), 'sys_file', $resourceView->getIdentifier());
    }

    /**
     * Render name
     */
    protected function renderName(ResourceView $resourceView): string
    {
        $resourceName = htmlspecialchars($resourceView->getName());
        if ($resourceView->resource instanceof Folder
            && $resourceView->resource->getRole() !== FolderInterface::ROLE_DEFAULT) {
            $resourceName = '<strong>' . $resourceName . '</strong>';
        }

        $attributes = [];
        $attributes['title'] = $resourceView->getName();
        $attributes['type'] = 'button';
        $attributes['class'] = 'btn btn-link p-0';
        $attributes['data-filelist-action'] = 'primary';

        $output = '<button ' . GeneralUtility::implodeAttributes($attributes, true) . '>' . $resourceName . '</button>';
        if ($resourceView->isMissing()) {
            $label = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing'));
            $output = '<span class="badge badge-danger">' . $label . '</span> ' . $output;
        }

        return $output;
    }

    /**
     * Render thumbnail
     */
    protected function renderThumbnail(ResourceView $resourceView): string
    {
        if ($this->thumbs === false
            || $resourceView->getPreview() === null
            || !($resourceView->getPreview()->isImage() || $resourceView->getPreview()->isMediaFile())
        ) {
            return '';
        }

        $processedFile = $resourceView->getPreview()->process(
            ProcessedFile::CONTEXT_IMAGEPREVIEW,
            [
                'width' => (int)($this->getBackendUser()->getTSConfig()['options.']['file_list.']['thumbnail.']['width'] ?? 64),
                'height' => (int)($this->getBackendUser()->getTSConfig()['options.']['file_list.']['thumbnail.']['height'] ?? 64),
            ]
        );

        return '<br><img src="' . htmlspecialchars($processedFile->getPublicUrl() ?? '') . '" ' .
            'width="' . htmlspecialchars($processedFile->getProperty('width')) . '" ' .
            'height="' . htmlspecialchars($processedFile->getProperty('height')) . '" ' .
            'title="' . htmlspecialchars($resourceView->getName()) . '" />';
    }

    /**
     * Render type
     */
    protected function renderType(ResourceView $resourceView): string
    {
        $type = $resourceView->getType();
        $content = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:' . $type);
        if ($resourceView->resource instanceof File && $resourceView->resource->getExtension() !== '') {
            $content .= ' (' . strtoupper($resourceView->resource->getExtension()) . ')';
        }

        return htmlspecialchars($content);
    }

    /**
     * Render creation time
     */
    protected function renderCreationTime(ResourceView $resourceView): string
    {
        $timestamp = ($resourceView->resource instanceof File) ? $resourceView->getCreatedAt() : null;
        return $timestamp ? BackendUtility::datetime($timestamp) : '';
    }

    /**
     * Render modification time
     */
    protected function renderModificationTime(ResourceView $resourceView): string
    {
        $timestamp = ($resourceView->resource instanceof File) ? $resourceView->getUpdatedAt() : null;
        return $timestamp ? BackendUtility::datetime($timestamp) : '';
    }

    /**
     * Render size
     */
    protected function renderSize(ResourceView $resourceView): string
    {
        if ($resourceView->resource instanceof File) {
            return GeneralUtility::formatSize((int)$resourceView->resource->getSize(), htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:byteSizeUnits')));
        }

        if ($resourceView->resource instanceof Folder) {
            try {
                $numFiles = $resourceView->resource->getFileCount();
            } catch (InsufficientFolderAccessPermissionsException $e) {
                $numFiles = 0;
            }
            if ($numFiles === 1) {
                return $numFiles . ' ' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:file'));
            }
                return $numFiles . ' ' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:files'));
        }

        return '';
    }

    /**
     * Render resource permission
     */
    protected function renderPermission(ResourceView $resourceView): string
    {
        return '<strong class="text-danger">'
            . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:read'))
            . ($resourceView->canWrite() ? htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:write')) : '')
            . '</strong>';
    }

    /**
     * Render any resource field
     */
    protected function renderField(ResourceView $resourceView, string $field): string
    {
        if ($resourceView->resource instanceof File && $resourceView->resource->hasProperty($field)) {
            if ($field === 'storage') {
                // Fetch storage name of the current file
                $storage = GeneralUtility::makeInstance(StorageRepository::class)->findByUid((int)$resourceView->resource->getProperty($field));
                if ($storage !== null) {
                    return htmlspecialchars($storage->getName());
                }
            } else {
                return htmlspecialchars(
                    (string)BackendUtility::getProcessedValueExtra(
                        $this->getConcreteTableName($field),
                        $field,
                        $resourceView->resource->getProperty($field),
                        $this->maxTitleLength,
                        $resourceView->resource->getMetaData()->offsetGet('uid')
                    )
                );
            }
        }

        return '';
    }

    /**
     * Renders the checkbox to select a resource in the listing
     */
    protected function renderSelector(ResourceView $resourceView): string
    {
        $checkboxConfig = $resourceView->getCheckboxConfig();
        if ($checkboxConfig === null) {
            return '';
        }
        if (!$resourceView->isSelectable) {
            return '';
        }

        $attributes = [
            'class' => 'form-check-input ' . $checkboxConfig['class'],
            'type' => 'checkbox',
            'name' => $checkboxConfig['name'],
            'value' => $checkboxConfig['value'],
            'checked' => $checkboxConfig['checked'],
        ];

        return '<span class="form-check form-check-type-toggle">'
            . '<input ' . GeneralUtility::implodeAttributes($attributes, true) . ' />'
            . '</span>';
    }

    /**
     * Render resource path
     */
    protected function renderPath(ResourceView $resourceView): string
    {
        return htmlspecialchars($resourceView->getPath());
    }

    /**
     * Render reference count. Wraps the count into a button to
     * open the element information in case references exists.
     */
    protected function renderReferenceCount(ResourceView $resourceView): string
    {
        if (!$resourceView->resource instanceof File) {
            return '-';
        }

        $referenceCount = $this->getFileReferenceCount($resourceView->resource);
        if (!$referenceCount) {
            return '-';
        }

        $attributes = [
            'type' => 'button',
            'class' => 'btn btn-sm btn-link',
            'data-filelist-action' => 'show',
            'title' => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:show_references') . ' (' . $referenceCount . ')',
        ];

        return '<button ' . GeneralUtility::implodeAttributes($attributes, true) . '>' . $referenceCount . '</button>';
    }

    /**
     * Renders the control section
     */
    protected function renderControl(ResourceView $resourceView): string
    {
        if ($this->mode === Mode::MANAGE) {
            return $this->renderControlManage($resourceView);
        }
        if ($this->mode === Mode::BROWSE) {
            return $this->renderControlBrowse($resourceView);
        }

        return '';
    }

    /**
     * Creates the control section for the file list module
     */
    protected function renderControlManage(ResourceView $resourceView): string
    {
        if (!$resourceView->resource instanceof File && !$resourceView->resource instanceof Folder) {
            return '';
        }

        // primary actions
        $primaryActions =  ['view', 'metadata', 'translations', 'delete'];
        $userTsConfig = $this->getBackendUser()->getTSConfig();
        if ($userTsConfig['options.']['file_list.']['primaryActions'] ?? false) {
            $primaryActions = GeneralUtility::trimExplode(',', $userTsConfig['options.']['file_list.']['primaryActions']);
            // Always add "translations" as this action has an own dropdown container and therefore cannot be a secondary action
            if (!in_array('translations', $primaryActions, true)) {
                $primaryActions[] = 'translations';
            }
        }

        $actions = [
            'edit' => $this->createControlEditContent($resourceView),
            'metadata' => $this->createControlEditMetaData($resourceView),
            'translations' => $this->createControlTranslation($resourceView),
            'view' => $this->createControlView($resourceView),
            'replace' => $this->createControlReplace($resourceView),
            'rename' => $this->createControlRename($resourceView),
            'download' => $this->createControlDownload($resourceView),
            'upload' => $this->createControlUpload($resourceView),
            'info' => $this->createControlInfo($resourceView),
            'delete' => $this->createControlDelete($resourceView),
            'copy' => $this->createControlCopy($resourceView),
            'cut' => $this->createControlCut($resourceView),
            'paste' => $this->createControlPaste($resourceView),
        ];

        $event = new ProcessFileListActionsEvent($resourceView->resource, $actions);
        $event = $this->eventDispatcher->dispatch($event);
        $actions = $event->getActionItems();

        // Remove empty actions
        $actions = array_filter($actions, static fn ($action) => $action !== null && trim($action) !== '');

        // Compile items into a dropdown
        $cellOutput = '';
        $output = '';
        foreach ($actions as $key => $action) {
            if (in_array($key, $primaryActions, true)) {
                $output .= $action;
                continue;
            }
            // This is a backwards-compat layer for the existing hook items, which will be removed in TYPO3 v12.
            $action = str_replace('btn btn-sm btn-default', 'dropdown-item dropdown-item-spaced', $action);
            $title = [];
            preg_match('/title="([^"]*)"/', $action, $title);
            if (empty($title)) {
                preg_match('/aria-label="([^"]*)"/', $action, $title);
            }
            if (!empty($title[1])) {
                $action = str_replace(
                    [
                        '</a>',
                        '</button>',
                    ],
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
            $title = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.more');
            $output .= '<div class="btn-group dropdown" title="' . htmlspecialchars($title) . '" >'
                . '<a href="#actions_' . $resourceView->resource->getHashedIdentifier() . '" class="btn btn-sm btn-default dropdown-toggle dropdown-toggle-no-chevron" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false">'
                . $this->iconFactory->getIcon('actions-menu-alternative', Icon::SIZE_SMALL)->render()
                . '</a>'
                . '<ul id="actions_' . $resourceView->resource->getHashedIdentifier() . '" class="dropdown-menu">' . $cellOutput . '</ul>'
                . '</div>';
        }

        return '<div class="btn-group">' . $output . '</div>';
    }

    /**
     * Creates the control section for the element browser
     */
    protected function renderControlBrowse(ResourceView $resourceView): string
    {
        $fileOrFolderObject = $resourceView->resource;
        if (!$fileOrFolderObject instanceof File && !$fileOrFolderObject instanceof Folder) {
            return '';
        }

        $actions = [
            'select' => $this->createControlSelect($resourceView),
            'info' => $this->createControlInfo($resourceView),
        ];

        // Remove empty actions
        $actions = array_filter($actions, static fn ($action) => $action !== null && trim($action) !== '');
        if (empty($actions)) {
            return '';
        }

        return '<div class="btn-group">' . implode(' ', $actions) . '</div>';
    }

    protected function createControlSelect(ResourceView $resourceView): ?ButtonInterface
    {
        if (!$resourceView->isSelectable) {
            return null;
        }

        $button = GeneralUtility::makeInstance(InputButton::class);
        $button->setTitle($resourceView->getName());
        $button->setIcon($this->iconFactory->getIcon('actions-plus', Icon::SIZE_SMALL));
        $button->setDataAttributes(['filelist-action' => 'select']);

        return $button;
    }

    protected function createControlEditContent(ResourceView $resourceView): ?ButtonInterface
    {
        if (!($resourceView->resource instanceof File && $resourceView->resource->isTextFile())
            || !$resourceView->canWrite()) {
            return null;
        }

        $button = GeneralUtility::makeInstance(LinkButton::class);
        $button->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editcontent'));
        $button->setHref($resourceView->editContentUri);
        $button->setIcon($this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL));

        return $button;
    }

    protected function createControlEditMetaData(ResourceView $resourceView): ?ButtonInterface
    {
        if (!$resourceView->getMetaDataUid()) {
            return null;
        }

        $button = GeneralUtility::makeInstance(LinkButton::class);
        $button->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editMetadata'));
        $button->setHref($resourceView->editDataUri);
        $button->setIcon($this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL));

        return $button;
    }

    protected function createControlView(ResourceView $resourceView): ?ButtonInterface
    {
        if (!$resourceView->getPublicUrl()) {
            return null;
        }

        $button = GeneralUtility::makeInstance(GenericButton::class);
        $button->setTag('a');
        $button->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.view'));
        $button->setHref($resourceView->getPublicUrl());
        $button->setAttributes(['target' => '_blank']);
        $button->setIcon($this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL));

        return $button;
    }

    protected function createControlReplace(ResourceView $resourceView): ?ButtonInterface
    {
        if (!$resourceView->replaceUri) {
            return null;
        }

        $button = GeneralUtility::makeInstance(LinkButton::class);
        $button->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.replace'));
        $button->setHref($resourceView->replaceUri);
        $button->setIcon($this->iconFactory->getIcon('actions-edit-replace', Icon::SIZE_SMALL));

        return $button;
    }

    protected function createControlRename(ResourceView $resourceView): ?ButtonInterface
    {
        if (!$resourceView->canRename()) {
            return null;
        }

        $button = GeneralUtility::makeInstance(GenericButton::class);
        $button->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.rename'));
        $button->setAttributes(['type' => 'button', 'data-filelist-action' => 'rename']);
        $button->setIcon($this->iconFactory->getIcon('actions-edit-rename', Icon::SIZE_SMALL));

        return $button;
    }

    protected function createControlDownload(ResourceView $resourceView): ?ButtonInterface
    {
        if (!$resourceView->canRead() || !(bool)($this->getBackendUser()->getTSConfig()['options.']['file_list.']['fileDownload.']['enabled'] ?? true)) {
            return null;
        }

        if (!$resourceView->isDownloadable) {
            return null;
        }

        $button = GeneralUtility::makeInstance(GenericButton::class);
        $button->setLabel($this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:download'));
        $button->setAttributes([
            'type' => 'button',
            'data-filelist-action' => 'download',
            'data-filelist-action-url' => $this->uriBuilder->buildUriFromRoute('file_download'),
        ]);
        $button->setIcon($this->iconFactory->getIcon('actions-download', Icon::SIZE_SMALL));

        return $button;
    }

    protected function createControlUpload(ResourceView $resourceView): ?ButtonInterface
    {
        if (!$resourceView->resource->getStorage()->checkUserActionPermission('add', 'File')
            || !$resourceView->resource instanceof Folder
            || !$resourceView->canWrite()) {
            return null;
        }

        $button = GeneralUtility::makeInstance(LinkButton::class);
        $button->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.upload'));
        $button->setHref($this->uriBuilder->buildUriFromRoute('file_upload', ['target' => $resourceView->getIdentifier(), 'returnUrl' => $this->createModuleUri()]));
        $button->setIcon($this->iconFactory->getIcon('actions-edit-upload', Icon::SIZE_SMALL));

        return $button;
    }

    protected function createControlInfo(ResourceView $resourceView): ?ButtonInterface
    {
        if (!$resourceView->canRead()) {
            return null;
        }

        $button = GeneralUtility::makeInstance(GenericButton::class);
        $button->setLabel($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.info'));
        $button->setAttributes([
            'type' => 'button',
            'data-filelist-action' => 'show',
        ]);
        $button->setIcon($this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL));

        return $button;
    }

    protected function createControlDelete(ResourceView $resourceView): ?ButtonInterface
    {
        if (!$resourceView->canDelete()) {
            return null;
        }

        $recordInfo = $resourceView->getName();

        if ($resourceView->resource instanceof Folder) {
            $identifier = $resourceView->getIdentifier();
            $referenceCountText = BackendUtility::referenceCount('_FILE', $identifier, LF . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToFolder'));
            $deleteType = 'delete_folder';
            if ($this->getBackendUser()->shallDisplayDebugInformation()) {
                $recordInfo .= ' [' . $identifier . ']';
            }
        } else {
            $referenceCountText = BackendUtility::referenceCount('sys_file', (string)$resourceView->getUid(), LF . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToFile'));
            $deleteType = 'delete_file';
            if ($this->getBackendUser()->shallDisplayDebugInformation()) {
                $recordInfo .= ' [sys_file:' . $resourceView->getUid() . ']';
            }
        }

        $title = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete');
        $button = GeneralUtility::makeInstance(GenericButton::class);
        $button->setLabel($title);
        $button->setIcon($this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL));
        $button->setAttributes([
            'type' => 'button',
            'data-title' => $title,
            'data-bs-content' => sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.delete'), trim($recordInfo)) . $referenceCountText,
            'data-filelist-action' => 'delete',
            'data-filelist-delete' => 'true',
            'data-filelist-delete-identifier' => $resourceView->getIdentifier(),
            'data-filelist-delete-url' => $this->uriBuilder->buildUriFromRoute('tce_file'),
            'data-filelist-delete-type' => $deleteType,
            'data-filelist-delete-check' => $this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE) ? '1' : '0',
        ]);

        return $button;
    }

    /**
     * Creates the file metadata translation dropdown. Each item links
     * to the corresponding metadata translation, while depending on
     * the current state, either a new translation can be created or
     * an existing translation can be edited.
     */
    protected function createControlTranslation(ResourceView $resourceView): ?ButtonInterface
    {
        if (!$resourceView->resource instanceof File) {
            return null;
        }

        $backendUser = $this->getBackendUser();

        // Fetch all system languages except "default (0)" and "all languages (-1)"
        $systemLanguages = array_filter(
            $this->translateTools->getSystemLanguages(),
            static fn (array $languageRecord): bool => $languageRecord['uid'] > 0 && $backendUser->checkLanguageAccess($languageRecord['uid'])
        );

        if ($systemLanguages === []
            || !($GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField'] ?? false)
            || !$resourceView->resource->isIndexed()
            || !$resourceView->resource->checkActionPermission('editMeta')
            || !$backendUser->check('tables_modify', 'sys_file_metadata')
        ) {
            // Early return in case no system languages exists or metadata
            // of this file can not be created / edited by the current user.
            return null;
        }

        $dropdownItems = [];
        $metaDataRecord = $resourceView->resource->getMetaData()->get();
        $existingTranslations = $this->getTranslationsForMetaData($metaDataRecord);

        foreach ($systemLanguages as $languageId => $language) {
            if (!isset($existingTranslations[$languageId]) && !($metaDataRecord['uid'] ?? false)) {
                // Skip if neither a translation nor the metadata uid exists
                continue;
            }

            if (isset($existingTranslations[$languageId])) {
                // Set options for edit action of an existing translation
                $title = sprintf($this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:editMetadataForLanguage'), $language['title']);
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
                $title = sprintf($this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:createMetadataForLanguage'), $language['title']);
                $actionType = 'new';
                $metaDataRecordId = (int)($metaDataRecord['uid'] ?? 0);
                $url = (string)$this->uriBuilder->buildUriFromRoute(
                    'tce_db',
                    [
                        'cmd' => [
                            'sys_file_metadata' => [
                                $metaDataRecordId => [
                                    'localize' => $languageId,
                                ],
                            ],
                        ],
                        'redirect' => (string)$this->uriBuilder->buildUriFromRoute(
                            'record_edit',
                            [
                                'justLocalized' => 'sys_file_metadata:' . $metaDataRecordId . ':' . $languageId,
                                'returnUrl' => $this->createModuleUri(),
                            ]
                        ),
                    ]
                );
            }

            $dropdownItem = GeneralUtility::makeInstance(DropDownItem::class);
            $dropdownItem->setLabel($title);
            $dropdownItem->setHref($url);
            $dropdownItem->setIcon($this->iconFactory->getIcon($language['flagIcon'], Icon::SIZE_SMALL, 'overlay-' . $actionType));
            $dropdownItems[] = $dropdownItem;
        }

        if (empty($dropdownItems)) {
            return null;
        }

        $dropdownButton = GeneralUtility::makeInstance(DropDownButton::class);
        $dropdownButton->setLabel('Translations');
        $dropdownButton->setIcon($this->iconFactory->getIcon('actions-translate', Icon::SIZE_SMALL));
        foreach ($dropdownItems as $dropdownItem) {
            $dropdownButton->addItem($dropdownItem);
        }

        return $dropdownButton;
    }

    protected function createControlCopy(ResourceView $resourceView): ?ButtonInterface
    {
        if (!$resourceView->canRead() || !$resourceView->canCopy()) {
            return null;
        }

        if ($this->clipObj->current === 'normal') {
            $isSelected = $this->clipObj->isSelected('_FILE', md5($resourceView->getIdentifier()));
            $button = GeneralUtility::makeInstance(LinkButton::class);
            $button->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.' . ($isSelected === 'copy' ? 'copyrelease' : 'copy')));
            $button->setHref($this->clipObj->selUrlFile($resourceView->getIdentifier(), true, $isSelected === 'copy'));
            $button->setIcon($this->iconFactory->getIcon($isSelected === 'copy' ? 'actions-edit-copy-release' : 'actions-edit-copy', Icon::SIZE_SMALL));
            return $button;
        }

        return null;
    }

    protected function createControlCut(ResourceView $resourceView): ?ButtonInterface
    {
        if (!$resourceView->canRead() || !$resourceView->canMove()) {
            return null;
        }

        if ($this->clipObj->current === 'normal') {
            $isSelected = $this->clipObj->isSelected('_FILE', md5($resourceView->getIdentifier()));
            $button = GeneralUtility::makeInstance(LinkButton::class);
            $button->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.' . ($isSelected === 'cut' ? 'cutrelease' : 'cut')));
            $button->setHref($this->clipObj->selUrlFile($resourceView->getIdentifier(), true, $isSelected === 'cut'));
            $button->setIcon($this->iconFactory->getIcon($isSelected === 'cut' ? 'actions-edit-cut-release' : 'actions-edit-cut', Icon::SIZE_SMALL));
            $actions['cut'] = $button;

            return $button;
        }

        return null;
    }

    protected function createControlPaste(ResourceView $resourceView): ?ButtonInterface
    {
        $permission = ($this->clipObj->clipData[$this->clipObj->current]['mode'] ?? '') === 'copy' ? 'copy' : 'move';
        $addPasteButton = $this->folderObject->checkActionPermission($permission);
        $elementFromTable = $this->clipObj->elFromTable('_FILE');
        if ($elementFromTable === []
            || !$addPasteButton
            || !$resourceView->canRead()
            || !$resourceView->canWrite()
            || !$resourceView->resource instanceof Folder) {
            return null;
        }

        $elementsToConfirm = [];
        foreach ($elementFromTable as $key => $element) {
            $clipBoardElement = $this->resourceFactory->retrieveFileOrFolderObject($element);
            if ($clipBoardElement instanceof Folder
                && $clipBoardElement->getStorage()->isWithinFolder($clipBoardElement, $resourceView->resource)
            ) {
                // In case folder is already present in the target folder, return actions without paste button
                return null;
            }
            $elementsToConfirm[$key] = $clipBoardElement->getName();
        }

        $pasteTitle = $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:clip_pasteInto');
        $button = GeneralUtility::makeInstance(LinkButton::class);
        $button->setTitle($pasteTitle);
        $button->setHref($this->clipObj->pasteUrl('_FILE', $resourceView->getIdentifier()));
        $button->setDataAttributes([
            'title' => $pasteTitle,
            'bs-content' => $this->clipObj->confirmMsgText('_FILE', $resourceView->getName(), 'into', $elementsToConfirm),
        ]);
        $button->setIcon($this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL));

        return $button;
    }

    protected function isEditMetadataAllowed(File $file): bool
    {
        return $file->isIndexed()
            && $file->checkActionPermission('editMeta')
            && $this->getUserPermissions()->editMetaData;
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
                            ' . $this->iconFactory->getIcon('actions-selection-elements-all', Icon::SIZE_SMALL)->render() . '
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
                            ' . $this->iconFactory->getIcon('actions-selection-elements-none', Icon::SIZE_SMALL)->render() . '
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
                        ' . $this->iconFactory->getIcon('actions-selection-elements-invert', Icon::SIZE_SMALL)->render() . '
                        </span>
                        <span class="dropdown-item-column dropdown-item-column-title">
                            ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.toggleSelection')) . '
                        </span>
                    </span>
                </button>
            </li>';

        return '
            <div class="btn-group dropdown">
                <button type="button" class="dropdown-toggle dropdown-toggle-link t3js-multi-record-selection-check-actions-toggle" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false">
                    ' . $this->iconFactory->getIcon('actions-selection', Icon::SIZE_SMALL) . '
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

        $uri = new Uri($this->request->getAttribute('normalizedParams')->getRequestUri());
        parse_str($uri->getQuery(), $queryParameters);
        unset($queryParameters['contentOnly']);
        $queryParameters = array_merge($queryParameters, ['currentPage' => $targetPage]);
        $uri = $uri->withQuery(HttpUtility::buildQueryString($queryParameters, '&'));

        return new PaginationLink(
            $targetPagination->getStartRecordNumber() . '-' . $targetPagination->getEndRecordNumber(),
            (string)$uri,
        );
    }

    /**
     * Returns list URL; This is the URL of the current script with id and imagemode parameters, that's all.
     */
    public function createModuleUri(array $params = []): ?string
    {
        $request = $this->request;
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $route = $request->getAttribute('route');
        if (!$route instanceof Route) {
            return null;
        }

        $baseParams = [
            'currentPage' => $this->currentPage,
            'id' => $this->folderObject->getCombinedIdentifier(),
            'searchTerm' => $this->searchDemand ? $this->searchDemand->getSearchTerm() : '',
        ];

        // Keep ElementBrowser Settings
        if ($mode = $parsedBody['mode'] ?? $queryParams['mode'] ?? null) {
            $baseParams['mode'] = $mode;
        }
        if ($bparams = $parsedBody['bparams'] ?? $queryParams['bparams'] ?? null) {
            $baseParams['bparams'] = $bparams;
        }

        // Keep LinkHandler Settings
        if ($act = ($parsedBody['act'] ?? $queryParams['act'] ?? null)) {
            $baseParams['act'] = $act;
        }
        if ($linkHandlerParams = ($parsedBody['P'] ?? $queryParams['P'] ?? null)) {
            $baseParams['P'] = $linkHandlerParams;
        }

        $params = array_replace_recursive($baseParams, $params);

        // Expanded folder is used in the element browser.
        // We always map it to the id here.
        $params['expandFolder'] = $params['id'];
        $params = array_filter($params, static function ($value) {
            return (is_array($value) && $value !== []) || (trim((string)$value) !== '');
        });

        return (string)$this->uriBuilder->buildUriFromRoute($route->getOption('_identifier'), $params);
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

    /**
     * @return ResourceInterface[]
     */
    protected function sortResources(array $resources, string $sortField): array
    {
        $collator = new \Collator((string)($this->getLanguageService()->getLocale() ?? 'en'));
        uksort($resources, function (int $index1, int $index2) use ($sortField, $resources, $collator) {
            $resource1 = $resources[$index1];
            $resource2 = $resources[$index2];

            // Folders are always prioritized above files
            if ($resource1 instanceof File && $resource2 instanceof Folder) {
                return 1;
            }
            if ($resource1 instanceof Folder && $resource2 instanceof File) {
                return -1;
            }

            return (int)$collator->compare(
                $this->getSortingValue($resource1, $sortField) . $index1,
                $this->getSortingValue($resource2, $sortField) . $index2
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

    protected function getFieldLabel(string $field): string
    {
        $lang = $this->getLanguageService();

        if ($specialLabel = $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.' . $field)) {
            return $specialLabel;
        }
        if ($customLabel = $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:c_' . $field)) {
            return $customLabel;
        }

        $concreteTableName = $this->getConcreteTableName($field);
        $label = BackendUtility::getItemLabel($concreteTableName, $field);

        // In case global TSconfig exists we have to check if the label is overridden there
        $tsConfig = BackendUtility::getPagesTSconfig(0);
        $label = $lang->translateLabel(
            $tsConfig['TCEFORM.'][$concreteTableName . '.'][$field . '.']['label.'] ?? [],
            $tsConfig['TCEFORM.'][$concreteTableName . '.'][$field . '.']['label'] ?? $label
        );

        return $label;
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

    protected function getUserPermissions(): UserPermissions
    {
        return new UserPermissions($this->getBackendUser()->check('tables_modify', 'sys_file_metadata'));
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
