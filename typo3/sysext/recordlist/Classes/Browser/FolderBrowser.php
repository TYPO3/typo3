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
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tree/FileStorageBrowser');
    }

    /**
     * Checks for an additional request parameter
     */
    protected function initVariables()
    {
        parent::initVariables();
        $this->expandFolder = $this->getRequest()->getParsedBody()['expandFolder'] ?? $this->getRequest()->getQueryParams()['expandFolder'] ?? null;
    }

    /**
     * Session data for this class can be set from outside with this method.
     *
     * @param mixed[] $data Session data array
     * @return array<int, array|bool> Session data and boolean which indicates that data needs to be stored in session because it's changed
     */
    public function processSessionData($data)
    {
        if ($this->expandFolder !== null) {
            $data['expandFolder'] = $this->expandFolder;
            $store = true;
        } else {
            $this->expandFolder = $data['expandFolder'] ?? null;
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
            $selectedFolder = GeneralUtility::makeInstance(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier($this->expandFolder);
        }

        $folders = '';
        if ($selectedFolder) {
            $folders = $this->renderFolders($selectedFolder);
        }
        if ($selectedFolder) {
            $folders .= GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this)->createFolder($selectedFolder);
        }

        $contentOnly = (bool)($this->getRequest()->getQueryParams()['contentOnly'] ?? false);
        $this->setBodyTagParameters();
        $this->moduleTemplate->setTitle($this->getLanguageService()->getLL('folderSelector'));
        $view = $this->moduleTemplate->getView();
        $view->assignMultiple([
            'treeEnabled' => true,
            'treeType' => 'folder',
            'activeFolder' => $selectedFolder,
            'initialNavigationWidth' => $this->getBackendUser()->uc['selector']['navigation']['width'] ?? 250,
            'content' => $folders,
            'contentOnly' => $contentOnly,
        ]);
        if ($contentOnly) {
            return $view->render();
        }
        return $this->moduleTemplate->renderContent();
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
            if (str_contains($subFolderIdentifier, ',') || str_contains($subFolderIdentifier, '|')) {
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
     * @param array $values Array of values to include into the parameters
     * @return string[] Array of parameters which have to be added to URLs
     */
    public function getUrlParameters(array $values)
    {
        return [
            'mode' => 'folder',
            'expandFolder' => $values['identifier'] ?? $this->expandFolder,
            'bparams' => $this->bparams,
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
