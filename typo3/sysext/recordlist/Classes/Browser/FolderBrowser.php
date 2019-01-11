<?php
namespace TYPO3\CMS\Recordlist\Browser;

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

use TYPO3\CMS\Backend\Tree\View\ElementBrowserFolderTreeView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Recordlist\View\FolderUtilityRenderer;

/**
 * Browser for folders
 * @internal This class is a specific LinkBrowser implementation and is not part of the TYPO3's Core API.
 */
class FolderBrowser extends AbstractElementBrowser implements ElementBrowserInterface, LinkParameterProviderInterface
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
     * Adds additional JavaScript modules
     */
    protected function initialize()
    {
        parent::initialize();
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Recordlist/BrowseFolders');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/LegacyTree', 'function() {
            DragDrop.table = "folders";
            Tree.registerDragDropHandlers();
        }');
    }

    /**
     * Checks for an additional request parameter
     */
    protected function initVariables()
    {
        parent::initVariables();
        $this->expandFolder = GeneralUtility::_GP('expandFolder');
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
        $selectedFolder = null;
        if ($this->expandFolder) {
            $selectedFolder = ResourceFactory::getInstance()->getFolderObjectFromCombinedIdentifier($this->expandFolder);
        }

        // Create folder tree:
        /** @var ElementBrowserFolderTreeView $folderTree */
        $folderTree = GeneralUtility::makeInstance(ElementBrowserFolderTreeView::class);
        $folderTree->setLinkParameterProvider($this);
        $tree = $folderTree->getBrowsableTree();

        $folders = '';
        if ($selectedFolder) {
            $folders = $this->renderFolders($selectedFolder);
        }

        $this->initDocumentTemplate();
        $content = $this->doc->startPage(htmlspecialchars($this->getLanguageService()->getLL('folderSelector')));

        // Putting the parts together, side by side:
        $markup = [];
        $markup[] = '<!-- Wrapper table for folder tree / filelist: -->';
        $markup[] = '<div class="element-browser">';
        $markup[] = '   <div class="element-browser-panel element-browser-main">';
        $markup[] = '       <div class="element-browser-main-sidebar">';
        $markup[] = '           <div class="element-browser-body">';
        $markup[] = '               ' . $tree;
        $markup[] = '           </div>';
        $markup[] = '       </div>';
        $markup[] = '       <div class="element-browser-main-content">';
        $markup[] = '           <div class="element-browser-body">';
        $markup[] = '               ' . $this->doc->getFlashMessages();
        $markup[] = '               ' . $folders;
        if ($selectedFolder) {
            $markup[] = '           ' . GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this)->createFolder($selectedFolder);
        }
        $markup[] = '           </div>';
        $markup[] = '       </div>';
        $markup[] = '   </div>';
        $markup[] = '</div>';
        $content .= implode('', $markup);

        // Ending page, returning content:
        $content .= $this->doc->endPage();
        return $this->doc->insertStylesAndJS($content);
    }

    /**
     * @param Folder $parentFolder
     * @return string HTML code
     */
    protected function renderFolders(Folder $parentFolder)
    {
        if (!$parentFolder->checkActionPermission('read')) {
            return '';
        }
        $content = '';
        $lang = $this->getLanguageService();
        $folders = $parentFolder->getSubfolders();
        $folderIdentifier = $parentFolder->getCombinedIdentifier();

        // Create headline (showing number of folders):
        $content .= '<h3>' . sprintf(htmlspecialchars($lang->getLL('folders')) . ' (%s):', count($folders)) . '</h3>';

        $titleLength = (int)$this->getBackendUser()->uc['titleLen'];
        // Create the header of current folder:
        $folderIcon = '<a href="#" data-folder-id="' . htmlspecialchars($folderIdentifier) . '" data-close="1">';
        $folderIcon .= $this->iconFactory->getIcon('apps-filetree-folder-default', Icon::SIZE_SMALL);
        $folderIcon .= htmlspecialchars(GeneralUtility::fixed_lgd_cs($parentFolder->getName(), $titleLength));
        $folderIcon .= '</a>';
        $content .= $folderIcon . '<br />';

        $lines = [];
        // Traverse the folder list:
        foreach ($folders as $subFolder) {
            $subFolderIdentifier = $subFolder->getCombinedIdentifier();
            // Create folder icon:
            $icon = '<span style="width: 16px; height: 16px; display: inline-block;"></span>';
            $icon .= '<span title="' . htmlspecialchars($subFolder->getName()) . '">' . $this->iconFactory->getIcon('apps-filetree-folder-default', Icon::SIZE_SMALL) . '</span>';
            // Create links for adding the folder:
            $aTag = '<a href="#" data-folder-id="' . htmlspecialchars($subFolderIdentifier) . '" data-close="0">';
            $aTag_alt = '<a href="#" data-folder-id="' . htmlspecialchars($subFolderIdentifier) . '" data-close="1">';
            if (strstr($subFolderIdentifier, ',') || strstr($subFolderIdentifier, '|')) {
                // In case an invalid character is in the filepath, display error message:
                $errorMessage = sprintf(htmlspecialchars($lang->getLL('invalidChar')), ', |');
                $aTag = '<a href="#" class="t3js-folderIdError" data-message="' . $errorMessage . '">';
            }
            $aTag_e = '</a>';
            // Combine icon and folderpath:
            $foldernameAndIcon = $aTag_alt . $icon . htmlspecialchars(GeneralUtility::fixed_lgd_cs($subFolder->getName(), $titleLength)) . $aTag_e;
            $lines[] = '
				<tr>
					<td class="nowrap">' . $foldernameAndIcon . '&nbsp;</td>
					<td>' . $aTag . '<span title="' . htmlspecialchars($lang->getLL('addToList')) . '">' . $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL)->render() . '</span>' . $aTag_e . '</td>
					<td>&nbsp;</td>
				</tr>';
            $lines[] = '
					<tr>
						<td colspan="3"><span style="width: 1px; height: 3px; display: inline-block;"></span></td>
					</tr>';
        }
        // Wrap all the rows in table tags:
        $content .= '

	<!--
		Folder listing
	-->
			<table border="0" cellpadding="0" cellspacing="1" id="typo3-folderList">
				' . implode('', $lines) . '
			</table>';

        return $content;
    }

    /**
     * @return string[] Array of body-tag attributes
     */
    protected function getBodyTagAttributes()
    {
        return [
            'data-mode' => 'folder'
        ];
    }

    /**
     * @param array $values Array of values to include into the parameters
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values)
    {
        return [
            'mode' => 'folder',
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
