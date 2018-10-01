<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Controller\File;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script Class for display up to 10 upload fields
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class FileUploadController
{
    use PublicPropertyDeprecationTrait;

    /**
     * @var array
     */
    protected $deprecatedPublicProperties = [
        'title' => 'Using $title of class FileUploadController from outside is discouraged as this variable is only used for internal storage.',
        'target' => 'Using $target of class FileUploadController from outside is discouraged as this variable is only used for internal storage.',
        'returnUrl' => 'Using $returnUrl of class FileUploadController from outside is discouraged as this variable is only used for internal storage.',
        'content' => 'Using $content of class FileUploadController from outside is discouraged as this variable is only used for internal storage.',
    ];

    /**
     * Name of the filemount
     *
     * @var string
     */
    protected $title;

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
     * @var \TYPO3\CMS\Core\Resource\Folder $folderObject
     */
    protected $folderObject;

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');

        // @deprecated since TYPO3 v9, will be moved out of __construct() in TYPO3 v10.0
        $this->init($GLOBALS['TYPO3_REQUEST']);
    }

    /**
     * Processes the request, currently everything is handled and put together via "renderContent()"
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->renderContent();
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Main function, rendering the upload file form fields
     *
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function main()
    {
        trigger_error('FileUploadController->main() will be replaced by protected method renderContent() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        $this->renderContent();
    }

    /**
     * This function renders the upload form
     *
     * @return string The HTML form as a string, ready for outputting
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    public function renderUploadForm()
    {
        trigger_error('FileUploadController->renderUploadForm() will be replaced by protected method renderUploadFormInternal() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        return $this->renderUploadFormInternal();
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

        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);
        // Initialize GPvars:
        $this->target = $parsedBody['target'] ?? $queryParams['target'] ?? null;
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        if (!$this->returnUrl) {
            $this->returnUrl = (string)$uriBuilder->buildUriFromRoute('file_list', [
                'id' => rawurlencode($this->target)
            ]);
        }
        // Create the folder object
        if ($this->target) {
            $this->folderObject = ResourceFactory::getInstance()
                ->retrieveFileOrFolderObject($this->target);
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
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');

        // building pathInfo for metaInformation
        $pathInfo = [
            'combined_identifier' => $this->folderObject->getCombinedIdentifier(),
        ];
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pathInfo);
    }

    /**
     * Render module content
     */
    protected function renderContent(): void
    {
        $lang = $this->getLanguageService();
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);

        // set page title
        $this->moduleTemplate->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:file_upload.php.pagetitle'));

        $pageContent = '<form action="'
            . htmlspecialchars((string)$uriBuilder->buildUriFromRoute('tce_file'))
            . '" method="post" id="FileUploadController" name="editform" enctype="multipart/form-data">';
        // Make page header:
        $pageContent .= '<h1>' . $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:file_upload.php.pagetitle') . '</h1>';
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
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
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
        // Make checkbox for "overwrite"
        $content = '
            <div id="c-override">
                <p class="checkbox"><label for="overwriteExistingFiles"><input type="checkbox" name="overwriteExistingFiles" id="overwriteExistingFiles" value="replace" /> ' . htmlspecialchars($this->getLanguageService()->getLL('overwriteExistingFiles')) . '</label></p>
                <p>' . htmlspecialchars($this->getLanguageService()->getLL('uploadMultipleFilesInfo')) . '</p>
            </div>
            ';
        // Produce the number of upload-fields needed:
        $content .= '
            <div id="c-upload">
        ';
        // Adding 'size="50" ' for the sake of Mozilla!
        $content .= '
                <input type="file" multiple="multiple" name="upload_1[]" />
                <input type="hidden" name="data[upload][1][target]" value="' . htmlspecialchars($this->folderObject->getCombinedIdentifier()) . '" />
                <input type="hidden" name="data[upload][1][data]" value="1" /><br />
            ';
        $content .= '
            </div>
        ';
        // Submit button:
        $content .= '
            <div id="c-submit">
                <input type="hidden" name="data[upload][1][redirect]" value="' . $this->returnUrl . '" /><br />
                <input class="btn btn-default" type="submit" value="' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:file_upload.php.submit')) . '" />
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
