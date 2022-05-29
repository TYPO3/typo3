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

namespace TYPO3\CMS\Recordlist\LinkHandler;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Recordlist\View\FolderUtilityRenderer;

/**
 * Link handler for files
 * @internal This class is a specific LinkHandler implementation and is not part of the TYPO3's Core API.
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
        if (isset($linkParts['url'][$this->mode]) && $linkParts['url'][$this->mode] instanceof $this->expectedClass) {
            $this->linkParts = $linkParts;
            return true;
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
        return $this->linkParts['url'][$this->mode]->getName();
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
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/FileLinkHandler');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Viewport/ResizableNavigation');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tree/FileStorageBrowser');

        $this->view->assign('initialNavigationWidth', $this->getBackendUser()->uc['selector']['navigation']['width'] ?? 250);
        $this->view->assign('contentOnly', (bool)($request->getQueryParams()['contentOnly'] ?? false));
        $this->view->assign('treeActions', ($this->mode === 'folder') ? ['link'] : []);

        $this->expandFolder = $request->getQueryParams()['expandFolder'] ?? null;
        if (!isset($this->expandFolder)) {
            if (!empty($this->linkParts)) {
                $fileOrFolder = $this->linkParts['url'][$this->mode];
                if ($fileOrFolder instanceof File) {
                    $fileOrFolder = $fileOrFolder->getParentFolder();
                }
                if ($fileOrFolder instanceof Folder) {
                    $this->expandFolder = $fileOrFolder->getCombinedIdentifier();
                }
            } else {
                // Look up in the user's session which folder was opened the last time
                $moduleSessionData = $this->getBackendUser()->getModuleData('browse_links.php', 'ses');
                $this->expandFolder = $moduleSessionData['expandFolder'] ?? null;
            }
        }

        // Create upload/create folder forms, if a path is given
        $selectedFolder = $this->getSelectedFolder($this->expandFolder);

        // Build the file upload and folder creation form
        if ($selectedFolder) {

            // If a folder is found, store it in the session to continue where the editor left off the last time
            if ($selectedFolder->checkActionPermission('read')) {
                $moduleSessionData = $this->getBackendUser()->getModuleData('browse_links.php', 'ses') ?: [];
                $moduleSessionData['expandFolder'] = $selectedFolder->getCombinedIdentifier();
                $this->getBackendUser()->pushModuleData('browse_links.php', $moduleSessionData);
            }

            $folderUtilityRenderer = GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this);
            $uploadForm = $this->mode === 'file' ? $folderUtilityRenderer->uploadForm($selectedFolder, []) : '';
            $createFolder = $folderUtilityRenderer->createFolder($selectedFolder);

            $this->view->assign('uploadFileForm', $uploadForm);
            $this->view->assign('createFolderForm', $createFolder);

            // Render the file or folderlist
            if ($selectedFolder->checkActionPermission('read')) {
                $this->view->assign('selectedFolder', $selectedFolder);
                $parameters = $this->linkBrowser->getParameters();
                $allowedExtensions = $parameters['params']['allowedExtensions'] ?? '';
                $this->expandFolder($selectedFolder, $allowedExtensions);
            }
        }

        $this->view->setTemplate(ucfirst($this->mode));
        return '';
    }

    /**
     * For RTE: This displays all files from folder. No thumbnails shown
     *
     * @param Folder $folder The folder path to expand
     * @param string $extensionList List of file extensions to show
     */
    public function expandFolder(Folder $folder, $extensionList = '')
    {
        // Create header element; The folder from which files are listed.
        $folderIcon = $this->iconFactory->getIconForResource($folder, Icon::SIZE_SMALL)->render();
        $this->view->assign('selectedFolderIcon', $folderIcon);
        $this->view->assign('selectedFolderTitle', GeneralUtility::fixed_lgd_cs($folder->getIdentifier(), (int)$this->getBackendUser()->uc['titleLen']));
        $this->view->assign('selectedFolderUrl', GeneralUtility::makeInstance(LinkService::class)->asString(['type' => LinkService::TYPE_FOLDER, 'folder' => $folder]));
        if ($this->mode === 'file') {
            $this->view->assign('currentIdentifier', !empty($this->linkParts) ? $this->linkParts['url']['file']->getUid() : '');
        } else {
            $this->view->assign('currentIdentifier', !empty($this->linkParts) ? $this->linkParts['url']['folder']->getCombinedIdentifier() : '');
        }

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
     * @return FileInterface[]|Folder[]
     */
    protected function getFolderContent(Folder $folder, $extensionList)
    {
        if ($extensionList !== '') {
            $filter = GeneralUtility::makeInstance(FileExtensionFilter::class);
            $filter->setAllowedFileExtensions($extensionList);
            $folder->setFileAndFolderNameFilters([[$filter, 'filterFileList']]);
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
            (int)$fileOrFolderObject->getSize(),
            $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:byteSizeUnits')
        );

        return [
            'icon' => $this->iconFactory->getIconForResource($fileOrFolderObject, Icon::SIZE_SMALL)->render(),
            'uid'  => $fileOrFolderObject->getUid(),
            'size' => $size,
            'name' => $fileOrFolderObject->getName(),
            'url'  => GeneralUtility::makeInstance(LinkService::class)->asString(['type' => LinkService::TYPE_FILE, 'file' => $fileOrFolderObject]),
            'title' => GeneralUtility::fixed_lgd_cs($fileOrFolderObject->getName(), (int)$this->getBackendUser()->uc['titleLen']),
        ];
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    public function getBodyTagAttributes()
    {
        if (isset($this->linkParts['url']['file'])) {
            return [
                'data-current-link' => GeneralUtility::makeInstance(LinkService::class)->asString(['type' => LinkService::TYPE_FILE, 'file' => $this->linkParts['url']['file']]),
            ];
        }
        return [];
    }

    /**
     * @param array $values Array of values to include into the parameters or which might influence the parameters
     *
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values)
    {
        $parameters = [
            'expandFolder' => $values['identifier'] ?? $this->expandFolder,
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
    protected function getSelectedFolder($folderIdentifier = '')
    {
        $selectedFolder = false;
        if ($folderIdentifier) {
            try {
                $fileOrFolderObject = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($folderIdentifier);
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
