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

namespace TYPO3\CMS\Filelist\ElementBrowser;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\ElementBrowser\Event\IsFileSelectableEvent;
use TYPO3\CMS\Backend\View\FolderUtilityRenderer;
use TYPO3\CMS\Backend\View\RecordSearchBoxComponent;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceInterface;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Filelist\Matcher\AndMatcher;
use TYPO3\CMS\Filelist\Matcher\Matcher;
use TYPO3\CMS\Filelist\Matcher\MatcherInterface;
use TYPO3\CMS\Filelist\Matcher\ResourceFileExtensionMatcher;
use TYPO3\CMS\Filelist\Matcher\ResourceFolderTypeMatcher;
use TYPO3\CMS\Filelist\Type\Mode;
use TYPO3\CMS\Filelist\Type\SortDirection;

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
    protected ?FileExtensionFilter $fileExtensionFilter;
    protected ?FileSearchDemand $searchDemand = null;

    /**
     * Loads additional JavaScript
     */
    protected function initialize(ServerRequestInterface $request): void
    {
        parent::initialize($request);
        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/browse-files.js');
    }

    protected function initVariables(ServerRequestInterface $request): void
    {
        parent::initVariables($request);

        $this->searchWord = trim((string)($request->getParsedBody()['searchTerm'] ?? $request->getQueryParams()['searchTerm'] ?? ''));

        $fileExtensions = GeneralUtility::trimExplode('~', explode('|', $this->bparams)[3], true);
        $allowed = preg_replace('/^allowed=/', '', $fileExtensions[0] ?? '', 1);
        $disallowed = preg_replace('/^disallowed=/', '', $fileExtensions[1] ?? '', 1);

        $this->fileExtensionFilter = GeneralUtility::makeInstance(FileExtensionFilter::class);
        if ($allowed !== '' && !str_contains($allowed, 'sys_file') && !str_contains($allowed, '*')) {
            $this->fileExtensionFilter->setAllowedFileExtensions($allowed);
        }
        if ($disallowed !== '') {
            $this->fileExtensionFilter->setDisallowedFileExtensions($disallowed);
        }

        $this->resourceDisplayMatcher = GeneralUtility::makeInstance(Matcher::class);
        $this->resourceDisplayMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFolderTypeMatcher::class));
        $this->resourceDisplayMatcher->addMatcher(
            GeneralUtility::makeInstance(
                AndMatcher::class,
                GeneralUtility::makeInstance(ResourceFileExtensionMatcher::class)
                    ->setExtensions($this->fileExtensionFilter->getAllowedFileExtensions() ?? ['*'])
                    ->setIgnoredExtensions($this->fileExtensionFilter->getDisallowedFileExtensions() ?? []),
                new class (GeneralUtility::makeInstance(EventDispatcherInterface::class)) implements MatcherInterface {
                    public function __construct(private readonly EventDispatcherInterface $eventDispatcher) {}
                    public function supports(mixed $item): bool
                    {
                        return $item instanceof ResourceInterface;
                    }
                    public function match(mixed $item): bool
                    {
                        return $this->eventDispatcher->dispatch(new IsFileSelectableEvent($item))->isFileSelectable();
                    }
                }
            )
        );

        $this->resourceSelectableMatcher = GeneralUtility::makeInstance(Matcher::class);
        $this->resourceSelectableMatcher->addMatcher(
            GeneralUtility::makeInstance(ResourceFileExtensionMatcher::class)
                ->setExtensions($this->fileExtensionFilter->getAllowedFileExtensions() ?? ['*'])
                ->setIgnoredExtensions($this->fileExtensionFilter->getDisallowedFileExtensions() ?? [])
        );
    }

    public function render(): string
    {
        $this->initSelectedFolder();
        $contentHtml = '';

        if ($this->selectedFolder instanceof Folder) {
            $markup = [];

            // Prepare search box, since the component should always be displayed, even if no files are available
            $markup[] = '<div class="mb-4">';
            $markup[] = GeneralUtility::makeInstance(RecordSearchBoxComponent::class)
                ->setSearchWord($this->searchWord ?? '')
                ->render($this->getRequest(), $this->createUri());
            $markup[] = '</div>';

            // Create the filelist
            $this->filelist->start(
                $this->selectedFolder,
                MathUtility::forceIntegerInRange($this->currentPage, 1, 100000),
                $this->sortField,
                $this->sortDirection === SortDirection::DESCENDING,
                Mode::BROWSE
            );

            // Create the filelist header bar
            $markup[] = '<div class="row justify-content-between mb-2">';
            $markup[] = '    <div class="col-auto">';
            $markup[] = '        <div class="hidden t3js-multi-record-selection-actions">';
            $markup[] = '            <strong>' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.selection')) . '</strong>';
            $markup[] = '            <button type="button" class="btn btn-default btn-sm" data-multi-record-selection-action="import" title="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:importSelection')) . '">';
            $markup[] = '                ' . $this->iconFactory->getIcon('actions-document-import-t3d', IconSize::SMALL);
            $markup[] = '                ' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang_browse_links.xlf:importSelection'));
            $markup[] = '            </button>';
            $markup[] = '        </div>';
            $markup[] = '    </div>';
            $markup[] = '    <div class="col-auto">';
            $markup[] = '        ' . $this->getSortingModeButtons($this->filelist->mode);
            $markup[] = '        ' . $this->getViewModeButton();
            $markup[] = '    </div>';
            $markup[] = '</div>';

            $this->filelist->setResourceDisplayMatcher($this->resourceDisplayMatcher);
            $this->filelist->setResourceSelectableMatcher($this->resourceSelectableMatcher);
            $searchDemand = $this->searchWord !== ''
                ? FileSearchDemand::createForSearchTerm($this->searchWord)->withFolder($this->selectedFolder)->withRecursive()
                : null;
            $markup[] = $this->filelist->render($searchDemand, $this->view);

            // Build the file upload and folder creation form
            $folderUtilityRenderer = GeneralUtility::makeInstance(FolderUtilityRenderer::class, $this);
            $markup[] = $folderUtilityRenderer->uploadForm($this->getRequest(), $this->selectedFolder, $this->fileExtensionFilter);
            $markup[] = $folderUtilityRenderer->createFolder($this->getRequest(), $this->selectedFolder);

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
