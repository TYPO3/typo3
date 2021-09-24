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
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for display up to 10 upload fields
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class FileUploadController
{
    /**
     * Set with the target path inputted in &target
     *
     * @var string
     */
    protected $target;

    /**
     * Return URL of list module.
     *
     * @var string
     */
    protected $returnUrl;

    /**
     * Accumulating content
     *
     * @var string
     */
    protected $content;

    /**
     * The folder object which is the target directory for the upload
     *
     * @var File|Folder|null
     */
    protected $folderObject;

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
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');
        $this->init($request);
        $this->renderContent();
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Initialize
     *
     * @param ServerRequestInterface $request
     * @throws InsufficientFolderAccessPermissionsException
     */
    protected function init(ServerRequestInterface $request): void
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        // Initialize GPvars:
        $this->target = $parsedBody['target'] ?? $queryParams['target'] ?? null;
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        if (!$this->returnUrl) {
            $this->returnUrl = (string)$this->uriBuilder->buildUriFromRoute('file_list', [
                'id' => rawurlencode($this->target),
            ]);
        }
        // Create the folder object
        if ($this->target) {
            $this->folderObject = $this->resourceFactory->retrieveFileOrFolderObject($this->target);
        }
        if ($this->folderObject->getStorage()->getUid() === 0) {
            throw new InsufficientFolderAccessPermissionsException(
                'You are not allowed to access folders outside your storages',
                1375889834
            );
        }

        // Cleaning and checking target directory
        if (!$this->folderObject) {
            $title = $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:paramError');
            $message = $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:targetNoDir');
            throw new \RuntimeException($title . ': ' . $message, 1294586843);
        }

        // Setting up the context sensitive menu
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');

        // building pathInfo for metaInformation
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformationForResource($this->folderObject);
    }

    /**
     * Render module content
     */
    protected function renderContent(): void
    {
        $lang = $this->getLanguageService();

        // set page title
        $this->moduleTemplate->setTitle($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:file_upload.php.pagetitle'));

        $pageContent = '<form action="'
            . htmlspecialchars((string)$this->uriBuilder->buildUriFromRoute('tce_file'))
            . '" method="post" id="FileUploadController" name="editform" enctype="multipart/form-data">';
        // Make page header:
        $pageContent .= '<h1>' . $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:file_upload.php.pagetitle') . '</h1>';
        $pageContent .= $this->renderUploadFormInternal();

        // Header Buttons
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // csh button
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('file_upload');
        $buttonBar->addButton($cshButton);

        // back button
        if ($this->returnUrl) {
            $backButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton);
        }

        $pageContent .= '</form>';
        $this->content .= '<div>' . $pageContent . '</div>';
        $this->moduleTemplate->setContent($this->content);
    }

    /**
     * This function renders the upload form
     *
     * @return string The HTML form as a string, ready for outputting
     */
    protected function renderUploadFormInternal(): string
    {
        $content = '
            <div class="form-group">
                <input type="file" multiple="multiple" class="form-control" name="upload_1[]" />
                <input type="hidden" name="data[upload][1][target]" value="' . htmlspecialchars($this->folderObject->getCombinedIdentifier()) . '" />
                <input type="hidden" name="data[upload][1][data]" value="1" />
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="overwriteExistingFiles" id="overwriteExistingFiles" value="replace" />
                <label class="form-check-label" for="overwriteExistingFiles"> ' . htmlspecialchars($this->getLanguageService()->getLL('overwriteExistingFiles')) . '</label>
            </div>
            <div>
                <input type="hidden" name="data[upload][1][redirect]" value="' . $this->returnUrl . '" />
                <input class="btn btn-primary" type="submit" value="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:file_upload.php.submit')) . '" />
            </div>
            <div class="callout callout-warning">
              ' . htmlspecialchars($this->getLanguageService()->getLL('uploadMultipleFilesInfo')) . '
            </div>
        ';

        return $content;
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
