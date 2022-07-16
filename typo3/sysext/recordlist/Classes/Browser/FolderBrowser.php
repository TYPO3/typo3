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
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Recordlist\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Recordlist\View\FolderUtilityRenderer;

/**
 * Browser for folders. This is used with type=folder to select folders.
 *
 * @internal This class is a specific LinkBrowser implementation and is not part of the TYPO3's Core API.
 */
class FolderBrowser extends AbstractElementBrowser implements ElementBrowserInterface, LinkParameterProviderInterface
{
    protected string $identifier = 'folder';

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
        $this->pageRenderer->loadJavaScriptModule('@typo3/recordlist/browse-folders.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/tree/file-storage-browser.js');
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

        if ($selectedFolder) {
            $folderIcon = $this->iconFactory->getIconForResource($selectedFolder, Icon::SIZE_SMALL)->render();
            $this->view->assign('selectedFolderIcon', $folderIcon);
            $this->view->assign('selectedFolderTitle', GeneralUtility::fixed_lgd_cs($selectedFolder->getIdentifier(), (int)$this->getBackendUser()->uc['titleLen']));
            $this->view->assign('createFolderForm', GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this)->createFolder($selectedFolder));
        }

        $contentOnly = (bool)($this->getRequest()->getQueryParams()['contentOnly'] ?? false);
        $this->pageRenderer->setTitle($this->getLanguageService()->sL('LLL:EXT:recordlist/Resources/Private/Language/locallang_browse_links.xlf:folderSelector'));
        $this->view->assignMultiple([
            'treeEnabled' => true,
            'activeFolder' => $selectedFolder,
            'initialNavigationWidth' => $this->getBackendUser()->uc['selector']['navigation']['width'] ?? 250,
            'folders' => $selectedFolder ? $selectedFolder->getSubfolders() : [],
            'contentOnly' => $contentOnly,
        ]);
        $content = $this->view->render('ElementBrowser/Folder');
        if ($contentOnly) {
            return $content;
        }
        $this->pageRenderer->setBodyContent('<body ' . $this->getBodyTagParameters() . '>' . $content);
        return $this->pageRenderer->render();
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
