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
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script Class for rendering the file editing screen
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class EditFileController
{
    /**
     * Module content accumulated.
     *
     * @var string
     */
    protected $content;

    /**
     * Original input target
     *
     * @var string
     */
    protected $origTarget;

    /**
     * The original target, but validated.
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
     * the file that is being edited on
     *
     * @var File|Folder|null
     */
    protected $fileObject;

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    protected IconFactory $iconFactory;
    protected UriBuilder $uriBuilder;
    protected ResourceFactory $resourceFactory;
    protected ModuleTemplateFactory $moduleTemplateFactory;

    public function __construct(
        IconFactory $iconFactory,
        UriBuilder $uriBuilder,
        ResourceFactory $resourceFactory,
        ModuleTemplateFactory $moduleTemplateFactory
    ) {
        $this->iconFactory = $iconFactory;
        $this->uriBuilder = $uriBuilder;
        $this->resourceFactory = $resourceFactory;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
    }

    /**
     * Processes the request, currently everything is handled and put together via "process()"
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);
        if ($response = $this->process()) {
            return $response;
        }

        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Initialize script class
     *
     * @param ServerRequestInterface $request
     *
     * @throws InsufficientFileAccessPermissionsException
     */
    protected function init(ServerRequestInterface $request): void
    {
        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        // Setting target, which must be a file reference to a file within the mounts.
        $this->target = $this->origTarget = $parsedBody['target'] ?? $queryParams['target'] ?? '';
        // create the file object
        if ($this->target) {
            $this->fileObject = $this->resourceFactory->retrieveFileOrFolderObject($this->target);
        }
        // Cleaning and checking target directory
        if (!$this->fileObject) {
            $title = $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:paramError');
            $message = $this->getLanguageService()->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:targetNoDir');
            throw new \RuntimeException($title . ': ' . $message, 1294586841);
        }
        if ($this->fileObject->getStorage()->getUid() === 0) {
            throw new InsufficientFileAccessPermissionsException(
                'You are not allowed to access files outside your storages',
                1375889832
            );
        }
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl(
            $parsedBody['returnUrl']
                ?? $queryParams['returnUrl']
                ?? (string)$this->uriBuilder->buildUriFromRoute('file_FilelistList', [
                    'id' => $this->fileObject->getParentFolder()->getCombinedIdentifier(),
                ])
        );
    }

    /**
     * Main function, rendering the actual content of the editing page
     *
     * @return ResponseInterface|null Possible redirect response
     */
    protected function process(): ?ResponseInterface
    {
        $dataColumnDefinition = [
            'label' => htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:file'))
                . ' ' . htmlspecialchars($this->target),
            'config' => [
                'type' => 'text',
                'cols' => 48,
                'wrap' => 'OFF',
            ],
            'defaultExtras' => 'fixed-font: enable-tab',
        ];

        $this->getButtonsInternal();
        // Hook: before compiling the output
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['preOutputProcessingHook'] ?? [] as $hookFunction) {
            $hookParameters = [
                'content' => &$this->content,
                'target' => &$this->target,
                'dataColumnDefinition' => &$dataColumnDefinition,
            ];
            GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
        }

        $assigns = [];
        $assigns['moduleUrlTceFile'] = (string)$this->uriBuilder->buildUriFromRoute('tce_file');
        $assigns['fileName'] = $this->fileObject->getName();

        try {
            $extList = $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'];
            if (!$this->fileObject->isTextFile()) {
                // @todo throw a minor exception here, not the global one
                throw new \Exception('Files with that extension are not editable. Allowed extensions are: ' . $extList, 1476050135);
            }

            $fullIdentifier= $this->fileObject->getCombinedIdentifier();
            $returnUrl = (string)$this->uriBuilder->buildUriFromRoute('file_edit', ['target' => $fullIdentifier]);

            // Making the formfields
            $formData = [
                'databaseRow' => [
                    'uid' => 0,
                    'data' => $this->fileObject->getContents(),
                    'target' => $this->fileObject->getUid(),
                    'redirect' => $returnUrl,
                ],
                'tableName' => 'editfile',
                'processedTca' => [
                    'columns' => [
                        'data' => $dataColumnDefinition,
                        'target' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'hidden',
                            ],
                        ],
                        'redirect' => [
                            'config' => [
                                'type' => 'input',
                                'renderType' => 'hidden',
                            ],
                        ],
                    ],
                    'types' => [
                        1 => [
                            'showitem' => 'data,target,redirect',
                        ],
                    ],
                ],
                'recordTypeValue' => 1,
                'inlineStructure' => [],
                'renderType' => 'fullRecordContainer',
            ];

            $resultArray = GeneralUtility::makeInstance(NodeFactory::class)->create($formData)->render();
            $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
            $formResultCompiler->mergeResult($resultArray);

            $form = $formResultCompiler->addCssFiles()
                . $resultArray['html']
                . $formResultCompiler->printNeededJSFunctions();

            $assigns['form'] = $form;
        } catch (\Exception $e) {
            // @todo catch dedicated exceptions, not the global one, if possible
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $e->getMessage(), '', FlashMessage::ERROR, true);

            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);

            return new RedirectResponse($this->returnUrl, 500);
        }

        // Rendering of the output via fluid
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplateRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Templates')]);
        $view->setPartialRootPaths([GeneralUtility::getFileAbsFileName('EXT:backend/Resources/Private/Partials')]);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName(
            'EXT:filelist/Resources/Private/Templates/File/EditFile.html'
        ));
        $view->assignMultiple($assigns);
        $pageContent = $view->render();

        // Hook: after compiling the output
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['postOutputProcessingHook'] ?? [] as $hookFunction) {
            $hookParameters = [
                'pageContent' => &$pageContent,
                'target' => &$this->target,
            ];
            GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
        }

        $this->content .= $pageContent;
        $this->moduleTemplate->setContent($this->content);
        return null;
    }

    /**
     * Builds the buttons for the docheader
     */
    protected function getButtonsInternal(): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        $lang = $this->getLanguageService();
        // CSH button
        $helpButton = $buttonBar->makeHelpButton()
            ->setFieldName('file_edit')
            ->setModuleName('xMOD_csh_corebe');
        $buttonBar->addButton($helpButton);

        // Save button
        $saveButton = $buttonBar->makeInputButton()
            ->setName('_save')
            ->setValue('1')
            ->setForm('EditFileController')
            ->setShowLabelText(true)
            ->setTitle($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:file_edit.php.submit'))
            ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL));
        $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 20);

        // Cancel button
        $closeButton = $buttonBar->makeLinkButton()
            ->setShowLabelText(true)
            ->setHref($this->returnUrl)
            ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel'))
            ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL));
        $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
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

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
