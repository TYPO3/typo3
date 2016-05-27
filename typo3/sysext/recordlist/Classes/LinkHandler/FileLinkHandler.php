<?php
namespace TYPO3\CMS\Recordlist\LinkHandler;

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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Recordlist\View\FolderUtilityRenderer;

/**
 * Link handler for files
 */
class FileLinkHandler extends AbstractLinkHandler implements LinkHandlerInterface, LinkParameterProviderInterface
{
    /**
     * Parts of the current link
     *
     * @var array
     */
    protected $linkParts = [];

    /**
     * @var string
     */
    protected $expectedClass = File::class;

    /**
     * @var string
     */
    protected $mode = 'file';

    /**
     * @var string
     */
    protected $expandFolder;

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
        if (!$linkParts['url']) {
            return false;
        }
        $url = rawurldecode($linkParts['url']);

        if (StringUtility::beginsWith($url, 'file:') && !StringUtility::beginsWith($url, 'file://')) {
            $rel = substr($url, 5);
            try {
                // resolve FAL-api "file:UID-of-sys_file-record" and "file:combined-identifier"
                $fileOrFolderObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($rel);
                if (is_a($fileOrFolderObject, $this->expectedClass)) {
                    $this->linkParts = $linkParts;
                    $this->linkParts['url'] = $rel;
                    $this->linkParts['name'] = $fileOrFolderObject->getName();
                    return true;
                }
            } catch (FileDoesNotExistException $e) {
            }
        }
        return false;
    }

    /**
     * Format the current link for HTML output
     *
     * @return string
     */
    public function formatCurrentUrl()
    {
        return $this->linkParts['name'];
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
        GeneralUtility::makeInstance(PageRenderer::class)->loadRequireJsModule('TYPO3/CMS/Recordlist/FileLinkHandler');

        $this->expandFolder = isset($request->getQueryParams()['expandFolder']) ? $request->getQueryParams()['expandFolder'] : null;
        if (!empty($this->linkParts) && !isset($this->expandFolder)) {
            $this->expandFolder = $this->linkParts['url'];
        }

        /** @var ElementBrowserFolderTreeView $folderTree */
        $folderTree = GeneralUtility::makeInstance(ElementBrowserFolderTreeView::class);
        $folderTree->setLinkParameterProvider($this);
        $this->view->assign('tree', $folderTree->getBrowsableTree());

        // Create upload/create folder forms, if a path is given
        $selectedFolder = $this->getSelectedFolder($this->expandFolder);

        // Build the file upload and folder creation form
        if ($selectedFolder) {
            $folderUtilityRenderer = GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this);
            $uploadForm = $this->mode === 'file' ? $folderUtilityRenderer->uploadForm($selectedFolder, []) : '';
            $createFolder = $folderUtilityRenderer->createFolder($selectedFolder);

            // Insert the upload form on top, if so configured
            $positionOfUploadFieldsOnTop = $this->getBackendUser()->getTSConfigVal('options.uploadFieldsInTopOfEB');
            $this->view->assign('positionOfUploadFields', $positionOfUploadFieldsOnTop ? 'top' : 'bottom');
            $this->view->assign('uploadFileForm', $uploadForm);
            $this->view->assign('createFolderForm', $createFolder);

            // Render the file or folderlist
            if ($selectedFolder->checkActionPermission('read')) {
                $this->view->assign('selectedFolder', $selectedFolder);
                $parameters = $this->linkBrowser->getUrlParameters();
                $allowedExtensions = isset($parameters['allowedExtensions']) ? $parameters['allowedExtensions'] : '';
                $this->expandFolder($selectedFolder, $allowedExtensions);
            }
        }

        return $this->view->render(ucfirst($this->mode));
    }

    /**
     * For RTE: This displays all files from folder. No thumbnails shown
     *
     * @param Folder $folder The folder path to expand
     * @param string $extensionList List of file extensions to show
     * @return string HTML output
     */
    public function expandFolder(Folder $folder, $extensionList = '')
    {
        // Create header element; The folder from which files are listed.
        $folderIcon = $this->iconFactory->getIconForResource($folder, Icon::SIZE_SMALL)->render();
        $this->view->assign('selectedFolderIcon', $folderIcon);
        $this->view->assign('selectedFolderTitle', GeneralUtility::fixed_lgd_cs($folder->getIdentifier(), (int)$this->getBackendUser()->uc['titleLen']));
        $this->view->assign('currentIdentifier', !empty($this->linkParts) ? $this->linkParts['url'] : '');

        // Get files from the folder:
        $fileObjects = $this->getFolderContent($folder, $extensionList);
        $itemsInSelectedFolder = [];
        if (!empty($fileObjects)) {
            foreach ($fileObjects as $fileOrFolderObject) {
                $itemsInSelectedFolder[] = $this->renderItem($fileOrFolderObject);
            }
        }
        $this->view->assign('itemsInSelectedFolder', $itemsInSelectedFolder);
    }


    /**
     * @param Folder $folder
     * @param string $extensionList
     *
     * @return FileInterface[]
     */
    protected function getFolderContent(Folder $folder, $extensionList)
    {
        if ($extensionList !== '') {
            /** @var FileExtensionFilter $filter */
            $filter = GeneralUtility::makeInstance(FileExtensionFilter::class);
            $filter->setAllowedFileExtensions($extensionList);
            $folder->setFileAndFolderNameFilters(array(array($filter, 'filterFileList')));
        }
        return $folder->getFiles();
    }

    /**
     * Renders a single item displayed in the current folder
     *
     * @param ResourceInterface $fileOrFolderObject
     *
     * @return array
     * @throws \InvalidArgumentException
     */
    protected function renderItem(ResourceInterface $fileOrFolderObject)
    {
        if (!$fileOrFolderObject instanceof File) {
            throw new \InvalidArgumentException('Expected File object, got "' . get_class($fileOrFolderObject) . '" object.', 1443651368);
        }
        // Get size and icon:
        $size = GeneralUtility::formatSize(
            $fileOrFolderObject->getSize(),
            $this->getLanguageService()->sL('LLL:EXT:lang/locallang_common.xlf:byteSizeUnits')
        );

        return [
            'icon' => $this->iconFactory->getIconForResource($fileOrFolderObject, Icon::SIZE_SMALL)->render(),
            'uid'  => $fileOrFolderObject->getUid(),
            'size' => $size,
            'name' => $fileOrFolderObject->getName(),
            'title' => GeneralUtility::fixed_lgd_cs($fileOrFolderObject->getName(), (int)$this->getBackendUser()->uc['titleLen'])
        ];
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes()
    {
        return [
            'data-current-link' => empty($this->linkParts) ? '' : 'file:' . $this->linkParts['url']
        ];
    }

    /**
     * @param array $values Array of values to include into the parameters or which might influence the parameters
     *
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values)
    {
        $parameters = [
            'expandFolder' => isset($values['identifier']) ? $values['identifier'] : (string)$this->expandFolder
        ];
        return array_merge($this->linkBrowser->getUrlParameters($values), $parameters);
    }

    /**
     * @param array $values Values to be checked
     *
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
        return $this->linkBrowser->getScriptUrl();
    }

    /**
     * Returns the currently selected folder, or th default upload folder
     *
     * @param string $folderIdentifier
     * @return mixed the folder object or false if nothing was found
     */
    protected function getSelectedFolder($folderIdentifier = '') {
        $selectedFolder = false;
        if ($folderIdentifier) {
            try {
                $fileOrFolderObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($folderIdentifier);
                if ($fileOrFolderObject instanceof Folder) {
                    // It's a folder
                    $selectedFolder = $fileOrFolderObject;
                } elseif ($fileOrFolderObject instanceof FileInterface) {
                    // It's a file
                    try {
                        $selectedFolder = $fileOrFolderObject->getParentFolder();
                    } catch (\Exception $e) {
                        // Accessing the parent folder failed for some reason. e.g. permissions
                    }
                }
            } catch (\Exception $e) {
                // No path is selected
            }

        }

        // If no folder is selected, get the user's default upload folder
        if (!$selectedFolder) {
            try {
                $selectedFolder = $this->getBackendUser()->getDefaultUploadFolder();
            } catch (\Exception $e) {
                // The configured default user folder does not exist
            }
        }
        return $selectedFolder;
    }
}
