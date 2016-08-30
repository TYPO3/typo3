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
 */
class FolderBrowser extends AbstractElementBrowser implements ElementBrowserInterface, LinkParameterProviderInterface
{
    /**
     * When you click a folder name/expand icon to see the content of a certain file folder,
     * this value will contain the path of the expanded file folder.
     * If the value is NOT set, then it will be restored from the module session data.
     * Example value: "/www/htdocs/typo3/32/3dsplm/fileadmin/css/"
     *
     * @var string|NULL
     */
    protected $expandFolder;

    /**
     * @return void
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
     * @return void
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
        $content = $this->doc->startPage('TBE folder selector');
        $content .= $this->doc->getFlashMessages();

        // Putting the parts together, side by side:
        $content .= '

			<!--
				Wrapper table for folder tree / folder list:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-EBfiles">
				<tr>
					<td class="c-wCell" valign="top"><h3>' . $this->getLanguageService()->getLL('folderTree', true) . ':</h3>' . $tree . '</td>
					<td class="c-wCell" valign="top">' . $folders . '</td>
				</tr>
			</table>
			';

        // Adding create folder if applicable:
        if ($selectedFolder) {
            $content .= GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this)->createFolder($selectedFolder);
        }

        // Add some space
        $content .= '<br /><br />';

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
        $content .= '<h3>' . sprintf($lang->getLL('folders', true) . ' (%s):', count($folders)) . '</h3>';

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
            $aTag = '<a href="#" data-folder-id="' . htmlspecialchars($folderIdentifier) . '" data-close="0">';
            $aTag_alt = '<a href="#" data-folder-id="' . htmlspecialchars($folderIdentifier) . '" data-close="1">';
            if (strstr($subFolderIdentifier, ',') || strstr($subFolderIdentifier, '|')) {
                // In case an invalid character is in the filepath, display error message:
                $errorMessage = sprintf($lang->getLL('invalidChar', true), ', |');
                $aTag = '<a href="#" class="t3js-folderIdError" data-message="' . $errorMessage . '">';
            }
            $aTag_e = '</a>';
            // Combine icon and folderpath:
            $foldernameAndIcon = $aTag_alt . $icon . htmlspecialchars(GeneralUtility::fixed_lgd_cs($subFolder->getName(), $titleLength)) . $aTag_e;
            $lines[] = '
				<tr class="bgColor4">
					<td nowrap="nowrap">' . $foldernameAndIcon . '&nbsp;</td>
					<td>' . $aTag . '<span title="' . $lang->getLL('addToList', true) . '">' . $this->iconFactory->getIcon('actions-edit-add', Icon::SIZE_SMALL)->render() . '</span>' . $aTag_e . '</td>
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
            'expandFolder' => isset($values['identifier']) ? $values['identifier'] : $this->expandFolder,
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
