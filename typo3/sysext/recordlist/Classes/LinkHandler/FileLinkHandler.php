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
     * @var string
     */
    protected $additionalFolderClass = '';

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
        $tree = $folderTree->getBrowsableTree();

        // Create upload/create folder forms, if a path is given
        $selectedFolder = false;
        if ($this->expandFolder) {
            $fileOrFolderObject = null;
            try {
                $fileOrFolderObject = ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->expandFolder);
            } catch (\Exception $e) {
                // No path is selected
            }

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
        }

        $backendUser = $this->getBackendUser();
        // If no folder is selected, get the user's default upload folder
        if (!$selectedFolder) {
            try {
                $selectedFolder = $backendUser->getDefaultUploadFolder();
            } catch (\Exception $e) {
                // The configured default user folder does not exist
            }
        }
        // Build the file upload and folder creation form
        $uploadForm = '';
        $createFolder = '';
        $content = '';
        if ($selectedFolder) {
            $folderUtilityRenderer = GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this);
            $uploadForm = $this->mode === 'file' ? $folderUtilityRenderer->uploadForm($selectedFolder, []) : '';
            $createFolder = $folderUtilityRenderer->createFolder($selectedFolder);
        }
        // Insert the upload form on top, if so configured
        if ($backendUser->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
            $content .= $uploadForm;
        }

        // Render the filelist if there is a folder selected
        $files = '';
        if ($selectedFolder) {
            $parameters = $this->linkBrowser->getUrlParameters();
            $allowedExtensions = isset($parameters['allowedExtensions']) ? $parameters['allowedExtensions'] : '';
            $files = $this->expandFolder($selectedFolder, $allowedExtensions);
        }
        // Create folder tree:
        $content .= '
				<!--
					Wrapper table for folder tree / file/folder list:
				-->
						<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkFiles">
							<tr>
								<td class="c-wCell" valign="top"><h3>' . $this->getLanguageService()->getLL('folderTree') . ':</h3>' . $tree . '</td>
								<td class="c-wCell" valign="top">' . $files . '</td>
							</tr>
						</table>
						';
        // Adding create folder + upload form if applicable
        if (!$backendUser->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
            $content .= $uploadForm;
        }
        $content .=  '<br />' . $createFolder . '<br />';
        return $content;
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
        if (!$folder->checkActionPermission('read')) {
            return '';
        }
        $out = '<h3>' . htmlspecialchars($this->getTitle()) . ':</h3>';

        // Create header element; The folder from which files are listed.
        $titleLen = (int)$this->getBackendUser()->uc['titleLen'];
        $folderIcon = $this->iconFactory->getIconForResource($folder, Icon::SIZE_SMALL);
        $folderIcon .= htmlspecialchars(GeneralUtility::fixed_lgd_cs($folder->getIdentifier(), $titleLen));

        $currentIdentifier = !empty($this->linkParts) ? $this->linkParts['url'] : '';
        $selected = $currentIdentifier === $folder->getCombinedIdentifier() ? $this->additionalFolderClass : '';
        $out .= '
			<span class="' . $selected . '" title="' . htmlspecialchars($folder->getIdentifier()) . '">
				' . $folderIcon . '
			</span>
			';
        // Get files from the folder:
        $folderContent = $this->getFolderContent($folder, $extensionList);
        if (!empty($folderContent)) {
            $out .= '<ul class="list-tree">';
            foreach ($folderContent as $fileOrFolderObject) {
                list($fileIdentifier, $icon) = $this->renderItem($fileOrFolderObject);
                $selected = (int)$currentIdentifier === $fileIdentifier ? ' class="active"' : '';
                $out .=
                    '<li' . $selected . '>
                        <span class="list-tree-group">
                            <a href="#" class="t3js-fileLink list-tree-group" title="' . htmlspecialchars($fileOrFolderObject->getName()) . '" data-file="file:' . htmlspecialchars($fileIdentifier) . '">
                                <span class="list-tree-icon">' . $icon . '</span>
                                <span class="list-tree-title">' . htmlspecialchars(GeneralUtility::fixed_lgd_cs($fileOrFolderObject->getName(), $titleLen)) . '</span>
                            </a>
                        </span>
                    </li>';
            }
            $out .= '</ul>';
        }
        return $out;
    }

    /**
     * @return string
     */
    protected function getTitle()
    {
        return $this->getLanguageService()->getLL('files');
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
        $fileIdentifier = $fileOrFolderObject->getUid();
        // Get size and icon:
        $size = ' (' . GeneralUtility::formatSize($fileOrFolderObject->getSize()) . 'bytes)';
        $icon = '<span title="' . htmlspecialchars($fileOrFolderObject->getName() . $size) . '">'
            . $this->iconFactory->getIconForResource($fileOrFolderObject, Icon::SIZE_SMALL)
            . '</span>';
        return [$fileIdentifier, $icon];
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
}
