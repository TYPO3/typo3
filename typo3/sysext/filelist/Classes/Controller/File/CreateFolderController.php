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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script class for the create-new script
 *
 * Displays forms for creating folders (1 to 10), a media asset or a new file.
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class CreateFolderController
{
    /**
     * @var int
     */
    protected $folderNumber = 10;

    /**
     * @var int
     */
    protected $number;

    /**
     * Set with the target path inputted in &target
     *
     * @var string
     */
    protected $target;

    /**
     * The folder object which is  the target directory
     *
     * @var \TYPO3\CMS\Core\Resource\Folder $folderObject
     */
    protected $folderObject;

    /**
     * Return URL of list module.
     *
     * @var string
     */
    protected $returnUrl;

    /**
     * @var array
     */
    protected $pathInfo;

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
     * Processes the request, currently everything is handled and put together via "main()"
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->init($request);
        $this->main();
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * @param ServerRequestInterface $request
     *
     * @throws InsufficientFolderAccessPermissionsException
     * @throws \RuntimeException
     */
    protected function init(ServerRequestInterface $request): void
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->number = $parsedBody['number'] ?? $queryParams['number'] ?? 0;
        $this->target = ($combinedIdentifier = $parsedBody['target'] ?? $queryParams['target'] ?? '');
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        // create the folder object
        if ($combinedIdentifier) {
            $this->folderObject = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($combinedIdentifier);
        }
        // Cleaning and checking target directory
        if (!$this->folderObject) {
            $title = $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:paramError');
            $message = $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:targetNoDir');
            throw new \RuntimeException($title . ': ' . $message, 1294586845);
        }
        if ($this->folderObject->getStorage()->getUid() === 0) {
            throw new InsufficientFolderAccessPermissionsException(
                'You are not allowed to access folders outside your storages',
                1375889838
            );
        }

        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformationForResource($this->folderObject);
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/CreateFolder');
    }

    /**
     * Main function, rendering the main module content
     */
    protected function main()
    {
        $lang = $this->getLanguageService();
        $assigns = [
            'target' => $this->target,
            'confirmTitle' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:pleaseConfirm'),
            'confirmText' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:mess.redraw'),
            'selfUrl' => (string)$this->uriBuilder->buildUriFromRoute('file_newfolder', [
                'target' => $this->target,
                'returnUrl' => $this->returnUrl,
                'number' => 'AMOUNT',
            ]),
        ];
        if ($this->folderObject->checkActionPermission('add')) {
            $assigns['moduleUrlTceFile'] = (string)$this->uriBuilder->buildUriFromRoute('tce_file');
            $assigns['cshFileNewFolder'] = BackendUtility::cshItem('xMOD_csh_corebe', 'file_newfolder');
            // Making the selector box for the number of concurrent folder-creations
            $this->number = MathUtility::forceIntegerInRange($this->number, 1, 10);
            for ($a = 1; $a <= $this->folderNumber; $a++) {
                $options = [];
                $options['value'] = $a;
                $options['selected'] = ($this->number == $a ? ' selected="selected"' : '');
                $assigns['options'][] = $options;
            }
            // Making the number of new-folder boxes needed:
            for ($a = 0; $a < $this->number; $a++) {
                $folder = [];
                $folder['this'] = $a;
                $folder['next'] = $a + 1;
                $assigns['folders'][] = $folder;
            }
            // Making submit button for folder creation:
            $assigns['returnUrl'] = $this->returnUrl;
        }

        if ($this->folderObject->getStorage()->checkUserActionPermission('add', 'File')) {
            $assigns['moduleUrlOnlineMedia'] = (string)$this->uriBuilder->buildUriFromRoute('online_media');
            $assigns['cshFileNewMedia'] = BackendUtility::cshItem('xMOD_csh_corebe', 'file_newMedia');
            // Create a list of allowed file extensions with the readable format "youtube, vimeo" etc.
            $fileExtList = [];
            $onlineMediaFileExt = GeneralUtility::makeInstance(OnlineMediaHelperRegistry::class)->getSupportedFileExtensions();
            $fileNameVerifier = GeneralUtility::makeInstance(FileNameValidator::class);
            foreach ($onlineMediaFileExt as $fileExt) {
                if ($fileNameVerifier->isValid('.' . $fileExt)) {
                    $fileExtList[] = strtoupper(htmlspecialchars($fileExt));
                }
            }
            $assigns['fileExtList'] = $fileExtList;

            $assigns['moduleUrlTceFile'] = (string)$this->uriBuilder->buildUriFromRoute('tce_file');
            $assigns['cshFileNewFile'] = BackendUtility::cshItem('xMOD_csh_corebe', 'file_newfile');
            // Create a list of allowed file extensions with a text format "*.txt, *.css" etc.
            $fileExtList = [];
            $textFileExt = GeneralUtility::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'], true);
            foreach ($textFileExt as $fileExt) {
                if ($fileNameVerifier->isValid('.' . $fileExt)) {
                    $fileExtList[] = strtoupper(htmlspecialchars($fileExt));
                }
            }
            $assigns['txtFileExtList'] = $fileExtList;
        }

        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        // CSH button
        $helpButton = $buttonBar->makeHelpButton()
            ->setFieldName('file_new')
            ->setModuleName('xMOD_csh_corebe');
        $buttonBar->addButton($helpButton);

        // Back
        if ($this->returnUrl) {
            $backButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton);
        }

        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:filelist/Resources/Private/Templates/File/CreateFolder.html'
        ));
        $view->assignMultiple($assigns);
        $this->moduleTemplate->setContent($view->render());
    }

    /**
     * Returns LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
