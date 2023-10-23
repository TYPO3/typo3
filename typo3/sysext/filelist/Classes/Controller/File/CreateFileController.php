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
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\OnlineMedia\Helpers\OnlineMediaHelperRegistry;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Security\FileNameValidator;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Display form to create a new file.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class CreateFileController
{
    /**
     * Set with the target path inputted in &target
     */
    protected string $target = '';

    /**
     * The folder object which is the target directory
     */
    protected ?Folder $folderObject = null;

    /**
     * Return URL of file list module.
     */
    protected string $returnUrl = '';

    protected ModuleTemplate $view;

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly PageRenderer $pageRenderer,
        protected readonly ResourceFactory $resourceFactory,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly OnlineMediaHelperRegistry $onlineMediaHelperRegistry,
    ) {}

    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->view = $this->moduleTemplateFactory->create($request);
        $this->initialize($request);
        $hasPermission = $this->folderObject->getStorage()->checkUserActionPermission('add', 'File');
        $assigns = [
            'target' => $this->target,
            'hasPermission' => $hasPermission,
            'returnUrl' => $this->returnUrl,
        ];

        if ($hasPermission) {
            // Create a list of allowed file extensions with the readable format "youtube, vimeo" etc.
            $fileExtList = [];
            $onlineMediaFileExt = $this->onlineMediaHelperRegistry->getSupportedFileExtensions();
            $fileNameVerifier = GeneralUtility::makeInstance(FileNameValidator::class);
            foreach ($onlineMediaFileExt as $fileExt) {
                if ($fileNameVerifier->isValid('.' . $fileExt)) {
                    $fileExtList[] = strtoupper(htmlspecialchars($fileExt));
                }
            }
            $assigns['fileExtList'] = $fileExtList;

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

        $this->view->assignMultiple($assigns);
        return $this->view->renderResponse('File/CreateFile');
    }

    protected function initialize(ServerRequestInterface $request): void
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->target = $parsedBody['target'] ?? $queryParams['target'] ?? '';
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        // create the folder object
        if ($this->target) {
            $this->folderObject = $this->resourceFactory->getFolderObjectFromCombinedIdentifier($this->target);
        }
        // Cleaning and checking target directory
        if (!$this->folderObject instanceof Folder) {
            $title = $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:paramError');
            $message = $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:targetNoDir');
            throw new \RuntimeException($title . ': ' . $message, 1667565756);
        }
        if ($this->folderObject->getStorage()->getUid() === 0) {
            throw new InsufficientFolderAccessPermissionsException(
                'You are not allowed to access folders outside your storages',
                1667565757
            );
        }

        $this->view->getDocHeaderComponent()->setMetaInformationForResource($this->folderObject);
        if ($this->returnUrl) {
            $buttonBar = $this->view->getDocHeaderComponent()->getButtonBar();
            $backButton = $buttonBar->makeLinkButton()
                ->setHref($this->returnUrl)
                ->setShowLabelText(true)
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
                ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
            $buttonBar->addButton($backButton);
        }

        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/context-menu.js');
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
