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

use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\InaccessibleFolder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Resource\Utility\ListUtility;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

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
        '_CLIPBOARD_' => 'col-clipboard',
        'file' => 'col-title col-responsive',
        '_LOCALIZATION_' => 'col-localizationa',
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

    public function __construct()
    {
        // Setting the maximum length of the filenames to the user's settings or minimum 30 (= $this->fixedL)
        $this->fixedL = max($this->fixedL, $this->getBackendUser()->uc['titleLen'] ?? 1);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->translateTools = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
        $this->iLimit = MathUtility::forceIntegerInRange(
            $this->getBackendUser()->getTSConfig()['options.']['file_list.']['filesPerPage'] ?? $this->iLimit,
            1
        );
        // Create clipboard object and initialize that
        $this->clipObj = GeneralUtility::makeInstance(Clipboard::class);
        $this->clipObj->fileMode = true;
        $this->clipObj->initializeClipboard();
        $this->resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $this->getLanguageService()->includeLLFile('EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf');
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_common.xlf');
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->spaceIcon = '<span class="btn btn-default disabled">' . $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';
    }

    /**
     * Initialization of class
     *
     * @param Folder $folderObject The folder to work on
     * @param int $pointer Pointer
     * @param string $sort Sorting column
     * @param bool $sortRev Sorting direction
     * @param bool $clipBoard
     */
    public function start(Folder $folderObject, $pointer, $sort, $sortRev, $clipBoard = false)
    {
        $this->folderObject = $folderObject;
        $this->counter = 0;
        $this->totalbytes = 0;
        $this->sort = $sort;
        $this->sortRev = $sortRev;
        $this->firstElementNumber = $pointer;
        // Cleaning rowlist for duplicates and place the $titleCol as the first column always!
        $rowlist = 'file,_LOCALIZATION_,_CONTROL_,fileext,tstamp,size,rw,_REF_';
        if ($clipBoard) {
            $rowlist = str_replace('_CONTROL_,', '_CONTROL_,_CLIPBOARD_,', $rowlist);
        }
        $this->fieldArray = explode(',', $rowlist);
    }

    /**
     * Wrapping input string in a link with clipboard command.
     *
     * @param string $string String to be linked - must be htmlspecialchar'ed / prepared before.
     * @param string $cmd "cmd" value
     * @param string $warning Warning for JS confirm message
     * @return string Linked string
     */
    public function linkClipboardHeaderIcon($string, $cmd, $warning = '')
    {
        if ($warning) {
            $attributes['class'] = 'btn btn-default t3js-modal-trigger';
            $attributes['data-severity'] = 'warning';
            $attributes['data-bs-content'] = $warning;
            $attributes['data-event-name'] = 'filelist:clipboard:cmd';
            $attributes['data-event-payload'] = $cmd;
        } else {
            $attributes['class'] = 'btn btn-default';
            $attributes['data-filelist-clipboard-cmd'] = $cmd;
        }

        return '<button type="button" ' . GeneralUtility::implodeAttributes($attributes, true) . '>' . $string . '</button>';
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
            $filesNum = $this->firstElementNumber + $this->iLimit > $this->totalItems
                ? $this->totalItems - $this->firstElementNumber
                : $this->iLimit;
            $files = array_slice($files, $this->firstElementNumber, $filesNum);

            // Add special "Path" field for the search result
            array_unshift($this->fieldArray, '_PATH_');
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

            $folders = $storage->getFoldersInFolder($this->folderObject, $foldersFrom, $foldersNum, true, false, trim($this->sort), (bool)$this->sortRev);
            $files = $this->folderObject->getFiles($filesFrom, $filesNum, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, false, trim($this->sort), (bool)$this->sortRev);
            $this->totalItems = $foldersCount + $filesCount;

            // Adds the code of files/dirs
            $folders = ListUtility::resolveSpecialFolderNames($folders);
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
        foreach ($this->fieldArray as $v) {
            if ($v === '_CLIPBOARD_') {
                $theData[$v] = $this->renderClipboardHeaderRow(!empty($iOut));
            } elseif ($v === '_REF_') {
                $theData[$v] = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels._REF_'));
            } elseif ($v === '_PATH_') {
                $theData[$v] = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels._PATH_'));
            } else {
                // Normal row
                $theData[$v]  = $this->linkWrapSort($v);
            }
        }

        return '
            <div class="mb-4 mt-2">
                <div class="table-fit mb-0">
                    <table class="table table-striped table-hover" id="typo3-filelist">
                        <thead>' . $this->addElement('', $theData, true) . '</thead>
                        <tbody>' . $iOut . '</tbody>
                    </table>
                </div>
            </div>';
    }

    protected function renderClipboardHeaderRow(bool $hasContent): string
    {
        $cells = [];
        $elFromTable = $this->clipObj->elFromTable('_FILE');
        if (!empty($elFromTable) && $this->folderObject->checkActionPermission('write')) {
            $clipboardMode = $this->clipObj->clipData[$this->clipObj->current]['mode'] ?? '';
            $permission = $clipboardMode === 'copy' ? 'copy' : 'move';
            $addPasteButton = $this->folderObject->checkActionPermission($permission);
            $elToConfirm = [];
            foreach ($elFromTable as $key => $element) {
                $clipBoardElement = $this->resourceFactory->retrieveFileOrFolderObject($element);
                if ($clipBoardElement instanceof Folder && $clipBoardElement->getStorage()->isWithinFolder($clipBoardElement, $this->folderObject)) {
                    $addPasteButton = false;
                }
                $elToConfirm[$key] = $clipBoardElement->getName();
            }
            if ($addPasteButton) {
                $cells[] = '<a class="btn btn-default t3js-modal-trigger"' .
                    ' href="' . htmlspecialchars($this->clipObj->pasteUrl(
                        '_FILE',
                        $this->folderObject->getCombinedIdentifier()
                    )) . '"'
                    . ' data-bs-content="' . htmlspecialchars($this->clipObj->confirmMsgText(
                        '_FILE',
                        $this->folderObject->getReadablePath(),
                        'into',
                        $elToConfirm
                    )) . '"'
                    . ' data-severity="warning"'
                    . ' data-title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_paste')) . '"'
                    . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_paste')) . '">'
                    . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)
                        ->render()
                    . '</a>';
            } else {
                $cells[] = $this->spaceIcon;
            }
        }
        if ($this->clipObj->current !== 'normal' && $hasContent) {
            $cells[] = $this->linkClipboardHeaderIcon('<span title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_selectMarked')) . '">' . $this->iconFactory->getIcon('actions-edit-copy', Icon::SIZE_SMALL)->render() . '</span>', 'setCB');
            $cells[] = $this->linkClipboardHeaderIcon('<span title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_deleteMarked')) . '">' . $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render() . '</span>', 'delete', $this->getLanguageService()->getLL('clip_deleteMarkedWarning'));
            $cells[] = '<a class="btn btn-default t3js-toggle-all-checkboxes" data-checkboxes-names="' . htmlspecialchars(implode(',', $this->CBnames)) . '" rel="" href="#" title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_markRecords')) . '">' . $this->iconFactory->getIcon('actions-document-select', Icon::SIZE_SMALL)->render() . '</a>';
        }
        if (!empty($cells)) {
            return '<div class="btn-group">' . implode('', $cells) . '</div>';
        }
        return '';
    }

    /**
     * Returns a table-row with the content from the fields in the input data array.
     * OBS: $this->fieldArray MUST be set! (represents the list of fields to display)
     *
     * @param string $icon Is the <img>+<a> of the record. If not supplied the first 'join'-icon will be a 'line' instead
     * @param array $data Is the data array, record with the fields. Notice: These fields are (currently) NOT htmlspecialchar'ed before being wrapped in <td>-tags
     * @param bool $isTableHeader Whether the element to be added is a table header
     *
     * @return string HTML content for the table row
     */
    public function addElement(string $icon, array $data, bool $isTableHeader = false): string
    {
        // Initialize additional data attributes for the row
        // Note: To be consistent with the other $data values, the additional data attributes
        // are not htmlspecialchar'ed before being added to the table row. Therefore it
        // has to be ensured they are properly escaped when applied to the $data array!
        $dataAttributes = [];
        foreach (['type', 'file-uid', 'metadata-uid', 'folder-identifier', 'combined-identifier'] as $dataAttribute) {
            if (isset($data[$dataAttribute])) {
                $dataAttributes['data-' . $dataAttribute] = $data[$dataAttribute];
                // Unset as we don't need them anymore, when building the table cells
                unset($data[$dataAttribute]);
            }
        }

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
            <tr ' . GeneralUtility::implodeAttributes($dataAttributes) . '>
                <' . $colType . ' class="col-icon nowrap">' . ($icon ?: '') . '</' . $colType . '>'
                . implode(PHP_EOL, $cols) .
            '</tr>';
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
                $theData['file'] = '<a href="' . htmlspecialchars($href) . '">' . $this->iconFactory->getIcon(
                    'actions-move-up',
                    Icon::SIZE_SMALL
                )->render() . ' <i>[' . (max(0, $currentItemCount - $this->iLimit) + 1) . ' - ' . $currentItemCount . ']</i></a>';
                $code = $this->addElement('', $theData);
            }
            return $code;
        }
        if ($currentItemCount === $this->firstElementNumber + $this->iLimit) {
            // 	Forward
            $theData = [];
            $href = $this->listURL(['pointer' => $currentItemCount]);
            $theData['file'] = '<a href="' . htmlspecialchars($href) . '">' . $this->iconFactory->getIcon(
                'actions-move-down',
                Icon::SIZE_SMALL
            )->render() . ' <i>[' . ($currentItemCount + 1) . ' - ' . $this->totalItems . ']</i></a>';
            $code = $this->addElement('', $theData);
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
        foreach ($folders as $folderName => $folderObject) {
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

            // The icon with link
            $theIcon = '<span title="' . htmlspecialchars($folderName) . '">' . $this->iconFactory->getIconForResource($folderObject, Icon::SIZE_SMALL)->render() . '</span>';
            if (!$isLocked) {
                $theIcon = (string)BackendUtility::wrapClickMenuOnIcon($theIcon, 'sys_file', $folderObject->getCombinedIdentifier());
            }

            // Preparing and getting the data-array
            $theData = [
                'type' => 'folder',
                'folder-identifier' => htmlspecialchars($folderObject->getIdentifier()),
                'combined-identifier' => htmlspecialchars($folderObject->getCombinedIdentifier()),
            ];
            if ($isLocked) {
                foreach ($this->fieldArray as $field) {
                    $theData[$field] = '';
                }
                $theData['file'] = $displayName;
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
                        case 'fileext':
                            $theData[$field] = htmlspecialchars($this->getLanguageService()->getLL('folder'));
                            break;
                        case 'tstamp':
                            $tstamp = $folderObject->getModificationTime();
                            $theData[$field] = $tstamp ? BackendUtility::date($tstamp) : '-';
                            break;
                        case 'file':
                            $theData[$field] = $this->linkWrapDir($displayName, $folderObject);
                            break;
                        case '_CONTROL_':
                            $theData[$field] = $this->makeEdit($folderObject);
                            break;
                        case '_CLIPBOARD_':
                            $theData[$field] = $this->makeClip($folderObject);
                            break;
                        case '_REF_':
                            $theData[$field] = $this->makeRef($folderObject);
                            break;
                        case '_PATH_':
                            $theData[$field] = $this->makePath($folderObject);
                            break;
                        default:
                            $theData[$field] = GeneralUtility::fixed_lgd_cs($theData[$field] ?? '', $this->fixedL);
                    }
                }
            }
            $out .= $this->addElement($theIcon, $theData);
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
        $triggerTreeUpdateAttribute = sprintf(
            ' data-tree-update-request="%s"',
            htmlspecialchars($folderObject->getCombinedIdentifier())
        );
        // Sometimes $code contains plain HTML tags. In such a case the string should not be modified!
        if ((string)$title === strip_tags($title)) {
            return '<a href="' . htmlspecialchars($href) . '"' . $triggerTreeUpdateAttribute . ' title="' . htmlspecialchars($title) . '">' . $title . '</a>';
        }
        return '<a href="' . htmlspecialchars($href) . '"' . $triggerTreeUpdateAttribute . '>' . $title . '</a>';
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
                            $metaDataUid => 'edit'
                        ]
                    ],
                    'returnUrl' => $this->listURL()
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
            'searchTerm' => $this->searchDemand ? $this->searchDemand->getSearchTerm() : ''
        ], $params);
        $params = array_filter($params);
        return (string)$this->uriBuilder->buildUriFromRoute('file_FilelistList', $params);
    }

    protected function getAvailableSystemLanguages(): array
    {
        // first two keys are "0" (default) and "-1" (multiple), after that comes the "other languages"
        $allSystemLanguages = $this->translateTools->getSystemLanguages();
        return array_filter($allSystemLanguages, function ($languageRecord) {
            if ($languageRecord['uid'] === -1 || $languageRecord['uid'] === 0 || !$this->getBackendUser()->checkLanguageAccess($languageRecord['uid'])) {
                return false;
            }
            return true;
        });
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
        $systemLanguages = $this->getAvailableSystemLanguages();
        foreach ($files as $fileObject) {
            // Initialization
            $this->counter++;
            $this->totalbytes += $fileObject->getSize();
            $ext = $fileObject->getExtension();
            $fileUid = $fileObject->getUid();
            $fileName = trim($fileObject->getName());
            // The icon with link
            $theIcon = '<span title="' . htmlspecialchars($fileName . ' [' . $fileUid . ']') . '">'
                . $this->iconFactory->getIconForResource($fileObject, Icon::SIZE_SMALL)->render() . '</span>';
            $theIcon = (string)BackendUtility::wrapClickMenuOnIcon($theIcon, 'sys_file', $fileObject->getCombinedIdentifier());
            // Preparing and getting the data-array
            $theData = [
                'type' => 'file',
                'file-uid' => $fileUid
            ];
            if ($this->isEditMetadataAllowed($fileObject)
                && ($metaDataUid = $fileObject->getMetaData()->offsetGet('uid'))
            ) {
                $theData['metadata-uid'] = htmlspecialchars((string)$metaDataUid);
            }
            foreach ($this->fieldArray as $field) {
                switch ($field) {
                    case 'size':
                        $theData[$field] = GeneralUtility::formatSize((int)$fileObject->getSize(), htmlspecialchars($this->getLanguageService()->getLL('byteSizeUnits')));
                        break;
                    case 'rw':
                        $theData[$field] = '' . (!$fileObject->checkActionPermission('read') ? ' ' : '<strong class="text-danger">' . htmlspecialchars($this->getLanguageService()->getLL('read')) . '</strong>') . (!$fileObject->checkActionPermission('write') ? '' : '<strong class="text-danger">' . htmlspecialchars($this->getLanguageService()->getLL('write')) . '</strong>');
                        break;
                    case 'fileext':
                        $theData[$field] = htmlspecialchars(strtoupper($ext));
                        break;
                    case 'tstamp':
                        $theData[$field] = BackendUtility::date($fileObject->getModificationTime());
                        break;
                    case '_CONTROL_':
                        $theData[$field] = $this->makeEdit($fileObject);
                        break;
                    case '_CLIPBOARD_':
                        $theData[$field] = $this->makeClip($fileObject);
                        break;
                    case '_LOCALIZATION_':
                        if (!empty($systemLanguages) && $fileObject->isIndexed() && $fileObject->checkActionPermission('editMeta') && $this->getBackendUser()->check('tables_modify', 'sys_file_metadata') && !empty($GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField'] ?? null)) {
                            $metaDataRecord = $fileObject->getMetaData()->get();
                            $translations = $this->getTranslationsForMetaData($metaDataRecord);
                            $languageCode = '';

                            foreach ($systemLanguages as $language) {
                                $languageId = $language['uid'];
                                $flagIcon = $language['flagIcon'];
                                if (array_key_exists($languageId, $translations)) {
                                    $title = htmlspecialchars(sprintf($this->getLanguageService()->getLL('editMetadataForLanguage'), $language['title']));
                                    $urlParameters = [
                                        'edit' => [
                                            'sys_file_metadata' => [
                                                $translations[$languageId]['uid'] => 'edit'
                                            ]
                                        ],
                                        'returnUrl' => $this->listURL()
                                    ];
                                    $flagButtonIcon = $this->iconFactory->getIcon($flagIcon, Icon::SIZE_SMALL, 'overlay-edit')->render();
                                    $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                                    $languageCode .= '<a href="' . htmlspecialchars($url) . '" class="btn btn-default" title="' . $title . '">'
                                        . $flagButtonIcon . '</a>';
                                } elseif ($metaDataRecord['uid'] ?? false) {
                                    $parameters = [
                                        'justLocalized' => 'sys_file_metadata:' . $metaDataRecord['uid'] . ':' . $languageId,
                                        'returnUrl' => $this->listURL()
                                    ];
                                    $href = BackendUtility::getLinkToDataHandlerAction(
                                        '&cmd[sys_file_metadata][' . $metaDataRecord['uid'] . '][localize]=' . $languageId,
                                        (string)$this->uriBuilder->buildUriFromRoute('record_edit', $parameters)
                                    );
                                    $flagButtonIcon = '<span title="' . htmlspecialchars(sprintf($this->getLanguageService()->getLL('createMetadataForLanguage'), $language['title'])) . '">' . $this->iconFactory->getIcon($flagIcon, Icon::SIZE_SMALL, 'overlay-new')->render() . '</span>';
                                    $languageCode .= '<a href="' . htmlspecialchars($href) . '" class="btn btn-default">' . $flagButtonIcon . '</a> ';
                                }
                            }

                            // Hide flag button bar when not translated yet
                            $theData[$field] = ' <div class="localisationData btn-group' . (empty($translations) ? ' hidden' : '') . '" data-fileid="' . $fileUid . '">'
                                . $languageCode . '</div>';
                            $theData[$field] .= '<a class="btn btn-default filelist-translationToggler" data-fileid="' . $fileUid . '">' .
                                '<span title="' . htmlspecialchars($this->getLanguageService()->getLL('translateMetadata')) . '">'
                                . $this->iconFactory->getIcon('mimetypes-x-content-page-language-overlay', Icon::SIZE_SMALL)->render() . '</span>'
                                . '</a>';
                        }
                        break;
                    case '_REF_':
                        $theData[$field] = $this->makeRef($fileObject);
                        break;
                    case '_PATH_':
                        $theData[$field] = $this->makePath($fileObject);
                        break;
                    case 'file':
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
                            $theData[$field] .= '<br /><img src="' . htmlspecialchars(PathUtility::getAbsoluteWebPath($processedFile->getPublicUrl() ?? '')) . '" ' .
                                'width="' . htmlspecialchars($processedFile->getProperty('width')) . '" ' .
                                'height="' . htmlspecialchars($processedFile->getProperty('height')) . '" ' .
                                'title="' . htmlspecialchars($fileName) . '" alt="" />';
                        }
                        break;
                    default:
                        $theData[$field] = '';
                        if ($fileObject->hasProperty($field)) {
                            $theData[$field] = htmlspecialchars(GeneralUtility::fixed_lgd_cs($fileObject->getProperty($field), $this->fixedL));
                        }
                }
            }
            $out .= $this->addElement($theIcon, $theData);
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
            ->execute()
            ->fetchAllAssociative();

        $translations = [];
        foreach ($translationRecords as $record) {
            $translations[$record[$GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField']]] = $record;
        }
        return $translations;
    }

    /**
     * Wraps the directory-titles ($code) in a link to filelist/Modules/Filelist/index.php (id=$path) and sorting commands...
     *
     * @param string $col Sorting column
     * @return string HTML
     */
    public function linkWrapSort($col)
    {
        $code = htmlspecialchars($this->getLanguageService()->getLL('c_' . $col));
        $params = ['SET' => ['sort' => $col], 'pointer' => 0];

        if ($this->sort === $col) {
            // Check reverse sorting
            $params['SET']['reverse'] = ($this->sortRev ? '0' : '1');
            $sortArrow = $this->iconFactory->getIcon('status-status-sorting-' . ($this->sortRev ? 'desc' : 'asc'), Icon::SIZE_SMALL)->render();
        } else {
            $params['SET']['reverse'] = 0;
            $sortArrow = '';
        }
        $href = $this->listURL($params);
        return '<a href="' . htmlspecialchars($href) . '">' . $code . ' ' . $sortArrow . '</a>';
    }

    /**
     * Creates the clipboard control pad
     *
     * @param File|Folder $fileOrFolderObject Array with information about the file/directory for which to make the clipboard panel for the listing.
     * @return string HTML-table
     */
    public function makeClip($fileOrFolderObject)
    {
        if (!$fileOrFolderObject->checkActionPermission('read')) {
            return '';
        }
        $cells = [];
        $fullIdentifier = $fileOrFolderObject->getCombinedIdentifier();
        $fullName = $fileOrFolderObject->getName();
        $md5 = GeneralUtility::shortMD5($fullIdentifier);
        // For normal clipboard, add copy/cut buttons:
        if ($this->clipObj->current === 'normal') {
            $isSel = $this->clipObj->isSelected('_FILE', $md5);
            $copyTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.copy');
            $cutTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.cut');
            $copyIcon = $this->iconFactory->getIcon('actions-edit-copy', Icon::SIZE_SMALL)->render();
            $cutIcon = $this->iconFactory->getIcon('actions-edit-cut', Icon::SIZE_SMALL)->render();

            if ($isSel === 'copy') {
                $copyIcon = $this->iconFactory->getIcon('actions-edit-copy-release', Icon::SIZE_SMALL)->render();
                $copyTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.copyrelease');
            } elseif ($isSel === 'cut') {
                $cutIcon = $this->iconFactory->getIcon('actions-edit-cut-release', Icon::SIZE_SMALL)->render();
                $cutTitle = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.cutrelease');
            }

            if ($fileOrFolderObject->checkActionPermission('copy')) {
                $cells[] = '<a class="btn btn-default" href="' . htmlspecialchars($this->clipObj->selUrlFile(
                    $fullIdentifier,
                    true,
                    $isSel === 'copy'
                )) . '" title="' . htmlspecialchars($copyTitle) . '">' . $copyIcon . '</a>';
            } else {
                $cells[] = $this->spaceIcon;
            }
            // we can only cut if file can be moved
            if ($fileOrFolderObject->checkActionPermission('move')) {
                $cells[] = '<a class="btn btn-default" href="' . htmlspecialchars($this->clipObj->selUrlFile(
                    $fullIdentifier,
                    false,
                    $isSel === 'cut'
                )) . '" title="' . htmlspecialchars($cutTitle) . '">' . $cutIcon . '</a>';
            } else {
                $cells[] = $this->spaceIcon;
            }
        } else {
            // For numeric pads, add select checkboxes:
            $n = '_FILE|' . $md5;
            $this->CBnames[] = $n;
            $checked = $this->clipObj->isSelected('_FILE', $md5) ? ' checked="checked"' : '';
            $cells[] = '<label class="btn btn-default btn-checkbox"><input type="checkbox" name="CBC[' . $n . ']" value="' . htmlspecialchars($fullIdentifier) . '" ' . $checked . ' /><span class="t3-icon fa"></span><input type="hidden" name="CBH[' . $n . ']" value="0" /></label>';
        }
        // Display PASTE button, if directory:
        $elFromTable = $this->clipObj->elFromTable('_FILE');
        if ($fileOrFolderObject instanceof Folder && !empty($elFromTable) && $fileOrFolderObject->checkActionPermission('write')) {
            $clipboardMode = $this->clipObj->clipData[$this->clipObj->current]['mode'] ?? '';
            $permission = $clipboardMode === 'copy' ? 'copy' : 'move';
            $addPasteButton = $this->folderObject->checkActionPermission($permission);
            $elToConfirm = [];
            foreach ($elFromTable as $key => $element) {
                $clipBoardElement = $this->resourceFactory->retrieveFileOrFolderObject($element);
                if ($clipBoardElement instanceof Folder && $clipBoardElement->getStorage()->isWithinFolder($clipBoardElement, $fileOrFolderObject)) {
                    $addPasteButton = false;
                }
                $elToConfirm[$key] = $clipBoardElement->getName();
            }
            if ($addPasteButton) {
                $cells[] = '<a class="btn btn-default t3js-modal-trigger" '
                    . ' href="' . htmlspecialchars($this->clipObj->pasteUrl('_FILE', $fullIdentifier)) . '"'
                    . ' data-bs-content="' . htmlspecialchars($this->clipObj->confirmMsgText('_FILE', $fullName, 'into', $elToConfirm)) . '"'
                    . ' data-severity="warning"'
                    . ' data-title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteInto')) . '"'
                    . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteInto')) . '"'
                    . '>'
                    . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render()
                    . '</a>';
            } else {
                $cells[] = $this->spaceIcon;
            }
        }
        // Compile items into a DIV-element:
        return ' <div class="btn-group" role="group">' . implode('', $cells) . '</div>';
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
                        $metaDataUid => 'edit'
                    ]
                ],
                'returnUrl' => $this->listURL()
            ];
            $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
            $title = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editMetadata'));
            $cells['metadata'] = '<a class="btn btn-default" href="' . htmlspecialchars($url) . '" title="' . $title . '">' . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>';
        }

        // document view
        if ($fileOrFolderObject instanceof File) {
            $fileUrl = $fileOrFolderObject->getPublicUrl();
            if ($fileUrl) {
                $cells['view'] = '<a href="' . htmlspecialchars(PathUtility::getAbsoluteWebPath($fileUrl)) . '" target="_blank" class="btn btn-default" title="' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.view') . '">' . $this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL)->render() . '</a>';
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
        unset($cells['__fileOrFolderObject']);
        // Compile items into a dropdown
        $cellOutput = '';
        $output = '';
        foreach ($cells as $key => $action) {
            if (in_array($key, ['view', 'metadata', 'delete'])) {
                $output .= $action;
                continue;
            }
            if ($action === $this->spaceIcon) {
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
                $action = str_replace('</a>', ' ' . $title[1] . '</a>', $action);
                $action = str_replace('</button>', ' ' . $title[1] . '</button>', $action);
            }
            $cellOutput .= '<li>' . $action . '</li>';
        }
        $icon = $this->iconFactory->getIcon('actions-menu-alternative', Icon::SIZE_SMALL);
        $output .= '<div class="btn-group dropdown position-static">' .
            '<a href="#actions_' . $fileOrFolderObject->getHashedIdentifier() . '" class="btn btn-default dropdown-toggle dropdown-toggle-no-chevron" data-bs-toggle="dropdown" data-bs-boundary="window" aria-expanded="false">' . $icon->render() . '</a>' .
            '<ul id="actions_' . $fileOrFolderObject->getHashedIdentifier() . '" class="dropdown-menu dropdown-list">' . $cellOutput . '</ul>' .
            '</div>';
        return '<div class="btn-group position-static">' . $output . '</div>';
    }

    /**
     * Make reference count
     *
     * @param File|Folder $fileOrFolderObject Array with information about the file/directory for which to make the clipboard panel for the listing.
     * @return string HTML
     */
    public function makeRef($fileOrFolderObject)
    {
        if ($fileOrFolderObject instanceof FolderInterface) {
            return '-';
        }
        // Look up the file in the sys_refindex.
        // Exclude sys_file_metadata records as these are no use references
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_refindex');
        $referenceCount = $queryBuilder->count('*')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq(
                    'ref_table',
                    $queryBuilder->createNamedParameter('sys_file', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'ref_uid',
                    $queryBuilder->createNamedParameter($fileOrFolderObject->getUid(), \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->neq(
                    'tablename',
                    $queryBuilder->createNamedParameter('sys_file_metadata', \PDO::PARAM_STR)
                )
            )
            ->execute()
            ->fetchOne();

        return $this->generateReferenceToolTip($referenceCount, $fileOrFolderObject);
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

    /**
     * Generates HTML code for a Reference tooltip out of
     * sys_refindex records you hand over
     *
     * @param int $references number of records from sys_refindex table
     * @param AbstractFile $fileObject
     * @return string
     */
    protected function generateReferenceToolTip($references, $fileObject)
    {
        if (!$references) {
            return '-';
        }
        $attributes = [
            'data-filelist-show-item-type' => '_FILE',
            'data-filelist-show-item-identifier' => $fileObject->getCombinedIdentifier(),
            'title' => $this->getLanguageService()
                ->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:show_references')
                . ' (' . $references . ')'
        ];
        $htmlCode = '<a href="#" ' . GeneralUtility::implodeAttributes($attributes, true) . '">';
        $htmlCode .= $references;
        $htmlCode .= '</a>';
        return $htmlCode;
    }

    protected function isEditMetadataAllowed(File $file): bool
    {
        return $file->isIndexed()
            && $file->checkActionPermission('editMeta')
            && $this->getBackendUser()->check('tables_modify', 'sys_file_metadata');
    }
}
