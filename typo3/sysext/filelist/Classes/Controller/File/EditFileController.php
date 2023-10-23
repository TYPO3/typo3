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

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InvalidFileException;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\Event\ModifyEditFileFormDataEvent;

/**
 * Edit text files via FormEngine. Reachable via FileList module "Edit content".
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class EditFileController
{
    protected array $dataColumnTca = [
        'label' => '',
        'config' => [
            'type' => 'text',
            'cols' => 48,
            'wrap' => 'off',
            'enableTabulator' => true,
            'fixedFont' => true,
        ],
    ];

    protected array $formEngineData = [
        'databaseRow' => [
            'uid' => 0,
            'data' => '',
            'target' => 0,
            'redirect' => '',
        ],
        'tableName' => 'editfile',
        'processedTca' => [
            'columns' => [
                'data' => [],
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

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ResourceFactory $resourceFactory,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly ResponseFactory $responseFactory,
        protected readonly StreamFactoryInterface $streamFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    /**
     * Render the edit file content form using FormEngine.
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $view = $this->moduleTemplateFactory->create($request);
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $combinedIdentifier = $parsedBody['target'] ?? $queryParams['target'] ?? '';
        $file = $this->resourceFactory->retrieveFileOrFolderObject($combinedIdentifier);

        if (!$file instanceof FileInterface) {
            throw new InvalidFileException('Referenced target "' . $combinedIdentifier . '" could not be resolved to a valid file', 1294586841);
        }
        if ($file->getStorage()->getUid() === 0) {
            throw new InsufficientFileAccessPermissionsException('You are not allowed to access files outside your storages', 1375889832);
        }

        $returnUrl = GeneralUtility::sanitizeLocalUrl(
            $parsedBody['returnUrl']
            ?? $queryParams['returnUrl']
            ?? (string)$this->uriBuilder->buildUriFromRoute('media_management', [
                'id' => $file->getParentFolder()->getCombinedIdentifier(),
            ])
        );

        if (!$file->isTextFile()) {
            $extList = $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'];
            $view->addFlashMessage('Files with that extension are not editable. Allowed extensions are: ' . $extList, '', ContextualFeedbackSeverity::ERROR, true);
            return $this->responseFactory->createResponse(400)->withHeader('location', $returnUrl);
        }

        $this->addDocHeaderButtons($view, $returnUrl);

        $dataColumnDefinition = $this->dataColumnTca;
        $dataColumnDefinition['label'] = htmlspecialchars($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:file')) . ' ' . htmlspecialchars($combinedIdentifier);

        $formData = $this->formEngineData;
        $formData['databaseRow']['data'] = $file->getContents();
        $formData['databaseRow']['target'] = $file->getUid();
        $formData['databaseRow']['redirect'] = (string)$this->uriBuilder->buildUriFromRoute('file_edit', ['target' => $combinedIdentifier]);
        $formData['processedTca']['columns']['data'] = $dataColumnDefinition;

        $formData = $this->eventDispatcher->dispatch(
            new ModifyEditFileFormDataEvent($formData, $file, $request)
        )->getFormData();

        $resultArray = GeneralUtility::makeInstance(NodeFactory::class)->create($formData)->render();
        $formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
        $formResultCompiler->mergeResult($resultArray);

        // Rendering of the output via fluid
        $view->assignMultiple([
            'moduleUrlTceFile' => (string)$this->uriBuilder->buildUriFromRoute('tce_file'),
            'fileName' => $file->getName(),
            'form' => $formResultCompiler->addCssFiles() . ($resultArray['html'] ?? '') . $formResultCompiler->printNeededJSFunctions(),
        ]);
        $content = $view->render('File/EditFile');

        return $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8')
            ->withBody($this->streamFactory->createStream($content));
    }

    protected function addDocHeaderButtons(ModuleTemplate $view, string $returnUrl): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();

        // Save button
        $saveButton = $buttonBar->makeInputButton()
            ->setName('_save')
            ->setValue('1')
            ->setForm('EditFileController')
            ->setShowLabelText(true)
            ->setTitle($languageService->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:file_edit.php.submit'))
            ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL));
        $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 20);

        // Cancel button
        $closeButton = $buttonBar->makeLinkButton()
            ->setShowLabelText(true)
            ->setHref($returnUrl)
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel'))
            ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL));
        $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
