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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\ResourceUtilityRenderer;
use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Filelist\Matcher\Matcher;
use TYPO3\CMS\Filelist\Matcher\ResourceFileTypeMatcher;
use TYPO3\CMS\Filelist\Matcher\ResourceFolderTypeMatcher;
use TYPO3\CMS\Filelist\Type\Mode;

/**
 * Browser to create new files. This is used with mode=create_file in the ElementBrowser.
 *
 * @internal
 */
class CreateFileBrowser extends AbstractResourceBrowser
{
    public const IDENTIFIER = 'create_file';
    protected string $identifier = self::IDENTIFIER;

    protected function initialize(ServerRequestInterface $request): void
    {
        parent::initialize($request);
        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/resource-creation.js');
    }

    protected function initializeDragUploader(): void
    {
        $lang = $this->getLanguageService();
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/drag-uploader.js');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf', 'file_upload');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf', 'file_download');
        $this->pageRenderer->addInlineLanguageLabelArray([
            'type.file' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:file'),
            'permissions.read' => $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:read'),
            'permissions.write' => $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:write'),
            'online_media.update.success' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.update.success'),
            'online_media.update.error' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:online_media.update.error'),
            'labels.contextMenu.open' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.contextMenu.open'),
        ]);
        $defaultDuplicationBehavior = DuplicationBehavior::getDefaultDuplicationBehaviour($this->getBackendUser());
        $this->view->assign('dragUploader', [
            'fileDenyPattern' => $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] ?? null,
            'maxFileSize' => GeneralUtility::getMaxUploadFileSize() * 1024,
            'defaultDuplicationBehaviourAction' => $defaultDuplicationBehavior->value,
        ]);
    }

    protected function initVariables(ServerRequestInterface $request): void
    {
        parent::initVariables($request);
        $this->resourceDisplayMatcher = GeneralUtility::makeInstance(Matcher::class);
        $this->resourceDisplayMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFolderTypeMatcher::class));
        $this->resourceDisplayMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFileTypeMatcher::class));
    }

    public function render(): string
    {
        $this->initSelectedFolder();
        $this->initializeDragUploader();
        $contentHtml = '';

        if ($this->selectedFolder !== null) {
            $markup = [];

            // Build the file creation and upload forms
            $resourceUtilityRenderer = GeneralUtility::makeInstance(ResourceUtilityRenderer::class, $this);
            $markup[] = $resourceUtilityRenderer->createDragUpload($this->selectedFolder);
            $markup[] = $resourceUtilityRenderer->addOnlineMedia($this->getRequest(), $this->selectedFolder);
            $markup[] = $resourceUtilityRenderer->createRegularFile($this->getRequest(), $this->selectedFolder);

            // Create the filelist
            $this->filelist->start(
                $this->selectedFolder,
                MathUtility::forceIntegerInRange($this->currentPage, 1, 100000),
                $this->sortField,
                $this->sortDirection,
                Mode::BROWSE
            );

            // Create the filelist header bar
            $markup[] = '<div class="row justify-content-between mb-2">';
            $markup[] = '    <div class="col-auto"></div>';
            $markup[] = '    <div class="col-auto">';
            $markup[] = '        ' . $this->getSortingModeButtons($this->filelist->mode);
            $markup[] = '        ' . $this->getViewModeButton();
            $markup[] = '    </div>';
            $markup[] = '</div>';

            $this->filelist->setResourceDisplayMatcher($this->resourceDisplayMatcher);
            $this->filelist->setResourceSelectableMatcher($this->resourceSelectableMatcher);
            $markup[] = $this->filelist->render(null, $this->view);

            $contentHtml = implode(PHP_EOL, $markup);
        }

        $contentOnly = (bool)($this->getRequest()->getQueryParams()['contentOnly'] ?? false);
        $this->pageRenderer->setTitle($this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:actions.new_file'));
        $this->view->assign('selectedFolder', $this->selectedFolder);
        $this->view->assign('content', $contentHtml);
        $this->view->assign('contentOnly', $contentOnly);

        $content = $this->view->render('ElementBrowser/ResourceCreation');
        if ($contentOnly) {
            return $content;
        }
        $this->pageRenderer->setBodyContent('<body ' . $this->getBodyTagParameters() . '>' . $content);
        return $this->pageRenderer->render();
    }
}
