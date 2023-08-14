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

namespace TYPO3\CMS\Filelist\ElementBrowser;

use TYPO3\CMS\Backend\View\FolderUtilityRenderer;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Filelist\Matcher\Matcher;
use TYPO3\CMS\Filelist\Matcher\ResourceFolderTypeMatcher;
use TYPO3\CMS\Filelist\Type\Mode;

/**
 * Browser for folders. This is used with type=folder to select folders.
 *
 * @internal
 */
class FolderBrowser extends AbstractResourceBrowser
{
    public const IDENTIFIER = 'folder';
    protected string $identifier = self::IDENTIFIER;

    protected function initialize(): void
    {
        parent::initialize();
        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/browse-folders.js');
    }

    protected function initVariables(): void
    {
        parent::initVariables();
        $this->resourceDisplayMatcher = GeneralUtility::makeInstance(Matcher::class);
        $this->resourceDisplayMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFolderTypeMatcher::class));
        $this->resourceSelectableMatcher = GeneralUtility::makeInstance(Matcher::class);
        $this->resourceSelectableMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFolderTypeMatcher::class));
    }

    /**
     * @return string HTML content
     */
    public function render()
    {
        $this->initSelectedFolder();
        $contentHtml = '';

        if ($this->selectedFolder instanceof Folder) {
            $markup = [];

            // Create the filelist header bar
            $markup[] = '<div class="row justify-content-between mb-2">';
            $markup[] = '    <div class="col-auto">';
            $markup[] = '        <div class="hidden t3js-multi-record-selection-actions">';
            $markup[] = '            <strong>' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.selection')) . '</strong>';
            $markup[] = '            <button type="button" class="btn btn-default btn-sm" data-multi-record-selection-action="import" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:importSelection')) . '">';
            $markup[] = '                ' . $this->iconFactory->getIcon('actions-document-import-t3d', Icon::SIZE_SMALL);
            $markup[] = '                ' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:importSelection'));
            $markup[] = '            </button>';
            $markup[] = '        </div>';
            $markup[] = '    </div>';
            $markup[] = '    <div class="col-auto">';
            $markup[] = '        ' . $this->getViewModeButton();
            $markup[] = '    </div>';
            $markup[] = '</div>';

            // Create the filelist
            $this->filelist->start(
                $this->selectedFolder,
                MathUtility::forceIntegerInRange($this->currentPage, 1, 100000),
                $this->getRequest()->getQueryParams()['sort'] ?? '',
                ($this->getRequest()->getQueryParams()['reverse'] ?? '') === '1',
                Mode::BROWSE
            );
            $this->filelist->setResourceDisplayMatcher($this->resourceDisplayMatcher);
            $this->filelist->setResourceSelectableMatcher($this->resourceSelectableMatcher);
            $markup[] = $this->filelist->render(null, $this->view);

            // Build the folder creation form
            $folderUtilityRenderer = GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this);
            $markup[] = $folderUtilityRenderer->createFolder($this->selectedFolder);

            $contentHtml = implode('', $markup);
        }

        $contentOnly = (bool)($this->getRequest()->getQueryParams()['contentOnly'] ?? false);
        $this->pageRenderer->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:folderSelector'));
        $this->view->assign('selectedFolder', $this->selectedFolder);
        $this->view->assign('content', $contentHtml);
        $this->view->assign('contentOnly', $contentOnly);

        $content = $this->view->render('ElementBrowser/Folder');
        if ($contentOnly) {
            return $content;
        }
        $this->pageRenderer->setBodyContent('<body ' . $this->getBodyTagParameters() . '>' . $content);
        return $this->pageRenderer->render();
    }
}
