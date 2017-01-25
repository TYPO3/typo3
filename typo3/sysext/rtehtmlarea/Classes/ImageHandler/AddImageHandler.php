<?php
namespace TYPO3\CMS\Rtehtmlarea\ImageHandler;

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Tree\View\ElementBrowserFolderTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\FileRepository;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Recordlist\Controller\AbstractLinkBrowserController;
use TYPO3\CMS\Recordlist\LinkHandler\LinkHandlerInterface;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Recordlist\View\FolderUtilityRenderer;
use TYPO3\CMS\Rtehtmlarea\Controller\SelectImageController;

class AddImageHandler implements LinkParameterProviderInterface, LinkHandlerInterface
{
    /**
     * Current mode: One of 'magic' or 'plain'
     *
     * @var string
     */
    protected $mode;

    /**
     * @var SelectImageController
     */
    protected $selectImageController;

    /**
     * Relevant for RTE mode "plain": the maximum width an image must have to be selectable.
     *
     * @var int
     */
    protected $plainMaxWidth;

    /**
     * Relevant for RTE mode "plain": the maximum height an image must have to be selectable.
     *
     * @var int
     */
    protected $plainMaxHeight;

    /**
     * @var string|NULL
     */
    protected $expandFolder;

    /**
     * @var string
     */
    protected $defaultClass;

    /**
     * @var Folder
     */
    protected $selectedFolder;

    /**
     * Holds information about files
     *
     * @var mixed[][]
     */
    protected $elements = [];

    /**
     * @var string
     */
    protected $searchWord;

    /**
     * @var FileRepository
     */
    protected $fileRepository;

    /**
     * URL of current request
     *
     * @var string
     */
    protected $thisScript = '';

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * Initialize the handler
     *
     * @param AbstractLinkBrowserController $linkBrowser
     * @param string $identifier
     * @param array $configuration Page TSconfig
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    public function initialize(AbstractLinkBrowserController $linkBrowser, $identifier, array $configuration)
    {
        $this->fileRepository = GeneralUtility::makeInstance(FileRepository::class);
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);

        $this->expandFolder = GeneralUtility::_GP('expandFolder');
        $this->searchWord = (string)GeneralUtility::_GP('searchWord');

        if ($identifier !== 'plain' && $identifier !== 'magic') {
            throw new \InvalidArgumentException('The given identifier "' . $identifier . '" is not supported by this handler."', 1455499720);
        }
        if (!$linkBrowser instanceof SelectImageController) {
            throw new \InvalidArgumentException('The given $linkBrowser must be of type SelectImageController."', 1455499721);
        }
        $this->mode = $identifier;
        $this->selectImageController = $linkBrowser;

        $buttonConfiguration = $linkBrowser->getButtonConfiguration();
        $this->plainMaxWidth = empty($buttonConfiguration['options.']['plain.']['maxWidth'])
            ? 640
            : $buttonConfiguration['options.']['plain.']['maxWidth'];
        $this->plainMaxHeight = empty($buttonConfiguration['options.']['plain.']['maxHeight'])
            ? 680
            : $buttonConfiguration['options.']['plain.']['maxHeight'];

        $this->getLanguageService()->includeLLFile('EXT:rtehtmlarea/Resources/Private/Language/locallang_selectimagecontroller.xlf');
    }

    /**
     * Render the link handler
     *
     * @param ServerRequestInterface $request
     *
     * @return string
     */
    public function render(ServerRequestInterface $request)
    {
        GeneralUtility::makeInstance(PageRenderer::class)->loadRequireJsModule('TYPO3/CMS/Rtehtmlarea/AddImage');

        $backendUser = $this->getBackendUser();

        // The key number 3 of the bparams contains the "allowed" string. Disallowed is not passed to
        // the element browser at all but only filtered out in TCEMain afterwards
        $bparams = explode('|', $this->selectImageController->getUrlParameters()['bparams']);
        if (isset($bparams[3])) {
            $allowedFileExtensions = GeneralUtility::trimExplode(',', $bparams[3], true);
        } else {
            $allowedFileExtensions = GeneralUtility::trimExplode(
                ',',
                $this->mode === 'plain'
                    ? SelectImageController::PLAIN_MODE_IMAGE_FILE_EXTENSIONS
                    : $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                true
            );
        }
        if (!empty($allowedFileExtensions) && $allowedFileExtensions[0] !== 'sys_file' && $allowedFileExtensions[0] !== '*') {
            // Create new filter object
            $filterObject = GeneralUtility::makeInstance(FileExtensionFilter::class);
            $filterObject->setAllowedFileExtensions($allowedFileExtensions);
            // Set file extension filters on all storages
            $storages = $backendUser->getFileStorages();
            /** @var $storage \TYPO3\CMS\Core\Resource\ResourceStorage */
            foreach ($storages as $storage) {
                $storage->addFileAndFolderNameFilter([$filterObject, 'filterFileList']);
            }
        }
        if ($this->expandFolder) {
            $fileOrFolderObject = null;

            // Try to fetch the folder the user had open the last time he browsed files
            // Fallback to the default folder in case the last used folder is not existing
            try {
                $fileOrFolderObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->expandFolder);
            } catch (Exception $accessException) {
                // We're just catching the exception here, nothing to be done if folder does not exist or is not accessible.
            } catch (\InvalidArgumentException $driverMissingExecption) {
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
                $this->selectedFolder = $backendUser->getDefaultUploadFolder();
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
        $noThumbs = $backendUser->getTSConfigVal('options.noThumbsInRTEimageSelect');
        $_MOD_SETTINGS = [];
        if (!$noThumbs) {
            // MENU-ITEMS, fetching the setting for thumbnails from File>List module:
            $_MOD_MENU = ['displayThumbs' => ''];
            $_MCONF['name'] = 'file_list';
            $_MOD_SETTINGS = BackendUtility::getModuleData($_MOD_MENU, GeneralUtility::_GP('SET'), $_MCONF['name']);
        }
        $noThumbs = $noThumbs ?: !$_MOD_SETTINGS['displayThumbs'];
        // Create folder tree:
        /** @var ElementBrowserFolderTreeView $folderTree */
        $folderTree = GeneralUtility::makeInstance(ElementBrowserFolderTreeView::class);
        $folderTree->setLinkParameterProvider($this);
        $tree = $folderTree->getBrowsableTree();
        if ($this->selectedFolder) {
            $files = $this->renderFilesInFolder($this->selectedFolder, $allowedFileExtensions, $noThumbs);
        } else {
            $files = '';
        }

        $content = '';
        // Insert the upload form on top, if so configured
        if ($backendUser->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
            $content .= $uploadForm;
        }
        // Putting the parts together, side by side:
        $content .= '

			<!--
				Wrapper table for folder tree / filelist:
			-->
			<div class="element-browser-section element-browser-filetree">
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-EBfiles">
				<tr>
					<td class="c-wCell" valign="top"><h3>' . $this->getLanguageService()->getLL('folderTree', true) . ':</h3>' . $tree . '</td>
					<td class="c-wCell" valign="top">' . $files . '</td>
				</tr>
			</table>
			</div>
			';
        // Add help message
        switch ($this->mode) {
            case 'plain':
                $content .= sprintf($this->getLanguageService()->getLL('plainImage_msg'), $this->plainMaxWidth, $this->plainMaxHeight);
                break;
            case 'magic':
                $content .= sprintf($this->getLanguageService()->getLL('magicImage_msg'));
                break;
        }
        // Adding create folder + upload forms if applicable:
        if (!$backendUser->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
            $content .= $uploadForm;
        }
        $content .= $createFolder;
        // Add some space
        $content .= '<br /><br />';

        return $content;
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
            $files = $this->fileRepository->searchByName($folder, $this->searchWord);
        } else {
            $extensionList = !empty($extensionList) && $extensionList[0] === '*' ? [] : $extensionList;
            $files = $this->getFilesInFolder($folder, $extensionList);
        }
        $filesCount = count($files);

        $lines = [];

        // Create the header of current folder:
        $folderIcon = $this->iconFactory->getIconForResource($folder, Icon::SIZE_SMALL);

        $lines[] = '
			<tr class="t3-row-header">
				<th class="col-title" nowrap="nowrap">' . $folderIcon . ' ' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($folder->getIdentifier(), $titleLen)) . '</th>
				<th class="col-control" nowrap="nowrap"></th>
				<th class="col-clipboard" nowrap="nowrap">
					<a href="#" class="btn btn-default" id="t3js-importSelection" title="' . $lang->getLL('importSelection', true) . '">' . $this->iconFactory->getIcon('actions-document-import-t3d', Icon::SIZE_SMALL) . '</a>
					<a href="#" class="btn btn-default" id="t3js-toggleSelection" title="' . $lang->getLL('toggleSelection', true) . '">' . $this->iconFactory->getIcon('actions-document-select', Icon::SIZE_SMALL) . '</a>
				</th>
				<th nowrap="nowrap">&nbsp;</th>
			</tr>';

        if ($filesCount === 0) {
            $lines[] = '
				<tr>
					<td colspan="4">No files found.</td>
				</tr>';
        }

        foreach ($files as $fileObject) {
            $fileExtension = $fileObject->getExtension();
            // Thumbnail/size generation:
            $imgInfo = [];
            if (!$noThumbs && GeneralUtility::inList(strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'] . ',' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['mediafile_ext']), strtolower($fileExtension))) {
                $processedFile = $fileObject->process(
                    ProcessedFile::CONTEXT_IMAGEPREVIEW,
                    ['width' => 64, 'height' => 64]
                );
                $imageUrl = $processedFile->getPublicUrl(true);
                $imgInfo = [
                    $fileObject->getProperty('width'),
                    $fileObject->getProperty('height')
                ];
                $pDim = $imgInfo[0] . 'x' . $imgInfo[1] . ' pixels';
                $clickIcon = '<img src="' . $imageUrl . '"'
                             . ' width="' . $processedFile->getProperty('width') . '"'
                             . ' height="' . $processedFile->getProperty('height') . '"'
                             . ' hspace="5" vspace="5" border="1" />';
            } else {
                $clickIcon = '';
                $pDim = '';
            }
            // Create file icon:
            $size = ' (' . GeneralUtility::formatSize($fileObject->getSize()) . 'bytes' . ($pDim ? ', ' . $pDim : '') . ')';
            $icon = '<span title="' . htmlspecialchars($fileObject->getName() . $size) . '">' . $this->iconFactory->getIconForResource($fileObject, Icon::SIZE_SMALL) . '</span>';
            // Create links for adding the file:
            $filesIndex = count($this->elements);
            $this->elements['file_' . $filesIndex] = [
                'type' => 'file',
                'table' => 'sys_file',
                'uid' => $fileObject->getUid(),
                'fileName' => $fileObject->getName(),
                'filePath' => $fileObject->getUid(),
                'fileExt' => $fileExtension,
                'fileIcon' => $icon
            ];
            if ($this->fileIsSelectableInFileList($fileObject, $imgInfo)) {
                $ATag = '<a href="#" class="btn btn-default" title="' . htmlspecialchars($fileObject->getName()) . '" data-file-index="' . htmlspecialchars($filesIndex) . '" data-close="0">';
                $ATag_alt = '<a href="#" title="' . htmlspecialchars($fileObject->getName()) . '" data-file-index="' . htmlspecialchars($filesIndex) . '" data-close="1">';
                $ATag_e = '</a>';
                $bulkCheckBox = '<label class="btn btn-default btn-checkbox"><input type="checkbox" class="typo3-bulk-item" name="file_' . $filesIndex . '" value="0" /><span class="t3-icon fa"></span></label>';
            } else {
                $ATag = '';
                $ATag_alt = '';
                $ATag_e = '';
                $bulkCheckBox = '';
            }
            // Create link to showing details about the file in a window:
            $Ahref = BackendUtility::getModuleUrl('show_item', [
                'type' => 'file',
                'table' => '_FILE',
                'uid' => $fileObject->getCombinedIdentifier(),
                'returnUrl' => GeneralUtility::getIndpEnv('REQUEST_URI')
            ]);

            // Combine the stuff:
            $filenameAndIcon = $ATag_alt . $icon . htmlspecialchars(GeneralUtility::fixed_lgd_cs($fileObject->getName(), $titleLen)) . $ATag_e;
            // Show element:
            $lines[] = '
					<tr class="file_list_normal">
						<td class="col-title" nowrap="nowrap">' . $filenameAndIcon . '&nbsp;</td>
						<td class="col-control">
							<div class="btn-group">' . $ATag . '<span title="' . $lang->getLL('addToList', true) . '">' . $this->iconFactory->getIcon('actions-edit-add', Icon::SIZE_SMALL)->render() . '</span>' . $ATag_e . '
							<a href="' . htmlspecialchars($Ahref) . '" class="btn btn-default" title="' . $lang->getLL('info', true) . '">' . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL) . '</a>
						</td>
						<td class="col-clipboard" valign="top">' . $bulkCheckBox . '</td>
						<td nowrap="nowrap">&nbsp;' . $pDim . '</td>
					</tr>';
            if ($pDim) {
                $lines[] = '
					<tr>
						<td class="filelistThumbnail" colspan="4">' . $ATag_alt . $clickIcon . $ATag_e . '</td>
					</tr>';
            }
        }

        $out = '<h3>' . $lang->getLL('files', true) . ' ' . $filesCount . ':</h3>';
        $out .= GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this)->getFileSearchField($this->searchWord);
        $out .= '<div id="filelist">';
        $out .= $this->getBulkSelector($filesCount);

        // Wrap all the rows in table tags:
        $out .= '

	<!--
		Filelisting
	-->
			<table class="table table-striped table-hover" id="typo3-filelist">
				' . implode('', $lines) . '
			</table>';
        // Return accumulated content for filelisting:
        $out .= '</div>';
        return $out;
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
     * @param int $filesCount Number of files currently displayed
     * @return string HTML data required for a bulk selection of files - if $filesCount is 0, nothing is returned
     */
    protected function getBulkSelector($filesCount)
    {
        if (!$filesCount) {
            return '';
        }

        $lang = $this->getLanguageService();
        $out = '';

        // Getting flag for showing/not showing thumbnails:
        $noThumbsInEB = $this->getBackendUser()->getTSConfigVal('options.noThumbsInEB');
        if (!$noThumbsInEB && $this->selectedFolder) {
            // MENU-ITEMS, fetching the setting for thumbnails from File>List module:
            $_MOD_MENU = ['displayThumbs' => ''];
            $_MCONF['name'] = 'file_list';
            $_MOD_SETTINGS = BackendUtility::getModuleData($_MOD_MENU, GeneralUtility::_GP('SET'), $_MCONF['name']);
            $addParams = GeneralUtility::implodeArrayForUrl('', $this->getUrlParameters(['identifier' => $this->selectedFolder->getCombinedIdentifier()]));
            $thumbNailCheck = '<div class="checkbox" style="padding:5px 0 15px 0"><label for="checkDisplayThumbs">'
                              . BackendUtility::getFuncCheck(
                    '',
                    'SET[displayThumbs]',
                    $_MOD_SETTINGS['displayThumbs'],
                    $this->thisScript,
                    $addParams,
                    'id="checkDisplayThumbs"'
                )
                              . $lang->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:displayThumbs', true) . '</label></div>';
            $out .= $thumbNailCheck;
        } else {
            $out .= '<div style="padding-top: 15px;"></div>';
        }
        return $out;
    }

    /**
     * Checks if the given file is selectable in the filelist.
     *
     * In "plain" RTE mode only image files with a maximum width and height are selectable.
     *
     * @param FileInterface $file
     * @param array $imgInfo Image dimensions from \TYPO3\CMS\Core\Imaging\GraphicalFunctions::getImageDimensions()
     * @return bool TRUE if file is selectable.
     */
    protected function fileIsSelectableInFileList(FileInterface $file, array $imgInfo)
    {
        return $this->mode !== 'plain'
               || (GeneralUtility::inList(SelectImageController::PLAIN_MODE_IMAGE_FILE_EXTENSIONS, strtolower($file->getExtension()))
                && $imgInfo[0] <= $this->plainMaxWidth && $imgInfo[1] <= $this->plainMaxHeight);
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes()
    {
        return [
            'data-elements' => json_encode($this->elements)
        ];
    }

    /**
     * Returns the URL of the current script
     *
     * @return string
     */
    public function getScriptUrl()
    {
        return $this->selectImageController->getScriptUrl();
    }

    /**
     * Provides an array or GET parameters for URL generation
     *
     * @param array $values Array of values to include into the parameters or which might influence the parameters
     *
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values)
    {
        $parameters = [
            'expandFolder' => isset($values['identifier']) ? $values['identifier'] : (string)$this->expandFolder
        ];
        return array_merge($this->selectImageController->getUrlParameters($values), $parameters);
    }

    /**
     * Return TRUE if the handler supports to update a link.
     *
     * This is useful for file or page links, when only attributes are changed.
     *
     * @return bool
     */
    public function isUpdateSupported()
    {
        return false;
    }

    /**
     * @return array
     */
    public function getLinkAttributes()
    {
        return [];
    }

    /**
     * @param string[] $fieldDefinitions Array of link attribute field definitions
     * @return string[]
     */
    public function modifyLinkAttributes(array $fieldDefinitions)
    {
        return $fieldDefinitions;
    }

    /**
     * Check if given value is currently the selected item
     *
     * This method is only used in the page tree.
     *
     * @param array $values Values to be checked
     *
     * @return bool Returns TRUE if the given values match the currently selected item
     */
    public function isCurrentlySelectedItem(array $values)
    {
        return false;
    }

    /**
     * Checks if this is the handler for the given link
     *
     * The handler may store this information locally for later usage.
     *
     * @param array $linkParts Link parts as returned from TypoLinkCodecService
     *
     * @return bool
     */
    public function canHandleLink(array $linkParts)
    {
        return false;
    }

    /**
     * Format the current link for HTML output
     *
     * @return string
     */
    public function formatCurrentUrl()
    {
        return '';
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
