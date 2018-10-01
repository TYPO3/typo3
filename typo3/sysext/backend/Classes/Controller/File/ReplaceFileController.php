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
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script Class for the replace-file form
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class ReplaceFileController
{
    use PublicPropertyDeprecationTrait;

    /**
     * @var array
     */
    protected $deprecatedPublicProperties = [
        'doc' => 'Using $doc of class ReplaceFileController from outside is discouraged as this variable is only used for internal storage.',
        'title' => 'Using $title of class ReplaceFileController from outside is discouraged as this variable is only used for internal storage.',
        'uid' => 'Using $uid of class ReplaceFileController from outside is discouraged as this variable is only used for internal storage.',
        'returnUrl' => 'Using $returnUrl of class ReplaceFileController from outside is discouraged as this variable is only used for internal storage.',
        'content' => 'Using $content of class ReplaceFileController from outside is discouraged as this variable is only used for internal storage.',
    ];

    /**
     * Document template object
     *
     * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
     */
    protected $doc;

    /**
     * Name of the filemount
     *
     * @var string
     */
    protected $title;

    /**
     * sys_file uid
     *
     * @var int
     */
    protected $uid;

    /**
     * The file or folder object that should be renamed
     *
     * @var \TYPO3\CMS\Core\Resource\ResourceInterface $fileOrFolderObject
     */
    protected $fileOrFolderObject;

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

        // @deprecated since TYPO3 v9, will be moved out of __construct() in TYPO3 v10.0
        $this->init($GLOBALS['TYPO3_REQUEST']);
    }

    /**
     * Processes the request, currently everything is handled and put together via "main()"
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
     * Main function, rendering the content of the replace form
     */
    public function main()
    {
        trigger_error('ReplaceFileController->main() will be replaced by protected method renderContent() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        $this->renderContent();
    }

    /**
     * Init
     *
     * @param ServerRequestInterface $request
     * @throws \RuntimeException
     * @throws InsufficientFileAccessPermissionsException
     */
    protected function init(ServerRequestInterface $request): void
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $lang = $this->getLanguageService();

        // Initialize GPvars:
        $this->uid = (int)($parsedBody['uid'] ?? $queryParams['uid'] ?? 0);
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');

        // Cleaning and checking uid
        if ($this->uid > 0) {
            $this->fileOrFolderObject = ResourceFactory::getInstance()
                ->retrieveFileOrFolderObject('file:' . $this->uid);
        }
        if (!$this->fileOrFolderObject) {
            $title = $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:paramError');
            $message = $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:targetNoDir');
            throw new \RuntimeException($title . ': ' . $message, 1436895930);
        }
        if ($this->fileOrFolderObject->getStorage()->getUid() === 0) {
            throw new InsufficientFileAccessPermissionsException(
                'You are not allowed to access files outside your storages',
                1436895931
            );
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

        $pathInfo = [
            'combined_identifier' => $this->fileOrFolderObject->getCombinedIdentifier(),
        ];
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($pathInfo);
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $this->moduleTemplate->addJavaScriptCode(
            'ReplaceFileOnlineJavaScript',
            'function backToList() {top.goToModule("file_FilelistList");}'
        );
    }

    /**
     * Render module content
     */
    protected function renderContent(): void
    {
        // Assign variables used by the fluid template
        $assigns = [];
        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $assigns['moduleUrlTceFile'] = (string)$uriBuilder->buildUriFromRoute('tce_file');
        $assigns['uid'] = $this->uid;
        $assigns['returnUrl'] = $this->returnUrl;

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        // csh button
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('file_rename');
        $buttonBar->addButton($cshButton);

        // Back button
        if ($this->returnUrl) {
            $returnButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($returnButton);
        }

        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:backend/Resources/Private/Templates/File/ReplaceFile.html'
        ));
        $view->assignMultiple($assigns);
        $this->content = $view->render();

        $this->moduleTemplate->setContent($this->content);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
