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
use TYPO3\CMS\Backend\View\RecordSearchBoxComponent;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Filelist\Matcher\Matcher;
use TYPO3\CMS\Filelist\Matcher\ResourceFileExtensionMatcher;
use TYPO3\CMS\Filelist\Matcher\ResourceFolderTypeMatcher;
use TYPO3\CMS\Filelist\Type\Mode;

/**
 * Browser for files. This is used when adding a FAL inline image with the 'add image' button in FormEngine.
 *
 * @internal
 */
class FileBrowser extends AbstractResourceBrowser
{
    public const IDENTIFIER = 'file';
    protected string $identifier = self::IDENTIFIER;

    protected ?string $searchWord = null;
    protected array $allowedFileExtensions = ['*'];
    protected ?FileSearchDemand $searchDemand = null;

    /**
     * Loads additional JavaScript
     */
    protected function initialize(): void
    {
        parent::initialize();
        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/browse-files.js');
    }

    protected function initVariables(): void
    {
        parent::initVariables();
        $request = $this->getRequest();

        $this->searchWord = (string)trim($request->getParsedBody()['searchTerm'] ?? $request->getQueryParams()['searchTerm'] ?? '');

        // The key number 3 of the bparams contains the "allowed" string. Disallowed is not passed to
        // the element browser at all but only filtered out in DataHandler afterwards
        $allowedFileExtensions = GeneralUtility::trimExplode(',', explode('|', $this->bparams)[3], true);
        if (!empty($allowedFileExtensions) && !in_array(['sys_file', '*'], $allowedFileExtensions)) {
            $this->allowedFileExtensions = $allowedFileExtensions;
        }

        $this->resourceDisplayMatcher = GeneralUtility::makeInstance(Matcher::class);
        $this->resourceDisplayMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFolderTypeMatcher::class));
        $this->resourceDisplayMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFileExtensionMatcher::class)->setExtensions($this->allowedFileExtensions));
        $this->resourceSelectableMatcher = GeneralUtility::makeInstance(Matcher::class);
        $this->resourceSelectableMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFileExtensionMatcher::class)->setExtensions($this->allowedFileExtensions));
    }

    /**
     * @return string HTML content
     */
    public function render()
    {
        $contentHtml = '';

        if ($this->selectedFolder instanceof Folder) {
            $markup = [];

            // Prepare search box, since the component should always be displayed, even if no files are available
            $markup[] = '<div class="mb-4">';
            $markup[] = GeneralUtility::makeInstance(RecordSearchBoxComponent::class)
                ->setSearchWord($this->searchWord ?? '')
                ->render($this->getRequest(), $this->createUri([]));
            $markup[] = '</div>';

            // Create the filelist header bar
            $markup[] = '<div class="row justify-content-between mb-2">';
            $markup[] = '    <div class="col-auto">';
            $markup[] = '        <div class="hidden t3js-multi-record-selection-actions">';
            $markup[] = '            <strong>' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.selection')) . '</strong>';
            $markup[] = '            <button type="button" class="btn btn-default btn-sm" data-multi-record-selection-action="import" title="' . htmlspecialchars($this->getLanguageService()->getLL('importSelection')) . '">';
            $markup[] = '                ' . $this->iconFactory->getIcon('actions-document-import-t3d', Icon::SIZE_SMALL);
            $markup[] = '                ' . htmlspecialchars($this->getLanguageService()->getLL('importSelection'));
            $markup[] = '            </button>';
            $markup[] = '        </div>';
            $markup[] = '    </div>';
            $markup[] = '    <div class="col-auto">';
            $markup[] = '        ' . $this->getViewModeButton();
            $markup[] = '    </div>';
            $markup[] = '</div>';

            // Create the filelist
            $this->filelist->start($this->selectedFolder, MathUtility::forceIntegerInRange($this->currentPage, 1, 100000), 'asc', false, Mode::BROWSE);
            $this->filelist->setResourceDisplayMatcher($this->resourceDisplayMatcher);
            $this->filelist->setResourceSelectableMatcher($this->resourceSelectableMatcher);
            $searchDemand = $this->searchWord !== ''
                ? FileSearchDemand::createForSearchTerm($this->searchWord)->withFolder($this->selectedFolder)->withRecursive()
                : null;
            $markup[] = $this->filelist->render($searchDemand, $this->view);

            // Build the file upload and folder creation form
            $folderUtilityRenderer = GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this);
            $markup[] = $folderUtilityRenderer->uploadForm($this->selectedFolder, $this->allowedFileExtensions);
            $markup[] = $folderUtilityRenderer->createFolder($this->selectedFolder);

            $contentHtml = implode('', $markup);
        }

        $contentOnly = (bool)($this->getRequest()->getQueryParams()['contentOnly'] ?? false);
        $this->pageRenderer->setTitle($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:fileSelector'));
        $this->view->assign('selectedFolder', $this->selectedFolder);
        $this->view->assign('content', $contentHtml);
        $this->view->assign('contentOnly', $contentOnly);

        $content = $this->view->render('ElementBrowser/Files');
        if ($contentOnly) {
            return $content;
        }
        $this->pageRenderer->setBodyContent('<body ' . $this->getBodyTagParameters() . '>' . $content);
        return $this->pageRenderer->render();
    }
}
