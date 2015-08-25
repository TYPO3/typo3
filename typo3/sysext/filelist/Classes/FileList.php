<?php
namespace TYPO3\CMS\Filelist;

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

use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\InaccessibleFolder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Utility\ListUtility;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Filelist\Configuration\ThumbnailConfiguration;
use TYPO3\CMS\Filelist\Controller\FileListController;

/**
 * Class for rendering of File>Filelist
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
    public $sortRev = 1;

    /**
     * @var int
     */
    public $firstElementNumber = 0;

    /**
     * @var bool
     */
    public $clipBoard = 0;

    /**
     * @var bool
     */
    public $bigControlPanel = 0;

    /**
     * @var string
     */
    public $JScode = '';

    /**
     * String with accumulated HTML content
     *
     * @var string
     */
    public $HTMLcode = '';

    /**
     * @var int
     */
    public $totalbytes = 0;

    /**
     * @var array
     */
    public $dirs = [];

    /**
     * @var array
     */
    public $files = [];

    /**
     * @var string
     */
    public $path = '';

    /**
     * OBSOLETE - NOT USED ANYMORE. leftMargin
     *
     * @var int
     */
    public $leftMargin = 0;

    /**
     * This could be set to the total number of items. Used by the fwd_rew_navigation...
     *
     * @var string
     */
    public $totalItems = '';

    /**
     * Decides the columns shown. Filled with values that refers to the keys of the data-array. $this->fieldArray[0] is the title column.
     *
     * @var array
     */
    public $fieldArray = [];

    /**
     * Set to zero, if you don't want a left-margin with addElement function
     *
     * @var int
     */
    public $setLMargin = 1;

    /**
     * Contains page translation languages
     *
     * @var array
     */
    public $pageOverlays = [];

    /**
     * Counter increased for each element. Used to index elements for the JavaScript-code that transfers to the clipboard
     *
     * @var int
     */
    public $counter = 0;

    /**
     * Contains sys language icons and titles
     *
     * @var array
     */
    public $languageIconTitles = [];

    /**
     * Script URL
     *
     * @var string
     */
    public $thisScript = '';

    /**
     * If set this is <td> CSS-classname for odd columns in addElement. Used with db_layout / pages section
     *
     * @var string
     */
    public $oddColumnsCssClass = '';

    /**
     * Counting the elements no matter what
     *
     * @var int
     */
    public $eCounter = 0;

    /**
     * @var TranslationConfigurationProvider
     */
    public $translateTools;

    /**
     * Keys are fieldnames and values are td-parameters to add in addElement(), please use $addElement_tdCSSClass for CSS-classes;
     *
     * @var array
     */
    public $addElement_tdParams = [];

    /**
     * @var int
     */
    public $no_noWrap = 0;

    /**
     * @var int
     */
    public $showIcon = 1;

    /**
     * Keys are fieldnames and values are td-css-classes to add in addElement();
     *
     * @var array
     */
    public $addElement_tdCssClass = [];

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
     * @var FileListController
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    protected $fileListController;

    /**
     * @var ThumbnailConfiguration
     */
    protected $thumbnailConfiguration;

    /**
     * Construct
     *
     * @param FileListController $fileListController @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function __construct(FileListController $fileListController = null)
    {
        $backendUser = $this->getBackendUser();
        if (isset($backendUser->uc['titleLen']) && $backendUser->uc['titleLen'] > 0) {
            $this->fixedL = $backendUser->uc['titleLen'];
        }
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->getTranslateTools();
        $this->determineScriptUrl();
        $this->fileListController = $fileListController;
        $this->thumbnailConfiguration = GeneralUtility::makeInstance(ThumbnailConfiguration::class);
        $this->iLimit = MathUtility::forceIntegerInRange(
            $backendUser->getTSConfig()['options.']['file_list.']['filesPerPage'] ?? $this->iLimit,
            1
        );
    }

    /**
     * @param ResourceFactory $resourceFactory
     */
    public function injectResourceFactory(ResourceFactory $resourceFactory)
    {
        $this->resourceFactory = $resourceFactory;
    }

    /**
     * Initialization of class
     *
     * @param Folder $folderObject The folder to work on
     * @param int $pointer Pointer
     * @param bool $sort Sorting column
     * @param bool $sortRev Sorting direction
     * @param bool $clipBoard
     * @param bool $bigControlPanel Show clipboard flag
     */
    public function start(Folder $folderObject, $pointer, $sort, $sortRev, $clipBoard = false, $bigControlPanel = false)
    {
        $this->folderObject = $folderObject;
        $this->counter = 0;
        $this->totalbytes = 0;
        $this->JScode = '';
        $this->HTMLcode = '';
        $this->path = $folderObject->getReadablePath();
        $this->sort = $sort;
        $this->sortRev = $sortRev;
        $this->firstElementNumber = $pointer;
        $this->clipBoard = $clipBoard;
        $this->bigControlPanel = $bigControlPanel;
        // Setting the maximum length of the filenames to the user's settings or minimum 30 (= $this->fixedL)
        $this->fixedL = max($this->fixedL, $this->getBackendUser()->uc['titleLen']);
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_common.xlf');
        $this->resourceFactory = ResourceFactory::getInstance();
    }

    /**
     * Reading files and directories, counting elements and generating the list in ->HTMLcode
     */
    public function generateList()
    {
        $this->HTMLcode .= $this->getTable('fileext,tstamp,size,rw,_REF_');
    }

    /**
     * Wrapping input string in a link with clipboard command.
     *
     * @param string $string String to be linked - must be htmlspecialchar'ed / prepared before.
     * @param string $_ unused
     * @param string $cmd "cmd" value
     * @param string $warning Warning for JS confirm message
     * @return string Linked string
     */
    public function linkClipboardHeaderIcon($string, $_, $cmd, $warning = '')
    {
        $jsCode = 'document.dblistForm.cmd.value=' . GeneralUtility::quoteJSvalue($cmd)
            . ';document.dblistForm.submit();';

        $attributes = [];
        if ($warning) {
            $attributes['class'] = 'btn btn-default t3js-modal-trigger';
            $attributes['data-href'] = 'javascript:' . $jsCode;
            $attributes['data-severity'] = 'warning';
            $attributes['data-content'] = $warning;
        } else {
            $attributes['class'] = 'btn btn-default';
            $attributes['onclick'] = $jsCode . 'return false;';
        }

        $attributesString = '';
        foreach ($attributes as $key => $value) {
            $attributesString .= ' ' . $key . '="' . htmlspecialchars($value) . '"';
        }
        return '<a href="#" ' . $attributesString . '>' . $string . '</a>';
    }

    /**
     * Returns a table with directories and files listed.
     *
     * @param array $rowlist Array of files from path
     * @return string HTML-table
     */
    public function getTable($rowlist)
    {
        // prepare space icon
        $this->spaceIcon = '<span class="btn btn-default disabled">' . $this->iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render() . '</span>';

        // @todo use folder methods directly when they support filters
        $storage = $this->folderObject->getStorage();
        $storage->resetFileAndFolderNameFiltersToDefault();

        // Only render the contents of a browsable storage
        if ($this->folderObject->getStorage()->isBrowsable()) {
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
            } else {
                if ($this->firstElementNumber <= $foldersCount) {
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
            }

            $folders = $storage->getFoldersInFolder($this->folderObject, $foldersFrom, $foldersNum, true, false, trim($this->sort), (bool)$this->sortRev);
            $files = $this->folderObject->getFiles($filesFrom, $filesNum, Folder::FILTER_MODE_USE_OWN_AND_STORAGE_FILTERS, false, trim($this->sort), (bool)$this->sortRev);
            $this->totalItems = $foldersCount + $filesCount;
            // Adds the code of files/dirs
            $out = '';
            $titleCol = 'file';
            // Cleaning rowlist for duplicates and place the $titleCol as the first column always!
            $rowlist = '_LOCALIZATION_,' . $rowlist;
            $rowlist = GeneralUtility::rmFromList($titleCol, $rowlist);
            $rowlist = GeneralUtility::uniqueList($rowlist);
            $rowlist = $rowlist ? $titleCol . ',' . $rowlist : $titleCol;
            if ($this->clipBoard) {
                $rowlist = str_replace('_LOCALIZATION_,', '_LOCALIZATION_,_CLIPBOARD_,', $rowlist);
                $this->addElement_tdCssClass['_CLIPBOARD_'] = 'col-clipboard';
            }
            if ($this->bigControlPanel) {
                $rowlist = str_replace('_LOCALIZATION_,', '_LOCALIZATION_,_CONTROL_,', $rowlist);
                $this->addElement_tdCssClass['_CONTROL_'] = 'col-control';
            }
            $this->fieldArray = explode(',', $rowlist);

            // Add classes to table cells
            $this->addElement_tdCssClass[$titleCol] = 'col-title col-responsive';
            $this->addElement_tdCssClass['_LOCALIZATION_'] = 'col-localizationa';

            $folders = ListUtility::resolveSpecialFolderNames($folders);

            $iOut = '';
            // Directories are added
            $this->eCounter = $this->firstElementNumber;
            list(, $code) = $this->fwd_rwd_nav();
            $iOut .= $code;

            $iOut .= $this->formatDirList($folders);
            // Files are added
            $iOut .= $this->formatFileList($files);

            $this->eCounter = $this->firstElementNumber + $this->iLimit < $this->totalItems
                ? $this->firstElementNumber + $this->iLimit
                : -1;
            list(, $code) = $this->fwd_rwd_nav();
            $iOut .= $code;

            // Header line is drawn
            $theData = [];
            foreach ($this->fieldArray as $v) {
                if ($v === '_CLIPBOARD_' && $this->clipBoard) {
                    $cells = [];
                    $table = '_FILE';
                    $elFromTable = $this->clipObj->elFromTable($table);
                    if (!empty($elFromTable) && $this->folderObject->checkActionPermission('write')) {
                        $addPasteButton = true;
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
                                . ' data-content="' . htmlspecialchars($this->clipObj->confirmMsgText(
                                    '_FILE',
                                    $this->path,
                                    'into',
                                    $elToConfirm
                                )) . '"'
                                . ' data-severity="warning"'
                                . ' data-title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_paste')) . '"'
                                . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_paste')) . '">'
                                . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)
                                    ->render()
                                . '</a>';
                        }
                    }
                    if ($this->clipObj->current !== 'normal' && $iOut) {
                        $cells[] = $this->linkClipboardHeaderIcon('<span title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_selectMarked')) . '">' . $this->iconFactory->getIcon('actions-edit-copy', Icon::SIZE_SMALL)->render() . '</span>', $table, 'setCB');
                        $cells[] = $this->linkClipboardHeaderIcon('<span title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_deleteMarked')) . '">' . $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render(), $table, 'delete', $this->getLanguageService()->getLL('clip_deleteMarkedWarning'));
                        $onClick = 'checkOffCB(' . GeneralUtility::quoteJSvalue(implode(',', $this->CBnames)) . ', this); return false;';
                        $cells[] = '<a class="btn btn-default" rel="" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_markRecords')) . '">' . $this->iconFactory->getIcon('actions-document-select', Icon::SIZE_SMALL)->render() . '</a>';
                    }
                    $theData[$v] = implode('', $cells);
                } else {
                    // Normal row:
                    $theT = $this->linkWrapSort(htmlspecialchars($this->getLanguageService()->getLL('c_' . $v)), $this->folderObject->getCombinedIdentifier(), $v);
                    $theData[$v] = $theT;
                }
            }

            $out .= '<thead>' . $this->addElement(1, '', $theData, '', '', '', 'th') . '</thead>';
            $out .= '<tbody>' . $iOut . '</tbody>';
            // half line is drawn
            // finish
            $out = '
                <!--
                    Filelist table:
                -->
                <div class="panel panel-default">
                    <div class="table-fit">
                        <table class="table table-striped table-hover" id="typo3-filelist">
                            ' . $out . '
                        </table>
                    </div>
                </div>';
        } else {
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $this->getLanguageService()->getLL('storageNotBrowsableMessage'), $this->getLanguageService()->getLL('storageNotBrowsableTitle'), FlashMessage::INFO);
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageService $flashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var \TYPO3\CMS\Core\Messaging\FlashMessageQueue $defaultFlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
            $out = '';
        }
        return $out;
    }

    /**
     * Returns a table-row with the content from the fields in the input data array.
     * OBS: $this->fieldArray MUST be set! (represents the list of fields to display)
     *
     * @param int $h Is an integer >=0 and denotes how tall an element is. Set to '0' makes a halv line, -1 = full line, set to 1 makes a 'join' and above makes 'line'
     * @param string $icon Is the <img>+<a> of the record. If not supplied the first 'join'-icon will be a 'line' instead
     * @param array $data Is the dataarray, record with the fields. Notice: These fields are (currently) NOT htmlspecialchar'ed before being wrapped in <td>-tags
     * @param string $rowParams Is insert in the <tr>-tags. Must carry a ' ' as first character
     * @param string $_ OBSOLETE - NOT USED ANYMORE. $lMargin is the leftMargin (int)
     * @param string $_2 OBSOLETE - NOT USED ANYMORE. Is the HTML <img>-tag for an alternative 'gfx/ol/line.gif'-icon (used in the top)
     * @param string $colType Defines the tag being used for the columns. Default is td.
     *
     * @return string HTML content for the table row
     */
    public function addElement($h, $icon, $data, $rowParams = '', $_ = '', $_2 = '', $colType = 'td')
    {
        $colType = ($colType === 'th') ? 'th' : 'td';
        $noWrap = $this->no_noWrap ? '' : ' nowrap';
        // Start up:
        $l10nParent = (int)($data['_l10nparent_'] ?? 0);
        $out = '
		<!-- Element, begin: -->
		<tr ' . $rowParams . ' data-uid="' . (int)($data['uid'] ?? 0) . '" data-l10nparent="' . $l10nParent . '">';
        // Show icon and lines
        if ($this->showIcon) {
            $out .= '
			<' . $colType . ' class="col-icon nowrap">';
            if (!$h) {
                $out .= '&nbsp;';
            } else {
                for ($a = 0; $a < $h; $a++) {
                    if (!$a) {
                        if ($icon) {
                            $out .= $icon;
                        }
                    }
                }
            }
            $out .= '</' . $colType . '>
			';
        }
        // Init rendering.
        $colsp = '';
        $lastKey = '';
        $c = 0;
        $ccount = 0;
        // __label is used as the label key to circumvent problems with uid used as label (see #67756)
        // as it was introduced later on, check if it really exists before using it
        $fields = $this->fieldArray;
        if ($colType === 'td' && array_key_exists('__label', $data)) {
            $fields[0] = '__label';
        }
        // Traverse field array which contains the data to present:
        foreach ($fields as $vKey) {
            if (isset($data[$vKey])) {
                if ($lastKey) {
                    $cssClass = $this->addElement_tdCssClass[$lastKey] ?? '';
                    if ($this->oddColumnsCssClass && $ccount % 2 == 0) {
                        $cssClass = implode(' ', [$cssClass, $this->oddColumnsCssClass]);
                    }
                    $out .= '
						<' . $colType . ' class="' . $cssClass . $noWrap . '"' . $colsp . ($this->addElement_tdParams[$lastKey] ?? '') . '>' . $data[$lastKey] . '</' . $colType . '>';
                }
                $lastKey = $vKey;
                $c = 1;
                $ccount++;
            } else {
                if (!$lastKey) {
                    $lastKey = $vKey;
                }
                $c++;
            }
            if ($c > 1) {
                $colsp = ' colspan="' . $c . '"';
            } else {
                $colsp = '';
            }
        }
        if ($lastKey) {
            $cssClass = $this->addElement_tdCssClass[$lastKey] ?? '';
            if ($this->oddColumnsCssClass) {
                $cssClass = implode(' ', [$cssClass, $this->oddColumnsCssClass]);
            }
            $out .= '
				<' . $colType . ' class="' . $cssClass . $noWrap . '"' . $colsp . ($this->addElement_tdParams[$lastKey] ?? '') . '>' . $data[$lastKey] . '</' . $colType . '>';
        }
        // End row
        $out .= '
		</tr>';
        // Return row.
        return $out;
    }

    /**
     * Dummy function, used to write the top of a table listing.
     */
    public function writeTop()
    {
    }

    /**
     * Creates a forward/reverse button based on the status of ->eCounter, ->firstElementNumber, ->iLimit
     *
     * @param string $table Table name
     * @return array array([boolean], [HTML]) where [boolean] is 1 for reverse element, [HTML] is the table-row code for the element
     */
    public function fwd_rwd_nav($table = '')
    {
        $code = '';
        if ($this->eCounter >= $this->firstElementNumber && $this->eCounter < $this->firstElementNumber + $this->iLimit) {
            if ($this->firstElementNumber && $this->eCounter == $this->firstElementNumber) {
                // 	Reverse
                $theData = [];
                $titleCol = $this->fieldArray[0];
                $theData[$titleCol] = $this->fwd_rwd_HTML('fwd', $this->eCounter, $table);
                $code = $this->addElement(1, '', $theData, 'class="fwd_rwd_nav"');
            }
            return [1, $code];
        }
        if ($this->eCounter == $this->firstElementNumber + $this->iLimit) {
            // 	Forward
            $theData = [];
            $titleCol = $this->fieldArray[0];
            $theData[$titleCol] = $this->fwd_rwd_HTML('rwd', $this->eCounter, $table);
            $code = $this->addElement(1, '', $theData, 'class="fwd_rwd_nav"');
        }
        return [0, $code];
    }

    /**
     * Creates the button with link to either forward or reverse
     *
     * @param string $type Type: "fwd" or "rwd
     * @param int $pointer Pointer
     * @param string $table Table name
     * @return string
     * @internal
     */
    public function fwd_rwd_HTML($type, $pointer, $table = '')
    {
        $content = '';
        $tParam = $table ? '&table=' . rawurlencode($table) : '';
        switch ($type) {
            case 'fwd':
                $href = $this->listURL() . '&pointer=' . ($pointer - $this->iLimit) . $tParam;
                $content = '<a href="' . htmlspecialchars($href) . '">' . $this->iconFactory->getIcon(
                        'actions-move-up',
                        Icon::SIZE_SMALL
                    )->render() . '</a> <i>[' . (max(0, $pointer - $this->iLimit) + 1) . ' - ' . $pointer . ']</i>';
                break;
            case 'rwd':
                $href = $this->listURL() . '&pointer=' . $pointer . $tParam;
                $content = '<a href="' . htmlspecialchars($href) . '">' . $this->iconFactory->getIcon(
                        'actions-move-down',
                        Icon::SIZE_SMALL
                    )->render() . '</a> <i>[' . ($pointer + 1) . ' - ' . $this->totalItems . ']</i>';
                break;
        }
        return $content;
    }

    /**
     * Returning JavaScript for ClipBoard functionality.
     *
     * @return string
     */
    public function CBfunctions()
    {
        return '
		// checkOffCB()
	function checkOffCB(listOfCBnames, link) {	//
		var checkBoxes, flag, i;
		var checkBoxes = listOfCBnames.split(",");
		if (link.rel === "") {
			link.rel = "allChecked";
			flag = true;
		} else {
			link.rel = "";
			flag = false;
		}
		for (i = 0; i < checkBoxes.length; i++) {
			setcbValue(checkBoxes[i], flag);
		}
	}
		// cbValue()
	function cbValue(CBname) {	//
		var CBfullName = "CBC["+CBname+"]";
		return (document.dblistForm[CBfullName] && document.dblistForm[CBfullName].checked ? 1 : 0);
	}
		// setcbValue()
	function setcbValue(CBname,flag) {	//
		CBfullName = "CBC["+CBname+"]";
		if(document.dblistForm[CBfullName]) {
			document.dblistForm[CBfullName].checked = flag ? "on" : 0;
		}
	}

		';
    }

    /**
     * Initializes page languages and icons
     */
    public function initializeLanguages()
    {
        // Look up page overlays:
        $localizationParentField = $GLOBALS['TCA']['pages']['ctrl']['transOrigPointerField'];
        $languageField = $GLOBALS['TCA']['pages']['ctrl']['languageField'];
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        $result = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->andX(
                    $queryBuilder->expr()->eq(
                        $localizationParentField,
                        $queryBuilder->createNamedParameter($this->id, \PDO::PARAM_INT)
                    ),
                    $queryBuilder->expr()->gt(
                        $languageField,
                        $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                    )
                )
            )
            ->execute();

        $this->pageOverlays = [];
        while ($row = $result->fetch()) {
            $this->pageOverlays[$row[$languageField]] = $row;
        }

        $this->languageIconTitles = $this->getTranslateTools()->getSystemLanguages($this->id);
    }

    /**
     * Return the icon for the language
     *
     * @param int $sys_language_uid Sys language uid
     * @param bool $addAsAdditionalText If set to true, only the flag is returned
     * @return string Language icon
     */
    public function languageFlag($sys_language_uid, $addAsAdditionalText = true)
    {
        $out = '';
        $title = htmlspecialchars($this->languageIconTitles[$sys_language_uid]['title']);
        if ($this->languageIconTitles[$sys_language_uid]['flagIcon']) {
            $out .= '<span title="' . $title . '">' . $this->iconFactory->getIcon(
                    $this->languageIconTitles[$sys_language_uid]['flagIcon'],
                    Icon::SIZE_SMALL
                )->render() . '</span>';
            if (!$addAsAdditionalText) {
                return $out;
            }
            $out .= '&nbsp;';
        }
        $out .= $title;
        return $out;
    }

    /**
     * If there is a parent folder and user has access to it, return an icon
     * which is linked to the filelist of the parent folder.
     *
     * @param Folder $currentFolder
     * @return string
     */
    protected function getLinkToParentFolder(Folder $currentFolder)
    {
        $levelUp = '';
        try {
            $currentStorage = $currentFolder->getStorage();
            $parentFolder = $currentFolder->getParentFolder();
            if ($parentFolder->getIdentifier() !== $currentFolder->getIdentifier() && $currentStorage->isWithinFileMountBoundaries($parentFolder)) {
                $levelUp = $this->linkWrapDir(
                    '<span title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.upOneLevel')) . '">'
                    . $this->iconFactory->getIcon('actions-view-go-up', Icon::SIZE_SMALL)->render()
                    . '</span>',
                    $parentFolder
                );
            }
        } catch (\Exception $e) {
        }
        return $levelUp;
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
                $theIcon = BackendUtility::wrapClickMenuOnIcon($theIcon, 'sys_file', $folderObject->getCombinedIdentifier());
            }

            // Preparing and getting the data-array
            $theData = [];
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
                        default:
                            $theData[$field] = GeneralUtility::fixed_lgd_cs($theData[$field] ?? '', $this->fixedL);
                    }
                }
            }
            $out .= $this->addElement(1, $theIcon, $theData);
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
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $href = (string)$uriBuilder->buildUriFromRoute('file_FilelistList', ['id' => $folderObject->getCombinedIdentifier()]);
        $onclick = ' onclick="' . htmlspecialchars('top.document.getElementsByName("nav_frame")[0].contentWindow.Tree.highlightActiveItem("file","folder' . GeneralUtility::md5int($folderObject->getCombinedIdentifier()) . '_"+top.fsMod.currentBank)') . '"';
        // Sometimes $code contains plain HTML tags. In such a case the string should not be modified!
        if ((string)$title === strip_tags($title)) {
            return '<a href="' . htmlspecialchars($href) . '"' . $onclick . ' title="' . htmlspecialchars($title) . '">' . $title . '</a>';
        }
        return '<a href="' . htmlspecialchars($href) . '"' . $onclick . '>' . $title . '</a>';
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
            if ($fileObject instanceof File && $fileObject->isIndexed() && $fileObject->checkActionPermission('editMeta') && $this->getBackendUser()->check('tables_modify', 'sys_file_metadata')) {
                $metaData = $fileObject->_getMetaData();
                $urlParameters = [
                    'edit' => [
                        'sys_file_metadata' => [
                            $metaData['uid'] => 'edit'
                        ]
                    ],
                    'returnUrl' => $this->listURL()
                ];
                /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
                $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
                $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
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
     * The URL however is not relative, otherwise GeneralUtility::sanitizeLocalUrl() would say that
     * the URL would be invalid
     *
     * @param string $altId
     * @param string $table Table name to display. Enter "-1" for the current table.
     * @param string $exclList Comma separated list of fields NOT to include ("sortField", "sortRev" or "firstElementNumber")
     *
     * @return string URL
     */
    public function listURL($altId = '', $table = '-1', $exclList = '')
    {
        return GeneralUtility::linkThisScript([
            'target' => rawurlencode($this->folderObject->getCombinedIdentifier()),
            'imagemode' => $this->thumbs
        ]);
    }

    /**
     * This returns tablerows for the files in the array $items['sorting'].
     *
     * @param File[] $files File items
     * @return string HTML table rows.
     */
    public function formatFileList(array $files)
    {
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $out = '';
        // first two keys are "0" (default) and "-1" (multiple), after that comes the "other languages"
        $allSystemLanguages = GeneralUtility::makeInstance(TranslationConfigurationProvider::class)->getSystemLanguages();
        $systemLanguages = array_filter($allSystemLanguages, function ($languageRecord) {
            if ($languageRecord['uid'] === -1 || $languageRecord['uid'] === 0 || !$this->getBackendUser()->checkLanguageAccess($languageRecord['uid'])) {
                return false;
            }
            return true;
        });

        foreach ($files as $fileObject) {
            // Initialization
            $this->counter++;
            $this->totalbytes += $fileObject->getSize();
            $ext = $fileObject->getExtension();
            $fileName = trim($fileObject->getName());
            // The icon with link
            $theIcon = '<span title="' . htmlspecialchars($fileName . ' [' . (int)$fileObject->getUid() . ']') . '">'
                . $this->iconFactory->getIconForResource($fileObject, Icon::SIZE_SMALL)->render() . '</span>';
            $theIcon = BackendUtility::wrapClickMenuOnIcon($theIcon, 'sys_file', $fileObject->getCombinedIdentifier());
            // Preparing and getting the data-array
            $theData = [];
            foreach ($this->fieldArray as $field) {
                switch ($field) {
                    case 'size':
                        $theData[$field] = GeneralUtility::formatSize($fileObject->getSize(), htmlspecialchars($this->getLanguageService()->getLL('byteSizeUnits')));
                        break;
                    case 'rw':
                        $theData[$field] = '' . (!$fileObject->checkActionPermission('read') ? ' ' : '<strong class="text-danger">' . htmlspecialchars($this->getLanguageService()->getLL('read')) . '</strong>') . (!$fileObject->checkActionPermission('write') ? '' : '<strong class="text-danger">' . htmlspecialchars($this->getLanguageService()->getLL('write')) . '</strong>');
                        break;
                    case 'fileext':
                        $theData[$field] = strtoupper($ext);
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
                        if (!empty($systemLanguages) && $fileObject->isIndexed() && $fileObject->checkActionPermission('editMeta') && $this->getBackendUser()->check('tables_modify', 'sys_file_metadata')) {
                            $metaDataRecord = $fileObject->_getMetaData();
                            $translations = $this->getTranslationsForMetaData($metaDataRecord);
                            $languageCode = '';

                            foreach ($systemLanguages as $language) {
                                $languageId = $language['uid'];
                                $flagIcon = $language['flagIcon'];
                                if (array_key_exists($languageId, $translations)) {
                                    $title = htmlspecialchars(sprintf($this->getLanguageService()->getLL('editMetadataForLanguage'), $language['title']));
                                    // @todo the overlay for the flag needs to be added ($flagIcon . '-overlay')
                                    $urlParameters = [
                                        'edit' => [
                                            'sys_file_metadata' => [
                                                $translations[$languageId]['uid'] => 'edit'
                                            ]
                                        ],
                                        'returnUrl' => $this->listURL()
                                    ];
                                    $flagButtonIcon = $this->iconFactory->getIcon($flagIcon, Icon::SIZE_SMALL, 'overlay-edit')->render();
                                    $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
                                    $languageCode .= '<a href="' . htmlspecialchars($url) . '" class="btn btn-default" title="' . $title . '">'
                                        . $flagButtonIcon . '</a>';
                                } else {
                                    $parameters = [
                                        'justLocalized' => 'sys_file_metadata:' . $metaDataRecord['uid'] . ':' . $languageId,
                                        'returnUrl' => $this->listURL()
                                    ];
                                    $returnUrl = (string)$uriBuilder->buildUriFromRoute('record_edit', $parameters);
                                    $href = BackendUtility::getLinkToDataHandlerAction(
                                        '&cmd[sys_file_metadata][' . $metaDataRecord['uid'] . '][localize]=' . $languageId,
                                        $returnUrl
                                    );
                                    $flagButtonIcon = '<span title="' . htmlspecialchars(sprintf($this->getLanguageService()->getLL('createMetadataForLanguage'), $language['title'])) . '">' . $this->iconFactory->getIcon($flagIcon, Icon::SIZE_SMALL, 'overlay-new')->render() . '</span>';
                                    $languageCode .= '<a href="' . htmlspecialchars($href) . '" class="btn btn-default">' . $flagButtonIcon . '</a> ';
                                }
                            }

                            // Hide flag button bar when not translated yet
                            $theData[$field] = ' <div class="localisationData btn-group" data-fileid="' . $fileObject->getUid() . '"' .
                                (empty($translations) ? ' style="display: none;"' : '') . '>' . $languageCode . '</div>';
                            $theData[$field] .= '<a class="btn btn-default filelist-translationToggler" data-fileid="' . $fileObject->getUid() . '">' .
                                '<span title="' . htmlspecialchars($this->getLanguageService()->getLL('translateMetadata')) . '">'
                                . $this->iconFactory->getIcon('mimetypes-x-content-page-language-overlay', Icon::SIZE_SMALL)->render() . '</span>'
                                . '</a>';
                        }
                        break;
                    case '_REF_':
                        $theData[$field] = $this->makeRef($fileObject);
                        break;
                    case 'file':
                        // Edit metadata of file
                        $theData[$field] = $this->linkWrapFile(htmlspecialchars($fileName), $fileObject);

                        if ($fileObject->isMissing()) {
                            $theData[$field] .= '<span class="label label-danger label-space-left">'
                                . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing'))
                                . '</span>';
                        // Thumbnails?
                        } elseif ($this->thumbs && ($this->isImage($ext) || $this->isMediaFile($ext))) {
                            $processedFile = $fileObject->process(
                                ProcessedFile::CONTEXT_IMAGEPREVIEW,
                                [
                                    'width' => $this->thumbnailConfiguration->getWidth(),
                                    'height' => $this->thumbnailConfiguration->getHeight()
                                ]
                            );
                            if ($processedFile) {
                                $thumbUrl = $processedFile->getPublicUrl(true);
                                $theData[$field] .= '<br /><img src="' . htmlspecialchars($thumbUrl) . '" ' .
                                    'width="' . $processedFile->getProperty('width') . '" ' .
                                    'height="' . $processedFile->getProperty('height') . '" ' .
                                    'title="' . htmlspecialchars($fileName) . '" alt="" />';
                            }
                        }
                        break;
                    default:
                        $theData[$field] = '';
                        if ($fileObject->hasProperty($field)) {
                            $theData[$field] = htmlspecialchars(GeneralUtility::fixed_lgd_cs($fileObject->getProperty($field), $this->fixedL));
                        }
                }
            }
            $out .= $this->addElement(1, $theIcon, $theData);
        }
        return $out;
    }

    /**
     * Fetch the translations for a sys_file_metadata record
     *
     * @param $metaDataRecord
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
                    $queryBuilder->createNamedParameter($metaDataRecord['uid'], \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->gt(
                    $GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetchAll();

        $translations = [];
        foreach ($translationRecords as $record) {
            $translations[$record[$GLOBALS['TCA']['sys_file_metadata']['ctrl']['languageField']]] = $record;
        }
        return $translations;
    }

    /**
     * Returns TRUE if $ext is an image-extension according to $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']
     *
     * @param string $ext File extension
     * @return bool
     */
    public function isImage($ext)
    {
        return GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], strtolower($ext));
    }

    /**
     * Returns TRUE if $ext is an media-extension according to $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']
     *
     * @param string $ext File extension
     * @return bool
     */
    public function isMediaFile($ext)
    {
        return GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext'], strtolower($ext));
    }

    /**
     * Wraps the directory-titles ($code) in a link to filelist/Modules/Filelist/index.php (id=$path) and sorting commands...
     *
     * @param string $code String to be wrapped
     * @param string $folderIdentifier ID (path)
     * @param string $col Sorting column
     * @return string HTML
     */
    public function linkWrapSort($code, $folderIdentifier, $col)
    {
        $params = ['id' => $folderIdentifier, 'SET' => ['sort' => $col]];

        if ($this->sort === $col) {
            // Check reverse sorting
            $params['SET']['reverse'] = ($this->sortRev ? '0' : '1');
            $sortArrow = $this->iconFactory->getIcon('status-status-sorting-' . ($this->sortRev ? 'desc' : 'asc'), Icon::SIZE_SMALL)->render();
        } else {
            $params['SET']['reverse'] = 0;
            $sortArrow = '';
        }
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        $href = (string)$uriBuilder->buildUriFromRoute('file_FilelistList', $params);
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
            $copyTitle = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.copy'));
            $cutTitle = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.cut'));
            $copyIcon = $this->iconFactory->getIcon('actions-edit-copy', Icon::SIZE_SMALL)->render();
            $cutIcon = $this->iconFactory->getIcon('actions-edit-cut', Icon::SIZE_SMALL)->render();

            if ($isSel === 'copy') {
                $copyIcon = $this->iconFactory->getIcon('actions-edit-copy-release', Icon::SIZE_SMALL)->render();
            } elseif ($isSel === 'cut') {
                $cutIcon = $this->iconFactory->getIcon('actions-edit-cut-release', Icon::SIZE_SMALL)->render();
            }

            $cells[] = '<a class="btn btn-default" href="' . htmlspecialchars($this->clipObj->selUrlFile(
                $fullIdentifier,
                1,
                    $isSel === 'copy'
            )) . '" title="' . $copyTitle . '">' . $copyIcon . '</a>';
            // we can only cut if file can be moved
            if ($fileOrFolderObject->checkActionPermission('move')) {
                $cells[] = '<a class="btn btn-default" href="' . htmlspecialchars($this->clipObj->selUrlFile(
                    $fullIdentifier,
                    0,
                        $isSel === 'cut'
                )) . '" title="' . $cutTitle . '">' . $cutIcon . '</a>';
            } else {
                $cells[] = $this->spaceIcon;
            }
        } else {
            // For numeric pads, add select checkboxes:
            $n = '_FILE|' . $md5;
            $this->CBnames[] = $n;
            $checked = $this->clipObj->isSelected('_FILE', $md5) ? ' checked="checked"' : '';
            $cells[] = '<input type="hidden" name="CBH[' . $n . ']" value="0" /><label class="btn btn-default btn-checkbox"><input type="checkbox" name="CBC[' . $n . ']" value="' . htmlspecialchars($fullIdentifier) . '" ' . $checked . ' /><span class="t3-icon fa"></span></label>';
        }
        // Display PASTE button, if directory:
        $elFromTable = $this->clipObj->elFromTable('_FILE');
        if ($fileOrFolderObject instanceof Folder && !empty($elFromTable) && $fileOrFolderObject->checkActionPermission('write')) {
            $addPasteButton = true;
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
                    . ' data-content="' . htmlspecialchars($this->clipObj->confirmMsgText('_FILE', $fullName, 'into', $elToConfirm)) . '"'
                    . ' data-severity="warning"'
                    . ' data-title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteInto')) . '"'
                    . ' title="' . htmlspecialchars($this->getLanguageService()->getLL('clip_pasteInto')) . '"'
                    . '>'
                    . $this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL)->render()
                    . '</a>';
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
        $md5 = GeneralUtility::shortMD5($fullIdentifier);
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);

        // Edit file content (if editable)
        if ($fileOrFolderObject instanceof File && $fileOrFolderObject->checkActionPermission('write') && GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'], $fileOrFolderObject->getExtension())) {
            $url = (string)$uriBuilder->buildUriFromRoute('file_edit', ['target' => $fullIdentifier]);
            $editOnClick = 'top.list_frame.location.href=' . GeneralUtility::quoteJSvalue($url) . '+\'&returnUrl=\'+top.rawurlencode(top.list_frame.document.location.pathname+top.list_frame.document.location.search);return false;';
            $cells['edit'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($editOnClick) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editcontent') . '">'
                . $this->iconFactory->getIcon('actions-page-open', Icon::SIZE_SMALL)->render()
                . '</a>';
        } else {
            $cells['edit'] = $this->spaceIcon;
        }

        // Edit metadata of file
        if ($fileOrFolderObject instanceof File && $fileOrFolderObject->checkActionPermission('editMeta') && $this->getBackendUser()->check('tables_modify', 'sys_file_metadata')) {
            $metaData = $fileOrFolderObject->_getMetaData();
            $urlParameters = [
                'edit' => [
                    'sys_file_metadata' => [
                        $metaData['uid'] => 'edit'
                    ]
                ],
                'returnUrl' => $this->listURL()
            ];
            $url = (string)$uriBuilder->buildUriFromRoute('record_edit', $urlParameters);
            $title = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.editMetadata'));
            $cells['metadata'] = '<a class="btn btn-default" href="' . htmlspecialchars($url) . '" title="' . $title . '">' . $this->iconFactory->getIcon('actions-open', Icon::SIZE_SMALL)->render() . '</a>';
        }

        // document view
        if ($fileOrFolderObject instanceof File) {
            $fileUrl = $fileOrFolderObject->getPublicUrl(true);
            if ($fileUrl) {
                $aOnClick = 'return top.openUrlInWindow(' . GeneralUtility::quoteJSvalue($fileUrl) . ', \'WebFile\');';
                $cells['view'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($aOnClick) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.view') . '">' . $this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL)->render() . '</a>';
            } else {
                $cells['view'] = $this->spaceIcon;
            }
        } else {
            $cells['view'] = $this->spaceIcon;
        }

        // replace file
        if ($fileOrFolderObject instanceof File && $fileOrFolderObject->checkActionPermission('replace')) {
            $url = (string)$uriBuilder->buildUriFromRoute('file_replace', ['target' => $fullIdentifier, 'uid' => $fileOrFolderObject->getUid()]);
            $replaceOnClick = 'top.list_frame.location.href = ' . GeneralUtility::quoteJSvalue($url) . '+\'&returnUrl=\'+top.rawurlencode(top.list_frame.document.location.pathname+top.list_frame.document.location.search);return false;';
            $cells['replace'] = '<a href="#" class="btn btn-default" onclick="' . $replaceOnClick . '"  title="' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.replace') . '">' . $this->iconFactory->getIcon('actions-edit-replace', Icon::SIZE_SMALL)->render() . '</a>';
        }

        // rename the file
        if ($fileOrFolderObject->checkActionPermission('rename')) {
            $url = (string)$uriBuilder->buildUriFromRoute('file_rename', ['target' => $fullIdentifier]);
            $renameOnClick = 'top.list_frame.location.href = ' . GeneralUtility::quoteJSvalue($url) . '+\'&returnUrl=\'+top.rawurlencode(top.list_frame.document.location.pathname+top.list_frame.document.location.search);return false;';
            $cells['rename'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($renameOnClick) . '"  title="' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.rename') . '">' . $this->iconFactory->getIcon('actions-edit-rename', Icon::SIZE_SMALL)->render() . '</a>';
        } else {
            $cells['rename'] = $this->spaceIcon;
        }

        // upload files
        if ($fileOrFolderObject->getStorage()->checkUserActionPermission('add', 'File') && $fileOrFolderObject->checkActionPermission('write')) {
            if ($fileOrFolderObject instanceof Folder) {
                $url = (string)$uriBuilder->buildUriFromRoute('file_upload', ['target' => $fullIdentifier]);
                $uploadOnClick = 'top.list_frame.location.href = ' . GeneralUtility::quoteJSvalue($url) . '+\'&returnUrl=\'+top.rawurlencode(top.list_frame.document.location.pathname+top.list_frame.document.location.search);return false;';
                $cells['upload'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($uploadOnClick) . '"  title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.upload')) . '">' . $this->iconFactory->getIcon('actions-edit-upload', Icon::SIZE_SMALL)->render() . '</a>';
            }
        }

        if ($fileOrFolderObject->checkActionPermission('read')) {
            $infoOnClick = '';
            if ($fileOrFolderObject instanceof Folder) {
                $infoOnClick = 'top.TYPO3.InfoWindow.showItem(\'_FOLDER\', ' . GeneralUtility::quoteJSvalue($fullIdentifier) . ');return false;';
            } elseif ($fileOrFolderObject instanceof File) {
                $infoOnClick = 'top.TYPO3.InfoWindow.showItem(\'_FILE\', ' . GeneralUtility::quoteJSvalue($fullIdentifier) . ');return false;';
            }
            $cells['info'] = '<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($infoOnClick) . '" title="' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.info') . '">' . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '</a>';
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
                $referenceCountText = BackendUtility::referenceCount('sys_file', $fileOrFolderObject->getUid(), ' ' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.referencesToFile'));
                $deleteType = 'delete_file';
            }

            if ($this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE)) {
                $confirmationCheck = '1';
            } else {
                $confirmationCheck = '0';
            }

            $deleteUrl = (string)$uriBuilder->buildUriFromRoute('tce_file');
            $confirmationMessage = sprintf($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.delete'), $fileOrFolderObject->getName()) . $referenceCountText;
            $title = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete');
            $cells['delete'] = '<a href="#" class="btn btn-default t3js-filelist-delete" data-content="' . htmlspecialchars($confirmationMessage)
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
        // Compile items into a DIV-element:
        return '<div class="btn-group">' . implode('', $cells) . '</div>';
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
                $queryBuilder->expr()->eq('deleted', $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)),
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
            ->fetchColumn();

        return $this->generateReferenceToolTip($referenceCount, '\'_FILE\', ' . GeneralUtility::quoteJSvalue($fileOrFolderObject->getCombinedIdentifier()));
    }

    /**
     * Returns an instance of LanguageService
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

    /**
     * Sets the script url depending on being a module or script request
     */
    protected function determineScriptUrl()
    {
        if ($routePath = GeneralUtility::_GP('route')) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $this->thisScript = (string)$uriBuilder->buildUriFromRoutePath($routePath);
        } else {
            $this->thisScript = GeneralUtility::getIndpEnv('SCRIPT_NAME');
        }
    }

    /**
     * @return string
     */
    protected function getThisScript()
    {
        return strpos($this->thisScript, '?') === false ? $this->thisScript . '?' : $this->thisScript . '&';
    }

    /**
     * Gets an instance of TranslationConfigurationProvider
     *
     * @return TranslationConfigurationProvider
     */
    protected function getTranslateTools()
    {
        if (!isset($this->translateTools)) {
            $this->translateTools = GeneralUtility::makeInstance(TranslationConfigurationProvider::class);
        }
        return $this->translateTools;
    }

    /**
     * Generates HTML code for a Reference tooltip out of
     * sys_refindex records you hand over
     *
     * @param int $references number of records from sys_refindex table
     * @param string $launchViewParameter JavaScript String, which will be passed as parameters to top.TYPO3.InfoWindow.showItem
     * @return string
     */
    protected function generateReferenceToolTip($references, $launchViewParameter = '')
    {
        if (!$references) {
            $htmlCode = '-';
        } else {
            $htmlCode = '<a href="#"';
            if ($launchViewParameter !== '') {
                $htmlCode .= ' onclick="' . htmlspecialchars(
                        'top.TYPO3.InfoWindow.showItem(' . $launchViewParameter . '); return false;'
                    ) . '"';
            }
            $htmlCode .= ' title="' . htmlspecialchars(
                    $this->getLanguageService()->sL(
                        'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:show_references'
                    ) . ' (' . $references . ')'
                ) . '">';
            $htmlCode .= $references;
            $htmlCode .= '</a>';
        }
        return $htmlCode;
    }
}
