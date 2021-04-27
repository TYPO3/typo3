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

namespace TYPO3\CMS\Recordlist\Browser;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Recordlist\View\FolderUtilityRenderer;
use TYPO3\CMS\Recordlist\View\RecordSearchBoxComponent;

/**
 * Browser for files
 * @internal This class is a specific LinkBrowser implementation and is not part of the TYPO3's Core API.
 */
class FileBrowser extends AbstractElementBrowser implements ElementBrowserInterface, LinkParameterProviderInterface
{
    /**
     * When you click a folder name/expand icon to see the content of a certain file folder,
     * this value will contain the path of the expanded file folder.
     * If the value is NOT set, then it will be restored from the module session data.
     * Example value: "/www/htdocs/typo3/32/3dsplm/fileadmin/css/"
     *
     * @var string|null
     */
    protected $expandFolder;

    /**
     * @var Folder
     */
    protected $selectedFolder;

    /**
     * @var string
     */
    protected $searchWord;

    /**
     * @var array
     */
    protected $thumbnailConfiguration = [];

    /**
     * Loads additional JavaScript
     */
    protected function initialize()
    {
        parent::initialize();
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/BrowseFiles');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tree/FileStorageBrowser');

        $thumbnailConfig = $this->getBackendUser()->getTSConfig()['options.']['file_list.']['thumbnail.'] ?? [];
        if (isset($thumbnailConfig['width']) && MathUtility::canBeInterpretedAsInteger($thumbnailConfig['width'])) {
            $this->thumbnailConfiguration['width'] = (int)$thumbnailConfig['width'];
        }
        if (isset($thumbnailConfig['height']) && MathUtility::canBeInterpretedAsInteger($thumbnailConfig['height'])) {
            $this->thumbnailConfiguration['height'] = (int)$thumbnailConfig['height'];
        }
    }

    /**
     * Checks additional GET/POST requests
     */
    protected function initVariables()
    {
        parent::initVariables();
        $this->expandFolder = $this->getRequest()->getParsedBody()['expandFolder'] ?? $this->getRequest()->getQueryParams()['expandFolder'] ?? null;
        $this->searchWord = $this->getRequest()->getParsedBody()['search_field'] ?? $this->getRequest()->getQueryParams()['search_field'] ?? '';
    }

    /**
     * Session data for this class can be set from outside with this method.
     *
     * @param mixed[] $data Session data array
     * @return array[] Session data and boolean which indicates that data needs to be stored in session because it's changed
     */
    public function processSessionData($data)
    {
        if ($this->expandFolder !== null) {
            $data['expandFolder'] = $this->expandFolder;
            $store = true;
        } else {
            $this->expandFolder = $data['expandFolder'];
            $store = false;
        }
        return [$data, $store];
    }

    /**
     * @return string HTML content
     */
    public function render()
    {
        $_MCONF = [];
        $backendUser = $this->getBackendUser();

        // The key number 3 of the bparams contains the "allowed" string. Disallowed is not passed to
        // the element browser at all but only filtered out in DataHandler afterwards
        $allowedFileExtensions = GeneralUtility::trimExplode(',', explode('|', $this->bparams)[3], true);
        if (!empty($allowedFileExtensions) && $allowedFileExtensions[0] !== 'sys_file' && $allowedFileExtensions[0] !== '*') {
            // Create new filter object
            $filterObject = GeneralUtility::makeInstance(FileExtensionFilter::class);
            $filterObject->setAllowedFileExtensions($allowedFileExtensions);
            // Set file extension filters on all storages
            $storages = $backendUser->getFileStorages();
            /** @var \TYPO3\CMS\Core\Resource\ResourceStorage $storage */
            foreach ($storages as $storage) {
                $storage->addFileAndFolderNameFilter([$filterObject, 'filterFileList']);
            }
        }
        if ($this->expandFolder) {
            $fileOrFolderObject = null;

            // Try to fetch the folder the user had open the last time he browsed files
            // Fallback to the default folder in case the last used folder is not existing
            try {
                $fileOrFolderObject = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($this->expandFolder);
            } catch (Exception $accessException) {
                // We're just catching the exception here, nothing to be done if folder does not exist or is not accessible.
            } catch (\InvalidArgumentException $driverMissingException) {
                // We're just catching the exception here, nothing to be done if the driver does not exist anymore.
            }

            if ($fileOrFolderObject instanceof Folder) {
                // It's a folder
                $this->selectedFolder = $fileOrFolderObject;
            } elseif ($fileOrFolderObject instanceof FileInterface) {
                // It's a file
                $this->selectedFolder = $fileOrFolderObject->getParentFolder();
            }
        }
        // Or get the user's default upload folder
        if (!$this->selectedFolder) {
            try {
                [, $pid, $table,, $field] = explode('-', explode('|', $this->bparams)[4]);
                $this->selectedFolder = $backendUser->getDefaultUploadFolder($pid, $table, $field);
            } catch (\Exception $e) {
                // The configured default user folder does not exist
            }
        }
        // Build the file upload and folder creation form
        $uploadForm = '';
        $createFolder = '';
        if ($this->selectedFolder) {
            $folderUtilityRenderer = GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this);
            $uploadForm = $folderUtilityRenderer->uploadForm($this->selectedFolder, $allowedFileExtensions);
            $createFolder = $folderUtilityRenderer->createFolder($this->selectedFolder);
        }

        // Getting flag for showing/not showing thumbnails:
        $noThumbs = $backendUser->getTSConfig()['options.']['noThumbsInEB'] ?? false;
        $_MOD_SETTINGS = [];
        if (!$noThumbs) {
            // MENU-ITEMS, fetching the setting for thumbnails from File>List module:
            $_MOD_MENU = ['displayThumbs' => ''];
            $_MCONF['name'] = 'file_list';
            $_MOD_SETTINGS = BackendUtility::getModuleData($_MOD_MENU, GeneralUtility::_GP('SET'), $_MCONF['name']);
        }
        $displayThumbs = $_MOD_SETTINGS['displayThumbs'] ?? false;
        $noThumbs = $noThumbs ?: !$displayThumbs;
        if ($this->selectedFolder) {
            $files = $this->renderFilesInFolder($this->selectedFolder, $allowedFileExtensions, $noThumbs);
        } else {
            $files = '';
        }
        $contentOnly = (bool)($this->getRequest()->getQueryParams()['contentOnly'] ?? false);

        $this->setBodyTagParameters();
        $this->moduleTemplate->setTitle($this->getLanguageService()->getLL('fileSelector'));
        $view = $this->moduleTemplate->getView();
        $view->assignMultiple([
            'treeEnabled' => true,
            'treeType' => 'folder',
            'activeFolder' => $this->selectedFolder,
            'initialNavigationWidth' => $this->getBackendUser()->uc['selector']['navigation']['width'] ?? 250,
            'content' => $files . $uploadForm . $createFolder,
            'contentOnly' => $contentOnly,
        ]);
        if ($contentOnly) {
            return $view->render();
        }
        return $this->moduleTemplate->renderContent();
    }

    /**
     * For TYPO3 Element Browser: Expand folder of files.
     *
     * @param Folder $folder The folder path to expand
     * @param array $extensionList List of fileextensions to show
     * @param bool $noThumbs Whether to show thumbnails or not. If set, no thumbnails are shown.
     * @return string HTML output
     */
    public function renderFilesInFolder(Folder $folder, array $extensionList = [], $noThumbs = false)
    {
        if (!$folder->checkActionPermission('read')) {
            return '';
        }
        $lang = $this->getLanguageService();
        $titleLen = (int)$this->getBackendUser()->uc['titleLen'];

        if ($this->searchWord !== '') {
            $searchDemand = FileSearchDemand::createForSearchTerm($this->searchWord)->withRecursive();
            $files = $folder->searchFiles($searchDemand);
        } else {
            $extensionList = !empty($extensionList) && $extensionList[0] === '*' ? [] : $extensionList;
            $files = $this->getFilesInFolder($folder, $extensionList);
        }
        if (empty($files)) {
            return '<div class="shadow-sm bg-info bg-gradient p-4 pb-2 pt-2 mb-3">' . sprintf(htmlspecialchars($lang->sL('LLL:EXT:recordlist/Resources/Private/Language/locallang_browse_links.xlf:no_files')), $folder->getStorage()->getName() . ':' . $folder->getReadablePath()) . '</div>';
        }
        $lines = [];

        // Create the header of current folder:
        $folderIcon = $this->iconFactory->getIconForResource($folder, Icon::SIZE_SMALL);

        $lines[] = '
			<tr>
				<th class="col-title nowrap">' . $folderIcon . ' ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($folder->getStorage()->getName() . ':' . $folder->getReadablePath(), $titleLen)) . '</th>
				<th class="col-control nowrap"></th>
				<th class="col-clipboard nowrap">
					<a href="#" class="btn btn-default disabled" id="t3js-importSelection" title="' . htmlspecialchars($lang->getLL('importSelection')) . '">' . $this->iconFactory->getIcon('actions-document-import-t3d', Icon::SIZE_SMALL) . '</a>
					<a href="#" class="btn btn-default" id="t3js-toggleSelection" title="' . htmlspecialchars($lang->getLL('toggleSelection')) . '">' . $this->iconFactory->getIcon('actions-document-select', Icon::SIZE_SMALL) . '</a>
				</th>
			</tr>';

        foreach ($files as $fileObject) {
            // Thumbnail/size generation:
            $imgInfo = [];
            if (!$noThumbs && ($fileObject->isMediaFile() || $fileObject->isImage())) {
                $processedFile = $fileObject->process(
                    ProcessedFile::CONTEXT_IMAGEPREVIEW,
                    $this->thumbnailConfiguration
                );
                $imageUrl = $processedFile->getPublicUrl();
                $imgInfo = [
                    $fileObject->getProperty('width'),
                    $fileObject->getProperty('height')
                ];
                $pDim = $imgInfo[0] . 'x' . $imgInfo[1] . ' pixels';
                $clickIcon = '<img src="' . PathUtility::getAbsoluteWebPath($imageUrl) . '"'
                    . ' width="' . $processedFile->getProperty('width') . '"'
                    . ' height="' . $processedFile->getProperty('height') . '" class="me-1" />';
            } else {
                $clickIcon = '';
                $pDim = '';
            }
            // Create file icon:
            $size = ' (' . GeneralUtility::formatSize($fileObject->getSize(), $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:byteSizeUnits')) . ($pDim ? ', ' . $pDim : '') . ')';
            $icon = '<span title="id=' . htmlspecialchars($fileObject->getUid()) . '">' . $this->iconFactory->getIconForResource($fileObject, Icon::SIZE_SMALL) . '</span>';
            if ($this->fileIsSelectableInFileList($fileObject, $imgInfo)) {
                $ATag = '<a href="#" class="btn btn-default" title="' . htmlspecialchars($fileObject->getName()) . '" data-file-name="' . htmlspecialchars($fileObject->getName()) . '" data-file-uid="' . $fileObject->getUid() . '" data-close="0">';
                $ATag .= '<span title="' . htmlspecialchars($lang->getLL('addToList')) . '">' . $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)->render() . '</span>';
                $ATag_alt = '<a href="#" title="' . htmlspecialchars($fileObject->getName()) . $size . '" data-file-name="' . htmlspecialchars($fileObject->getName()) . '" data-file-uid="' . $fileObject->getUid() . '" data-close="1">';
                $ATag_e = '</a>';
                $bulkCheckBox = '<label class="mb-0 btn btn-default btn-checkbox"><input type="checkbox" class="typo3-bulk-item" data-file-name="' . htmlspecialchars($fileObject->getName()) . '" data-file-uid="' . $fileObject->getUid() . '" name="file_' . $fileObject->getUid() . '" value="0" /><span class="t3-icon fa"></span></label>';
            } else {
                $ATag = '';
                $ATag_alt = '';
                $ATag_e = '';
                $bulkCheckBox = '';
            }
            // Create link to showing details about the file in a window:
            $Ahref = (string)$this->uriBuilder->buildUriFromRoute('show_item', [
                'type' => 'file',
                'table' => '_FILE',
                'uid' => $fileObject->getCombinedIdentifier(),
                'returnUrl' => $this->getRequest()->getAttribute('normalizedParams')->getRequestUri()
            ]);

            // Combine the stuff:
            $filenameAndIcon = $ATag_alt . $icon . htmlspecialchars(GeneralUtility::fixed_lgd_cs($fileObject->getName(), $titleLen)) . $ATag_e;
            // Show element:
            $lines[] = '
					<tr>
						<td class="col-title nowrap">' . $filenameAndIcon . '</td>
						<td class="col-control">
							<div class="btn-group">' . $ATag . $ATag_e . '
							<a href="' . htmlspecialchars($Ahref) . '" class="btn btn-default" title="' . htmlspecialchars($lang->getLL('info')) . '">' . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL) . '</a>
						</td>
						<td class="col-clipboard">' . $bulkCheckBox . '</td>
					</tr>';
            if ($pDim) {
                $lines[] = '
					<tr>
						<td colspan="3">' . $ATag_alt . $clickIcon . $ATag_e . $pDim . '</td>
					</tr>';
            }
        }

        $formUrl = $this->getScriptUrl() . HttpUtility::buildQueryString($this->getUrlParameters([]), '&');
        $searchBox = GeneralUtility::makeInstance(RecordSearchBoxComponent::class)
            ->setSearchWord($this->searchWord)
            ->render($formUrl);

        $markup = [];
        $markup[] = '<div class="pt-2 pb-3">' . $searchBox . '</div>';
        $markup[] = '<div id="filelist">';
        $markup[] = '   ' . $this->getBulkSelector();
        $markup[] = '     <table class="table table-sm table-responsive table-striped table-hover" id="typo3-filelist">';
        $markup[] = '         ' . implode('', $lines);
        $markup[] = '     </table>';
        $markup[] = ' </div>';
        return implode('', $markup);
    }

    /**
     * Get a list of Files in a folder filtered by extension
     *
     * @param Folder $folder
     * @param array $extensionList
     * @return File[]
     */
    protected function getFilesInFolder(Folder $folder, array $extensionList)
    {
        if (!empty($extensionList)) {
            /** @var FileExtensionFilter $filter */
            $filter = GeneralUtility::makeInstance(FileExtensionFilter::class);
            $filter->setAllowedFileExtensions($extensionList);
            $folder->setFileAndFolderNameFilters([[$filter, 'filterFileList']]);
        }
        return $folder->getFiles();
    }

    /**
     * Get the HTML data required for a bulk selection of files of the TYPO3 Element Browser.
     *
     * @return string HTML data required for a bulk selection of files
     */
    protected function getBulkSelector(): string
    {
        $_MCONF = [];

        $lang = $this->getLanguageService();
        $out = '';

        // Getting flag for showing/not showing thumbnails:
        $noThumbsInEB = $this->getBackendUser()->getTSConfig()['options.']['noThumbsInEB'] ?? false;
        if (!$noThumbsInEB && $this->selectedFolder) {
            // MENU-ITEMS, fetching the setting for thumbnails from File>List module:
            $_MOD_MENU = ['displayThumbs' => ''];
            $_MCONF['name'] = 'file_list';
            $_MOD_SETTINGS = BackendUtility::getModuleData($_MOD_MENU, GeneralUtility::_GP('SET'), $_MCONF['name']);
            $addParams = HttpUtility::buildQueryString($this->getUrlParameters(['identifier' => $this->selectedFolder->getCombinedIdentifier()]), '&');
            $thumbNailCheck = '<div class="form-check form-switch">'
                . BackendUtility::getFuncCheck(
                    '',
                    'SET[displayThumbs]',
                    $_MOD_SETTINGS['displayThumbs'],
                    $this->thisScript,
                    $addParams,
                    'id="checkDisplayThumbs"'
                )
                . '<label for="checkDisplayThumbs" class="form-check-label">'
                . htmlspecialchars($lang->sL('LLL:EXT:recordlist/Resources/Private/Language/locallang_browse_links.xlf:displayThumbs')) . '</label></div>';
            $out .= '<div class="pt-2 float-end">' . $thumbNailCheck . '</div>';
        } else {
            $out .= '<div class="pt-2"></div>';
        }
        return $out;
    }

    /**
     * Checks if the given file is selectable in the filelist.
     *
     * By default all files are selectable. This method may be overwritten in child classes.
     *
     * @param FileInterface $file
     * @param mixed[] $imgInfo Image dimensions from \TYPO3\CMS\Core\Imaging\GraphicalFunctions::getImageDimensions()
     * @return bool TRUE if file is selectable.
     */
    protected function fileIsSelectableInFileList(FileInterface $file, array $imgInfo)
    {
        return true;
    }

    /**
     * @param array $values Array of values to include into the parameters
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values)
    {
        return [
            'mode' => 'file',
            'expandFolder' => $values['identifier'] ?? $this->expandFolder,
            'bparams' => $this->bparams
        ];
    }

    /**
     * @param array $values Values to be checked
     * @return bool Returns TRUE if the given values match the currently selected item
     */
    public function isCurrentlySelectedItem(array $values)
    {
        return false;
    }

    /**
     * Returns the URL of the current script
     *
     * @return string
     */
    public function getScriptUrl()
    {
        return $this->thisScript;
    }
}
