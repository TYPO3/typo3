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

namespace TYPO3\CMS\Filelist\Controller\File;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script Class for the rename-file form.
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class RenameFileController
{
    /**
     * Target path
     *
     * @var string
     * @internal
     */
    protected $target;

    /**
     * The file or folder object that should be renamed
     *
     * @var File|Folder|null
     */
    protected $fileOrFolderObject;

    /**
     * Return URL of list module.
     *
     * @var string
     */
    protected $returnUrl;

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    protected IconFactory $iconFactory;
    protected PageRenderer $pageRenderer;
    protected UriBuilder $uriBuilder;
    protected ResourceFactory $resourceFactory;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        IconFactory $iconFactory,
        PageRenderer $pageRenderer,
        UriBuilder $uriBuilder,
        ResourceFactory $resourceFactory,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->pageRenderer = $pageRenderer;
        $this->uriBuilder = $uriBuilder;
        $this->resourceFactory = $resourceFactory;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Processes the request, currently everything is handled and put together via "renderContent()"
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->init($request);
        $this->renderContent();
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Initialize
     *
     * @param ServerRequestInterface $request
     * @throws InsufficientFileAccessPermissionsException
     */
    protected function init(ServerRequestInterface $request): void
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        // Initialize GPvars:
        $this->target = $parsedBody['target'] ?? $queryParams['target'] ?? null;
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        // Cleaning and checking target
        if ($this->target) {
            $this->fileOrFolderObject = $this->resourceFactory->retrieveFileOrFolderObject($this->target);
        }
        if (!$this->fileOrFolderObject) {
            $title = $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:paramError');
            $message = $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:targetNoDir');
            throw new \RuntimeException($title . ': ' . $message, 1294586844);
        }
        if ($this->fileOrFolderObject->getStorage()->getUid() === 0) {
            throw new InsufficientFileAccessPermissionsException('You are not allowed to access files outside your storages', 1375889840);
        }

        // If a folder should be renamed, AND the returnURL should go to the old directory name, the redirect is forced
        // so the redirect will NOT end in an error message
        // this case only happens if you select the folder itself in the foldertree and then use the clickmenu to
        // rename the folder
        if ($this->fileOrFolderObject instanceof Folder) {
            $parsedUrl = parse_url($this->returnUrl);
            $queryParts = GeneralUtility::explodeUrl2Array(urldecode($parsedUrl['query']));
            if ($queryParts['id'] === $this->fileOrFolderObject->getCombinedIdentifier()) {
                $this->returnUrl = str_replace(
                    urlencode($queryParts['id']),
                    urlencode($this->fileOrFolderObject->getStorage()->getRootLevelFolder()->getCombinedIdentifier()),
                    $this->returnUrl
                );
            }
        }

        // building pathInfo for metaInformation
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformationForResource($this->fileOrFolderObject);

        // Setting up the context sensitive menu
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/RenameFile');
    }

    /**
     * Render module content
     */
    protected function renderContent(): void
    {
        $assigns = [];
        $assigns['moduleUrlTceFile'] = (string)$this->uriBuilder->buildUriFromRoute('tce_file');
        $assigns['returnUrl'] = $this->returnUrl;

        if ($this->fileOrFolderObject instanceof Folder) {
            $fileIdentifier = $this->fileOrFolderObject->getCombinedIdentifier();
            $targetLabel = 'file_rename.php.label.target.folder';
        } else {
            $fileIdentifier = $this->fileOrFolderObject->getUid();
            $targetLabel = 'file_rename.php.label.target.file';
            $assigns['conflictMode'] = DuplicationBehavior::cast(DuplicationBehavior::RENAME);
            $assigns['destination'] = $this->fileOrFolderObject->getParentFolder()->getCombinedIdentifier();
        }

        $assigns['fileName'] = $this->fileOrFolderObject->getName();
        $assigns['fileIdentifier'] = $fileIdentifier;
        $assigns['fieldLabel'] = $targetLabel;

        // Create buttons
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // csh button
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('file_rename');
        $buttonBar->addButton($cshButton);

        // back button
        if ($this->returnUrl) {
            $backButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton);
        }

        // Save and Close button
        $saveAndCloseButton = $buttonBar->makeInputButton()
            ->setName('_saveandclose')
            ->setValue('1')
            ->setShowLabelText(true)
            ->setClasses('t3js-submit-file-rename')
            ->setForm('RenameFileController')
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:file_edit.php.saveAndClose'))
            ->setIcon($this->iconFactory->getIcon('actions-document-save-close', Icon::SIZE_SMALL));

        $buttonBar->addButton($saveAndCloseButton, ButtonBar::BUTTON_POSITION_LEFT, 20);

        $this->pageRenderer->addInlineLanguageLabelArray([
            'file_rename.actions.cancel' => $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:file_rename.actions.cancel'),
            'file_rename.actions.rename' => $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:file_rename.actions.rename'),
            'file_rename.actions.override' => $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:file_rename.actions.override'),
            'file_rename.exists.title' => $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:file_rename.exists.title'),
            'file_rename.exists.description' => $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:file_rename.exists.description'),
        ]);

        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:filelist/Resources/Private/Templates/File/RenameFile.html'
        ));
        $view->assignMultiple($assigns);
        $this->moduleTemplate->setContent($view->render());
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
