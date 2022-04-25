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
use TYPO3\CMS\Backend\Backend\Avatar\Avatar;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
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
use TYPO3\CMS\Filelist\Event\ProcessFileListActionsEvent;

/**
 * Class for rendering of File>Filelist (basically used in FileListController)
 * @see \TYPO3\CMS\Filelist\Controller\FileListController
 * @internal this is a concrete TYPO3 controller implementation and solely used for EXT:filelist and not part of TYPO3's Core API.
 */
class FileList
{
    /**
     * Default Max items shown
     *
     * @var int
     */
    public $iLimit = 40;

    /**
     * Thumbnails on records containing files (pictures)
     *
     * @var bool
     */
    public $thumbs = false;

    /**
     * Space icon used for alignment when no button is available
     *
     * @var string
     */
    public $spaceIcon;

    /**
     * Max length of strings
     *
     * @var int
     */
    public $fixedL = 30;

    /**
     * The field to sort by
     *
     * @var string
     */
    public $sort = '';

    /**
     * Reverse sorting flag
     *
     * @var bool
     */
    public $sortRev = true;

    /**
     * @var int
     */
    public $firstElementNumber = 0;

    /**
     * @var int
     */
    public $totalbytes = 0;

    /**
     * This could be set to the total number of items. Used by the fwd_rew_navigation...
     *
     * @var int
     */
    public $totalItems = 0;

    /**
     * Decides the columns shown. Filled with values that refers to the keys of the data-array. $this->fieldArray[0] is the title column.
     *
     * @var array
     */
    public $fieldArray = [];

    /**
     * Counter increased for each element. Used to index elements for the JavaScript-code that transfers to the clipboard
     *
     * @var int
     */
    public $counter = 0;

    /**
     * @var TranslationConfigurationProvider
     */
    public $translateTools;

    /**
     * Keys are fieldnames and values are td-css-classes to add in addElement();
     *
     * @var array
     */
    public $addElement_tdCssClass = [
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
     * @var array
     */
    public $CBnames = [];

    /**
     * @var Clipboard $clipObj
     */
    public $clipObj;

    /**
     * @var ResourceFactory
     */
    protected $resourceFactory;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * @var int
     */
    protected $id = 0;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    protected ?FileSearchDemand $searchDemand = null;
    protected ?FileExtensionFilter $fileExtensionFilter = null;

    /**
     * A runtime first-level cache to avoid unneeded calls to BackendUtility::getRecord()
     * @var array
     */
    protected array $backendUserCache = [];

    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(?ServerRequestInterface $request = null)
    {
        // Setting the maximum length of the filenames to the user's settings or minimum 30 (= $this->fixedL)
        $this->fixedL = max($this->fixedL, $this->getBackendUser()->uc['titleLen'] ?? 1);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
        $this->translateTools = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
        $this->iLimit = MathUtility::forceIntegerInRange(
            $this->getBackendUser()->getTSConfig()['options.']['file_list.']['filesPerPage'] ?? $this->iLimit,
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
     * @param int $pointer Pointer
     * @param string $sort Sorting column
     * @param bool $sortRev Sorting direction
     */
    public function start(Folder $folderObject, $pointer, $sort, $sortRev)
    {
        $this->folderObject = $folderObject;
        $this->counter = 0;
        $this->totalbytes = 0;
        $this->sort = $sort;
        $this->sortRev = $sortRev;
        $this->firstElementNumber = $pointer;
        $this->fieldArray = [
            '_SELECTOR_', 'icon', 'name', '_CONTROL_', 'record_type', 'size', 'rw', '_REF_',
        ];
    }

    public function setColumnsToRender(array $additionalFields = []): void
    {
        $this->fieldArray = array_unique(array_merge($this->fieldArray, $additionalFields));
    }

    /**
     * Returns a table with directories and files listed.
     *
     * @param FileSearchDemand|null $searchDemand
     * @return string HTML-table
     */
    public function getTable(?FileSearchDemand $searchDemand = null): string
    {
        if ($searchDemand !== null) {
            // Store given search demand
            $this->searchDemand = $searchDemand;
            // Search currently only works for files
            $folders = [];
            // Find files by the given search demand
            $files = iterator_to_array($this->folderObject->searchFiles($this->searchDemand));
            // @todo Currently files, which got deleted in the file system, are still found.
            //       Therefore we have to ask their parent folder if it still contains the file.
            $files = array_filter($files, static function (FileInterface $file): bool {
                try {
                    if ($file->getParentFolder()->hasFile($file->getName())) {
                        return true;
                    }
                } catch (ResourceDoesNotExistException $e) {
                    // Nothing to do, file does not longer exist in folder
                }
                return false;
            });

            // @todo We have to manually slice the search result, since it may
            //       contain invalid files, which were manually filtered out above.
            //       This should be fixed, so we can use the $firstResult and $maxResults
            //       properties of the search demand directly.
            $this->totalItems = count($files);
            if (trim($this->sort) !== '') {
                // Sort the files before applying the pagination
                $files = $this->sortFiles($files);
            }
            $filesNum = $this->firstElementNumber + $this->iLimit > $this->totalItems
                ? $this->totalItems - $this->firstElementNumber
                : $this->iLimit;
            $files = array_slice($files, $this->firstElementNumber, $filesNum);

            // Add special "Path" field for the search result
            array_splice($this->fieldArray, 3, 0, '_PATH_');
        } else {
            // @todo use folder methods directly when they support filters
            $storage = $this->folderObject->getStorage();
            $storage->resetFileAndFolderNameFiltersToDefault();

            // Only render the contents of a browsable storage
            if (!$this->folderObject->getStorage()->isBrowsable()) {
                return '';
            }
            try {
                $foldersCount = $storage->countFoldersInFolder($this->folderObject);
                $filesCount = $storage->countFilesInFolder($this->folderObject);
            } catch (InsufficientFolderAccessPermissionsException $e) {
                $foldersCount = 0;
                $filesCount = 0;
            }

            if ($foldersCount <= $this->firstElementNumber) {
                $foldersFrom = false;
                $foldersNum = false;
            } else {
                $foldersFrom = $this->firstElementNumber;
                if ($this->firstElementNumber + $this->iLimit > $foldersCount) {
                    $foldersNum = $foldersCount - $this->firstElementNumber;
                } else {
                    $foldersNum = $this->iLimit;
                }
            }
            if ($foldersCount >= $this->firstElementNumber + $this->iLimit) {
                $filesFrom = false;
                $filesNum  = false;
            } elseif ($this->firstElementNumber <= $foldersCount) {
                $filesFrom = 0;
                $filesNum  = $this->iLimit - $foldersNum;
            } else {
                $filesFrom = $this->firstElementNumber - $foldersCount;
                if ($filesFrom + $this->iLimit > $filesCount) {
                    $filesNum = $filesCount - $filesFrom;
                } else {
                    $filesNum = $this->iLimit;
                }
            }

            // Initialize files and folders
            $files = [];
            $folders = [];

            if (trim($this->sort) !== '') {
                // Note: In case sorting is requested, this can not be done by the driver because
                //       the sorting field might be a metadata field, which is not known to the
                //       driver (see #97443). This therefore requires to fetch all files and folders
                //       first. They are then sorted and sliced to respect the current pagination.
                if ($foldersNum) {
                    // Only perform find/sort/slice in case at least one folder is available
                    $folders = $this->sortFolders($storage->getFoldersInFolder($this->folderObject));
                    $folders = array_slice($folders, $foldersFrom, $foldersNum);
                }
                if ($filesNum) {
                    // Only perform find/sort/slice in case at least one file is available
                    $files = $this->sortFiles($this->folderObject->getFiles());
                    $files = array_slice($files, $filesFrom, $filesNum);
                }
            } else {
                $folders = $storage->getFoldersInFolder($this->folderObject, $foldersFrom, $foldersNum);
                $files = $this->folderObject->getFiles($filesFrom, $filesNum, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, false);
            }

            $this->totalItems = $foldersCount + $filesCount;
        }

        $iOut = '';
        // Directories are added
        $iOut .= $this->fwd_rwd_nav($this->firstElementNumber);

        $iOut .= $this->formatDirList($folders);
        // Files are added
        $iOut .= $this->formatFileList($files);

        $amountOfItemsShownOnCurrentPage = $this->firstElementNumber + $this->iLimit < $this->totalItems
            ? $this->firstElementNumber + $this->iLimit
            : -1;
        $iOut .= $this->fwd_rwd_nav($amountOfItemsShownOnCurrentPage);

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

        return '
            <div class="table-fit mb-4">
                <table class="table table-striped table-hover" id="typo3-filelist">
                    <thead>' . $this->addElement($theData, [], true) . '</thead>
                    <tbody data-multi-record-selection-row-selection="true">' . $iOut . '</tbody>
                </table>
            </div>';
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

        // Add the the table row
        return '
            <tr ' . GeneralUtility::implodeAttributes($attributes, true) . '>
                ' . implode(PHP_EOL, $cols) . '
            </tr>';
    }

    /**
     * Creates a forward/reverse button based on the status of ->eCounter, ->firstElementNumber, ->iLimit
     *
     * @return string the table-row code for the element
     */
    public function fwd_rwd_nav(int $currentItemCount): string
    {
        $code = '';
        if ($currentItemCount >= $this->firstElementNumber && $currentItemCount < $this->firstElementNumber + $this->iLimit) {
            if ($this->firstElementNumber && $currentItemCount == $this->firstElementNumber) {
                // 	Reverse
                $theData = [];
                $href = $this->listURL(['pointer' => ($currentItemCount - $this->iLimit)]);
                $theData['_SELECTOR_'] = '<a href="' . htmlspecialchars($href) . '">' . $this->iconFactory->getIcon(
                    'actions-move-up',
                    Icon::SIZE_SMALL
                )->render() . ' <i>[' . (max(0, $currentItemCount - $this->iLimit) + 1) . ' - ' . $currentItemCount . ']</i></a>';
                $code = $this->addElement($theData);
            }
            return $code;
        }
        if ($currentItemCount === $this->firstElementNumber + $this->iLimit) {
            // 	Forward
            $theData = [];
            $href = $this->listURL(['pointer' => $currentItemCount]);
            $theData['_SELECTOR_'] = '<a href="' . htmlspecialchars($href) . '">' . $this->iconFactory->getIcon(
                'actions-move-down',
                Icon::SIZE_SMALL
            )->render() . ' <i>[' . ($currentItemCount + 1) . ' - ' . $this->totalItems . ']</i></a>';
            $code = $this->addElement($theData);
        }
        return $code;
    }

    /**
     * Gets the number of files and total size of a folder
     *
     * @return string
     */
    public function getFolderInfo()
    {
        if ($this->counter == 1) {
            $fileLabel = htmlspecialchars($this->getLanguageService()->getLL('file'));
        } else {
            $fileLabel = htmlspecialchars($this->getLanguageService()->getLL('files'));
        }
        return $this->counter . ' ' . $fileLabel . ', ' . GeneralUtility::formatSize($this->totalbytes, htmlspecialchars($this->getLanguageService()->getLL('byteSizeUnits')));
    }

    /**
     * This returns tablerows for the directories in the array $items['sorting'].
     *
     * @param Folder[] $folders Folders of \TYPO3\CMS\Core\Resource\Folder
     * @return string HTML table rows.
     */
    public function formatDirList(array $folders)
    {
        $out = '';
        foreach (ListUtility::resolveSpecialFolderNames($folders) as $folderName => $folderObject) {
            $role = $folderObject->getRole();
            if ($role === FolderInterface::ROLE_PROCESSING) {
                // don't show processing-folder
                continue;
            }
            if ($role !== FolderInterface::ROLE_DEFAULT) {
                $displayName = '<strong>' . htmlspecialchars($folderName) . '</strong>';
            } else {
                $displayName = htmlspecialchars($folderName);
            }

            $isLocked = $folderObject instanceof InaccessibleFolder;
            $isWritable = $folderObject->checkActionPermission('write');

            // Initialization
            $this->counter++;

            // The icon - will be linked later on, if not locked
            $theIcon = $this->getFileOrFolderIcon($folderName, $folderObject);

            // Preparing and getting the data-array
            $theData = [];

            // Preparing table row attributes
            $attributes = [
                'data-type' => 'folder',
                'data-folder-identifier' => $folderObject->getIdentifier(),
                'data-combined-identifier' => $folderObject->getCombinedIdentifier(),
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
                            $theData[$field] = (string)BackendUtility::wrapClickMenuOnIcon($theIcon, 'sys_file', $folderObject->getCombinedIdentifier());
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
                            $theData[$field] = GeneralUtility::fixed_lgd_cs($theData[$field] ?? '', $this->fixedL);
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
     * @return string HTML
     */
    public function linkWrapDir($title, Folder $folderObject)
    {
        $href = $this->listURL(['id' => $folderObject->getCombinedIdentifier(), 'searchTerm' => '', 'pointer' => 0]);
        // Sometimes $code contains plain HTML tags. In such a case the string should not be modified!
        if ((string)$title === strip_tags($title)) {
            return '<a href="' . htmlspecialchars($href) . '" title="' . htmlspecialchars($title) . '">' . $title . '</a>';
        }
        return '<a href="' . htmlspecialchars($href) . '">' . $title . '</a>';
    }

    /**
     * Wraps filenames in links which opens the metadata editor.
     *
     * @param string $code String to be wrapped in links
     * @param File $fileObject File to be linked
     * @return string HTML
     */
    public function linkWrapFile($code, File $fileObject)
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
                    'returnUrl' => $this->listURL(),
                ];
                $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                $title = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editMetadata'));
                $code = '<a class="responsive-title" href="' . htmlspecialchars($url) . '" title="' . $title . '">' . $code . '</a>';
            }
        } catch (\Exception $e) {
            // intentional fall-through
        }
        return $code;
    }

    /**
     * Returns list URL; This is the URL of the current script with id and imagemode parameters, that's all.
     *
     * @return string URL
     */
    public function listURL(array $params = []): string
    {
        $params = array_replace_recursive([
            'pointer' => $this->firstElementNumber,
            'id' => $this->folderObject->getCombinedIdentifier(),
            'searchTerm' => $this->searchDemand ? $this->searchDemand->getSearchTerm() : '',
        ], $params);
        $params = array_filter($params);
        return (string)$this->uriBuilder->buildUriFromRoute('file_FilelistList', $params);
    }

    /**
     * This returns tablerows for the files in the array $items['sorting'].
     *
     * @param File[] $files File items
     * @return string HTML table rows.
     */
    public function formatFileList(array $files)
    {
        $out = '';
        foreach ($files as $fileObject) {
            // Initialization
            $this->counter++;
            $this->totalbytes += $fileObject->getSize();
            $ext = $fileObject->getExtension();
            $fileUid = $fileObject->getUid();
            $fileName = trim($fileObject->getName());
            // Preparing and getting the data-array
            $theData = [];
            // Preparing table row attributes
            $attributes = [
                'data-type' => 'file',
                'data-file-uid' => $fileUid,
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
                        $theData[$field] = (string)BackendUtility::wrapClickMenuOnIcon($this->getFileOrFolderIcon($fileName, $fileObject), 'sys_file', $fileObject->getCombinedIdentifier());
                        break;
                    case 'name':
                        // Edit metadata of file
                        $theData[$field] = $this->linkWrapFile(htmlspecialchars($fileName), $fileObject);

                        if ($fileObject->isMissing()) {
                            $theData[$field] .= '<span class="label label-danger label-space-left">'
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
                                'title="' . htmlspecialchars($fileName) . '" alt="" />';
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
                            $concreteTableName = $this->getConcreteTableName($field);
                            if ($field === ($GLOBALS['TCA'][$concreteTableName]['ctrl']['cruser_id'] ?? '')) {
                                // Handle cruser_id by adding the avatar along with the username
                                $theData[$field] = $this->getBackendUserInformation((int)$fileObject->getProperty($field));
                            } elseif ($field === 'storage') {
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
                                        $this->fixedL,
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
     * @return array keys are the sys_language uids, values are the $rows
     */
    protected function getTranslationsForMetaData($metaDataRecord)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->getRestrictions()->removeAll();
        $translationRecords = $queryBuilder->select('*')
            ->from('sys_file_metadata')
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA']['sys_file_metadata']['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($metaDataRecord['uid'] ?? 0, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    $GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $translations = [];
        foreach ($translationRecords as $record) {
            $translations[$record[$GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField']]] = $record;
        }
        return $translations;
    }

    /**
     * Wraps a field label for the header row into a link to the filelist with sorting commands
     *
     * @param string $fieldName The field to sort
     * @param string $label The label to be wrapped - will be determined if not given
     * @return string The constructed link - HTML
     */
    public function linkWrapSort(string $fieldName, string $label = ''): string
    {
        // Determine label if not given
        if ($label === '') {
            $lang = $this->getLanguageService();
            $concreteTableName = $this->getConcreteTableName($fieldName);
            $label = $lang->sL(BackendUtility::getItemLabel($concreteTableName, $fieldName) ?? '');
            if ($label !== '') {
                // In case global TSconfig exists we have to check if the label is overridden there
                $tsConfig = BackendUtility::getPagesTSconfig(0);
                if (!empty($tsConfig['TCEFORM.'][$concreteTableName . '.'][$fieldName . '.']['label.'][$lang->lang])) {
                    $label = $tsConfig['TCEFORM.'][$concreteTableName . '.'][$fieldName . '.']['label.'][$lang->lang];
                } elseif (!empty($tsConfig['TCEFORM.'][$concreteTableName . '.'][$fieldName . '.']['label'])) {
                    $label = $tsConfig['TCEFORM.'][$concreteTableName . '.'][$fieldName . '.']['label'];
                }
            }
        }

        $params = ['SET' => ['sort' => $fieldName], 'pointer' => 0];

        if ($this->sort === $fieldName) {
            // Check reverse sorting
            $params['SET']['reverse'] = ($this->sortRev ? '0' : '1');
            $sortArrow = $this->iconFactory->getIcon('status-status-sorting-' . ($this->sortRev ? 'desc' : 'asc'), Icon::SIZE_SMALL)->render();
        } else {
            $params['SET']['reverse'] = 0;
            $sortArrow = '';
        }
        $href = $this->listURL($params);

        return '<a href="' . htmlspecialchars($href) . '">' . htmlspecialchars($label) . ' ' . $sortArrow . '</a>';
    }

    /**
     * Creates the clipboard actions
     *
     * @param File|Folder $fileOrFolderObject Array with information about the file/directory for which to make the clipboard actions for the listing.
     * @return array clipboard actions
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
     * @return string
     */
    protected function makeCheckbox($fileOrFolderObject): string
    {
        if (!$fileOrFolderObject->checkActionPermission('read')) {
            return '';
        }

        $fullIdentifier = $fileOrFolderObject->getCombinedIdentifier();
        $md5 = md5($fullIdentifier);
        $identifier = '_FILE|' . $md5;
        $this->CBnames[] = $identifier;

        return '
            <span class="form-check form-toggle">
                <input class="form-check-input t3js-multi-record-selection-check" type="checkbox" name="CBC[' . $identifier . ']" value="' . htmlspecialchars($fullIdentifier) . '"/>
            </span>';
    }

    /**
     * Creates the edit control section
     *
     * @param File|Folder $fileOrFolderObject Array with information about the file/directory for which to make the edit control section for the listing.
     * @return string HTML-table
     */
    public function makeEdit($fileOrFolderObject)
    {
        $cells = [];
        $fullIdentifier = $fileOrFolderObject->getCombinedIdentifier();

        // Edit file content (if editable)
        if ($fileOrFolderObject instanceof File && $fileOrFolderObject->checkActionPermission('write') && $fileOrFolderObject->isTextFile()) {
            $attributes = [
                'href' => (string)$this->uriBuilder->buildUriFromRoute('file_edit', ['target' => $fullIdentifier, 'returnUrl' => $this->listURL()]),
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
            && ($metaDataUid = $fileOrFolderObject->getMetaData()->offsetGet('uid'))
        ) {
            $urlParameters = [
                'edit' => [
                    'sys_file_metadata' => [
                        $metaDataUid => 'edit',
                    ],
                ],
                'returnUrl' => $this->listURL(),
            ];
            $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
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
                'href' => (string)$this->uriBuilder->buildUriFromRoute('file_replace', ['target' => $fullIdentifier, 'uid' => $fileOrFolderObject->getUid(), 'returnUrl' => $this->listURL()]),
                'title' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.replace'),
            ];
            $cells['replace'] = '<a class="btn btn-default" ' . GeneralUtility::implodeAttributes($attributes, true) . '>' . $this->iconFactory->getIcon('actions-edit-replace', Icon::SIZE_SMALL)->render() . '</a>';
        }

        // rename the file
        if ($fileOrFolderObject->checkActionPermission('rename')) {
            $attributes = [
                'href' => (string)$this->uriBuilder->buildUriFromRoute('file_rename', ['target' => $fullIdentifier, 'returnUrl' => $this->listURL()]),
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
                    'href' => (string)$this->uriBuilder->buildUriFromRoute('file_upload', ['target' => $fullIdentifier, 'returnUrl' => $this->listURL()]),
                    'title' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.upload'),
                ];
                $cells['upload'] = '<a class="btn btn-default" ' . GeneralUtility::implodeAttributes($attributes, true) . '>' . $this->iconFactory->getIcon('actions-edit-upload', Icon::SIZE_SMALL)->render() . '</a>';
            }
        }

        if ($fileOrFolderObject->checkActionPermission('read')) {
            $attributes = [
                'title' => $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.info'),
            ];
            if ($fileOrFolderObject instanceof Folder || $fileOrFolderObject instanceof File) {
                $attributes['data-filelist-show-item-type'] = $fileOrFolderObject instanceof File ? '_FILE' : '_FOLDER';
                $attributes['data-filelist-show-item-identifier'] = $fullIdentifier;
            }
            $cells['info'] = '<a href="#" class="btn btn-default" ' . GeneralUtility::implodeAttributes($attributes, true) . '>'
                . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $cells['info'] = $this->spaceIcon;
        }

        // delete the file
        if ($fileOrFolderObject->checkActionPermission('delete')) {
            $identifier = $fileOrFolderObject->getIdentifier();
            if ($fileOrFolderObject instanceof Folder) {
                $referenceCountText = BackendUtility::referenceCount('_FILE', $identifier, ' ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToFolder'));
                $deleteType = 'delete_folder';
            } else {
                $referenceCountText = BackendUtility::referenceCount('sys_file', (string)$fileOrFolderObject->getUid(), ' ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToFile'));
                $deleteType = 'delete_file';
            }

            if ($this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE)) {
                $confirmationCheck = '1';
            } else {
                $confirmationCheck = '0';
            }

            $deleteUrl = (string)$this->uriBuilder->buildUriFromRoute('tce_file');
            $confirmationMessage = sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.delete'), $fileOrFolderObject->getName()) . $referenceCountText;
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

        // Hook for manipulating edit icons.
        // @deprecated will be removed in TYPO3 v12.0.
        $cells['__fileOrFolderObject'] = $fileOrFolderObject;
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook'] ?? [] as $className) {
            $hookObject = GeneralUtility::makeInstance($className);
            if (!$hookObject instanceof FileListEditIconHookInterface) {
                throw new \UnexpectedValueException(
                    $className . ' must implement interface ' . FileListEditIconHookInterface::class,
                    1235225797
                );
            }
            $hookObject->manipulateEditIcons($cells, $this);
        }
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['fileList']['editIconsHook'])) {
            trigger_error('Using the hook $TYPO3_CONF_VARS[SC_OPTIONS][fileList][editIconsHook] will not work in TYPO3 v12.0 anymore. Use the PSR-14 based ProcessFileListActionsEvent instead.', E_USER_DEPRECATED);
        }

        unset($cells['__fileOrFolderObject']);

        // Get clipboard actions
        $clipboardActions = $this->makeClip($fileOrFolderObject);
        if ($clipboardActions !== []) {
            // Add divider in case at least one clipboard action is displayed
            $cells['divider'] = '<hr class="dropdown-divider me-0 ms-0 border-white">';
        }
        // Merge the clipboard actions into the existing cells
        $cells = array_merge($cells, $clipboardActions);

        $event = new ProcessFileListActionsEvent($fileOrFolderObject, $cells);
        $event = $this->eventDispatcher->dispatch($event);
        $cells = $event->getActionItems();

        // Compile items into a dropdown
        $cellOutput = '';
        $output = '';
        foreach ($cells as $key => $action) {
            if (in_array($key, ['view', 'metadata', 'translations', 'delete'])) {
                $output .= $action;
                continue;
            }
            if ($action === $this->spaceIcon || $action === null) {
                continue;
            }
            // This is a backwards-compat layer for the existing hook items, which will be removed in TYPO3 v12.
            $action = str_replace('btn btn-default', 'dropdown-item', $action);
            $title = [];
            preg_match('/title="([^"]*)"/', $action, $title);
            if (empty($title)) {
                preg_match('/aria-label="([^"]*)"/', $action, $title);
            }
            if (!empty($title[1] ?? '')) {
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
                if (!empty($title[0] ?? '')) {
                    $action = str_replace($title[0], '', $action);
                }
            }
            $cellOutput .= '<li>' . $action . '</li>';
        }

        if ($cellOutput !== '') {
            $icon = $this->iconFactory->getIcon('actions-menu-alternative', Icon::SIZE_SMALL);
            $title = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.more');
            $output .= '<div class="btn-group dropdown position-static" data-bs-toggle="tooltip" title="' . htmlspecialchars($title) . '" >' .
                '<a href="#actions_' . $fileOrFolderObject->getHashedIdentifier() . '" class="btn btn-default dropdown-toggle dropdown-toggle-no-chevron" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false">' . $icon->render() . '</a>' .
                '<ul id="actions_' . $fileOrFolderObject->getHashedIdentifier() . '" class="dropdown-menu dropdown-list">' . $cellOutput . '</ul>' .
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
            <button ' . GeneralUtility::implodeAttributes($attributes, true) . '">
                ' . $referenceCount . '
            </button>';
    }

    /**
     * Generate readable path
     *
     * @param ResourceInterface $resource
     * @return string
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
     *
     * @param File $file
     * @return string
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
                        'returnUrl' => $this->listURL(),
                    ]
                );
            } else {
                // Set options for "create new" action of a new translation
                $title = sprintf($this->getLanguageService()->getLL('createMetadataForLanguage'), $language['title']);
                $actionType = 'new';
                $url = BackendUtility::getLinkToDataHandlerAction(
                    '&cmd[sys_file_metadata][' . $metaDataRecord['uid'] . '][localize]=' . $languageId,
                    (string)$this->uriBuilder->buildUriFromRoute(
                        'record_edit',
                        [
                            'justLocalized' => 'sys_file_metadata:' . $metaDataRecord['uid'] . ':' . $languageId,
                            'returnUrl' => $this->listURL(),
                        ]
                    )
                );
            }

            $translations[] = '
                <li>
                    <a href="' . htmlspecialchars($url) . '" class="dropdown-item" title="' . htmlspecialchars($title) . '">
                        ' . $this->iconFactory->getIcon($language['flagIcon'], Icon::SIZE_SMALL, 'overlay-' . $actionType)->render() . ' ' . htmlspecialchars($title) . '
                    </a>
                </li>';
        }

        return $translations !== [] ? '
            <div class="btn-group dropdown position-static" data-bs-toggle="tooltip" title="' . htmlspecialchars($this->getLanguageService()->getLL('translateMetadata')) . '">
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
            && $this->getBackendUser()->check('tables_modify', 'sys_file_metadata');
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
                <button type="button" class="btn btn-link dropdown-item disabled" data-multi-record-selection-check-action="check-all" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.checkAll')) . '">
                    ' . $this->iconFactory->getIcon('actions-check-square', Icon::SIZE_SMALL)->render() . ' ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.checkAll')) . '
                </button>
            </li>';

        $dropdownItems['checkNone'] = '
            <li>
                <button type="button" class="btn btn-link dropdown-item disabled" data-multi-record-selection-check-action="check-none" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.uncheckAll')) . '">
                    ' . $this->iconFactory->getIcon('actions-square', Icon::SIZE_SMALL)->render() . ' ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.uncheckAll')) . '
                </button>
            </li>';

        $dropdownItems['toggleSelection'] = '
            <li>
                <button type="button" class="btn btn-link dropdown-item" data-multi-record-selection-check-action="toggle" title="' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.toggleSelection')) . '">
                    ' . $this->iconFactory->getIcon('actions-document-select', Icon::SIZE_SMALL)->render() . ' ' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.toggleSelection')) . '
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
     * Helper method around fetching a "cruser_id" information for a record, with a cache, so the same information
     * does not have to be processed for the same user over and over again.
     */
    protected function getBackendUserInformation(int $backendUserId): string
    {
        if (!isset($this->backendUserCache[$backendUserId])) {
            $beUserRecord = BackendUtility::getRecord('be_users', $backendUserId);
            if (is_array($beUserRecord)) {
                $avatar = GeneralUtility::makeInstance(Avatar::class);
                $label = htmlspecialchars(BackendUtility::getRecordTitle('be_users', $beUserRecord));
                $content = $avatar->render($beUserRecord) . '<strong>' . $label . '</strong>';
            } else {
                $content = '<strong>&ndash;</strong>';
            }
            $this->backendUserCache[$backendUserId] = $content;
        }
        return $this->backendUserCache[$backendUserId];
    }

    /**
     * Determine the concrete table name by checking if
     * the field exists, while sys_file takes precedence.
     *
     * @param string $fieldName
     * @return string
     */
    protected function getConcreteTableName(string $fieldName): string
    {
        return ($GLOBALS['TCA']['sys_file']['columns'][$fieldName] ?? false) ? 'sys_file' : 'sys_file_metadata';
    }

    /**
     * Whether file download is enabled for the user
     *
     * @return bool
     */
    protected function fileDownloadEnabled(): bool
    {
        return (bool)($this->getBackendUser()->getTSConfig()['options.']['file_list.']['fileDownload.']['enabled'] ?? true);
    }

    /**
     * @param File[] $files
     * @return File[]
     */
    protected function sortFiles(array $files): array
    {
        $sortedFiles = [];
        foreach ($files as $file) {
            switch ($this->sort) {
                case 'fileext':
                    $sortingKey = $file->getExtension();
                    break;
                case 'size':
                    $sortingKey = '0' . $file->getSize() . 's';
                    break;
                case 'rw':
                    $sortingKey = ($file->checkActionPermission('read') ? 'R' : '')
                        . ($file->checkActionPermission('write') ? 'W' : '');
                    break;
                case '_REF_':
                    $sortingKey = '0' . $this->getFileReferenceCount($file) . 'ref';
                    break;
                case 'tstamp':
                    $sortingKey = $file->getModificationTime() . 't';
                    break;
                case 'crdate':
                    $sortingKey = $file->getCreationTime() . 'c';
                    break;
                default:
                    $sortingKey = $file->hasProperty($this->sort) ? (string)$file->getProperty($this->sort) : '';
            }
            $i = 0;
            while (isset($sortedFiles[$sortingKey . $i])) {
                $i++;
            }
            $sortedFiles[$sortingKey . $i] = $file;
        }
        uksort($sortedFiles, 'strnatcasecmp');

        if ($this->sortRev) {
            $sortedFiles = array_reverse($sortedFiles);
        }

        return $sortedFiles;
    }

    /**
     * @param Folder[] $folders
     * @return Folder[]
     */
    protected function sortFolders(array $folders): array
    {
        if (!in_array($this->sort, ['size', 'rw', 'name'], true)) {
            // Early return in case the sorting field is not supported on folders.
            return $folders;
        }

        $sortedFolders = [];
        foreach ($folders as $folder) {
            switch ($this->sort) {
                case 'size':
                    try {
                        $fileCount = $folder->getFileCount();
                    } catch (InsufficientFolderAccessPermissionsException $e) {
                        $fileCount = 0;
                    }
                    $sortingKey = '0' . $fileCount . 's';
                    break;
                case 'rw':
                    $sortingKey = ($folder->checkActionPermission('read') ? 'R' : '')
                        . ($folder->checkActionPermission('write') ? 'W' : '');
                    break;
                case 'name':
                    $sortingKey = $folder->getName();
                    break;
                default:
                    $sortingKey = '';
            }
            $i = 0;
            while (isset($sortedFolders[$sortingKey . $i])) {
                $i++;
            }
            $sortedFolders[$sortingKey . $i] = $folder;
        }
        uksort($sortedFolders, 'strnatcasecmp');

        if ($this->sortRev) {
            $sortedFolders = array_reverse($sortedFolders);
        }

        return $sortedFolders;
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
                    $queryBuilder->createNamedParameter($file->getUid(), \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->neq(
                    'tablename',
                    $queryBuilder->createNamedParameter('sys_file_metadata')
                )
            )
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
