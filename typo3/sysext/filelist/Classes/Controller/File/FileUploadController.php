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
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Upload files to a folder. Reachable from click-menu on folders.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class FileUploadController
{
    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ResourceFactory $resourceFactory,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
    ) {
    }

    /**
     * Render upload form.
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();
        $languageService = $this->getLanguageService();
        $view = $this->moduleTemplateFactory->create($request);

        $targetFolderCombinedIdentifier = $parsedBody['target'] ?? $queryParams['target'] ?? '';
        $folder = $this->resourceFactory->retrieveFileOrFolderObject($targetFolderCombinedIdentifier);

        if (!$folder instanceof FolderInterface
            || $folder->getStorage()->getUid() === 0
        ) {
            throw new InsufficientFolderAccessPermissionsException('You are not allowed to access folders outside your storages, or the folder couldn\'t be resolved', 1375889834);
        }

        $returnUrl = GeneralUtility::sanitizeLocalUrl(
            $parsedBody['returnUrl']
            ?? $queryParams['returnUrl']
            ?? (string)$this->uriBuilder->buildUriFromRoute('file_list', [
                'id' => rawurlencode($targetFolderCombinedIdentifier),
            ])
        );

        $view->getDocHeaderComponent()->setMetaInformationForResource($folder);
        $this->addDocHeaderButtons($view, $returnUrl);
        $view->setTitle($languageService->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:file_upload.php.pagetitle'));

        $view->assignMultiple([
            'returnUrl' => $returnUrl,
            'folderCombinedIdentifier' => $targetFolderCombinedIdentifier,
        ]);

        return $view->renderResponse('File/UploadFile');
    }

    protected function addDocHeaderButtons(ModuleTemplate $view, string $returnUrl)
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();

        $backButton = $buttonBar->makeLinkButton()
            ->setHref($returnUrl)
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.goBack'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-view-go-back', Icon::SIZE_SMALL));
        $buttonBar->addButton($backButton);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
