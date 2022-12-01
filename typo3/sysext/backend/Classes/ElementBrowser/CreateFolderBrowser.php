<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\ElementBrowser;

use TYPO3\CMS\Backend\Tree\View\LinkParameterProviderInterface;
use TYPO3\CMS\Backend\View\FolderUtilityRenderer;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Browser to create one or more folders. This is used with type=folder to select folders.
 *
 * @internal This class is a specific LinkBrowser implementation and is not part of the TYPO3's Core API.
 */
class CreateFolderBrowser extends AbstractElementBrowser implements ElementBrowserInterface, LinkParameterProviderInterface
{
    public const IDENTIFIER = 'create_folder';

    protected string $identifier = self::IDENTIFIER;
    protected ?string $selectedFolder = null;

    protected function initialize(): void
    {
        parent::initialize();
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/tree/file-storage-browser.js');
        $javaScriptRenderer = $this->pageRenderer->getJavaScriptRenderer();
        $javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/filelist/create-folder.js')->instance()
        );
    }

    protected function initVariables(): void
    {
        parent::initVariables();
        $this->selectedFolder = $this->getRequest()->getParsedBody()['expandFolder'] ?? $this->getRequest()->getQueryParams()['expandFolder'] ?? null;
    }

    public function render(): string
    {
        $selectedFolder = null;

        if ($this->selectedFolder !== null) {
            $selectedFolder = GeneralUtility::makeInstance(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier($this->selectedFolder);
            $this->view->assign('selectedFolderIcon', $this->iconFactory->getIconForResource($selectedFolder, Icon::SIZE_SMALL)->render());
            $this->view->assign('selectedFolderTitle', $selectedFolder->getStorage()->getName() . ': ' . $selectedFolder->getIdentifier());
            $this->view->assign('createFolderForm', GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this)->createFolder($selectedFolder));
        }

        $contentOnly = (bool)($this->getRequest()->getQueryParams()['contentOnly'] ?? false);
        $this->pageRenderer->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:createFolder'));
        $this->view->assignMultiple([
            'activeFolder' => $selectedFolder,
            'initialNavigationWidth' => $this->getBackendUser()->uc['selector']['navigation']['width'] ?? 250,
            'folders' => $selectedFolder ? $selectedFolder->getSubfolders() : [],
            'contentOnly' => $contentOnly,
        ]);
        $content = $this->view->render('ElementBrowser/CreateFolder');
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
    public function getUrlParameters(array $values): array
    {
        return [
            'mode' => $this->identifier,
            'expandFolder' => $values['identifier'] ?? $this->selectedFolder,
            'bparams' => $this->bparams,
        ];
    }

    public function isCurrentlySelectedItem(array $values): bool
    {
        return false;
    }

    public function getScriptUrl(): string
    {
        return $this->thisScript;
    }

    /**
     * @param mixed[] $data Session data array
     * @return array<int, array|bool> Session data and boolean which indicates that data needs to be stored in session because it's changed
     */
    public function processSessionData($data): array
    {
        return [$data, false];
    }
}
