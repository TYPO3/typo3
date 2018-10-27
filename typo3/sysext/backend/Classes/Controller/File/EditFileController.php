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
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\HttpUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Script Class for rendering the file editing screen
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class EditFileController
{
    use PublicMethodDeprecationTrait;
    use PublicPropertyDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'main' => 'Using EditFileController::main() is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    private $deprecatedPublicProperties = [
        'origTarget' => 'Using $origTarget of class EditFileController from outside is discouraged, as this variable is only used for internal storage.',
        'target' => 'Using $target of class EditFileController from outside is discouraged, as this variable is only used for internal storage.',
        'returnUrl' => 'Using $returnUrl of class EditFileController from outside is discouraged, as this variable is only used for internal storage.',
        'content' => 'Using $content of class EditFileController from outside is discouraged, as this variable is only used for internal storage.',
        'title' => 'Using $title of class EditFileController from outside is discouraged, as this variable is only used for internal storage.',
        'doc' => 'Using $doc of class EditFileController from outside is discouraged, as this variable is only used for internal storage.',
    ];
    /**
     * Module content accumulated.
     *
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $title;

    /**
     * Document template object
     *
     * @var DocumentTemplate
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0, unused
     */
    protected $doc;

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
     * @var \TYPO3\CMS\Core\Resource\AbstractFile
     */
    protected $fileObject;

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
        if ($response = $this->process()) {
            return $response;
        }

        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Main function, rendering the actual content of the editing page
     */
    protected function main()
    {
        $response = $this->process();

        if ($response instanceof RedirectResponse) {
            HttpUtility::redirect($response->getHeaderLine('location'), $response->getStatusCode());
        }
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
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        // Setting target, which must be a file reference to a file within the mounts.
        $this->target = $this->origTarget = $parsedBody['target'] ?? $queryParams['target'] ?? '';
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');
        // create the file object
        if ($this->target) {
            $this->fileObject = ResourceFactory::getInstance()
                ->retrieveFileOrFolderObject($this->target);
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

        // Setting the title and the icon
        $icon = $this->moduleTemplate->getIconFactory()->getIcon('apps-filetree-root', Icon::SIZE_SMALL)->render();
        $this->title = $icon
            . htmlspecialchars(
                $this->fileObject->getStorage()->getName()
            ) . ': ' . htmlspecialchars(
                $this->fileObject->getIdentifier()
            );

        // Setting template object
        $this->moduleTemplate->addJavaScriptCode(
            'FileEditBackToList',
            'function backToList() {
				top.goToModule("file_FilelistList");
			}'
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
            'defaultExtras' => 'fixed-font: enable-tab'
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
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $assigns['moduleUrlTceFile'] = (string)$uriBuilder->buildUriFromRoute('tce_file');
        $assigns['fileName'] = $this->fileObject->getName();

        $extList = $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'];
        try {
            if (!$extList || !GeneralUtility::inList($extList, $this->fileObject->getExtension())) {
                // @todo throw a minor exception here, not the global one
                throw new \Exception('Files with that extension are not editable. Allowed extensions are: ' . $extList, 1476050135);
            }

            // Making the formfields
            $hValue = (string)$uriBuilder->buildUriFromRoute('file_edit', [
                'target' => $this->origTarget,
                'returnUrl' => $this->returnUrl
            ]);

            $formData = [
                'databaseRow' => [
                    'uid' => 0,
                    'data' => $this->fileObject->getContents(),
                    'target' => $this->fileObject->getUid(),
                    'redirect' => $hValue,
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
            'EXT:backend/Resources/Private/Templates/File/EditFile.html'
        ));
        $view->assignMultiple($assigns);
        $pageContent = $view->render();

        // Hook: after compiling the output
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['postOutputProcessingHook'] ?? [] as $hookFunction) {
            $hookParameters = [
                'pageContent' => &$pageContent,
                'target' => &$this->target
            ];
            GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
        }

        $this->content .= $pageContent;
        $this->moduleTemplate->setContent($this->content);
        return null;
    }

    /**
     * Builds the buttons for the docheader and returns them as an
     *
     * @deprecated since TYPO3 v9, will be set protected in TYPO3 v10.0
     */
    public function getButtons()
    {
        trigger_error('EditFileController->getButtons() will be replaced by protected method getButtonsInternal() in TYPO3 v10.0. Do not call from other extension.', E_USER_DEPRECATED);
        $this->getButtonsInternal();
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
            ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:file_edit.php.submit'))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL));

        // Save and Close button
        $saveAndCloseButton = $buttonBar->makeInputButton()
            ->setName('_saveandclosedok')
            ->setValue('1')
            ->setForm('EditFileController')
            ->setOnClick(
                'document.editform.elements.namedItem("data[editfile][0][redirect]").value='
                . GeneralUtility::quoteJSvalue($this->returnUrl)
                . ';'
            )
            ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:file_edit.php.saveAndClose'))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon(
                'actions-document-save-close',
                Icon::SIZE_SMALL
            ));

        $splitButton = $buttonBar->makeSplitButton()
            ->addItem($saveButton)
            ->addItem($saveAndCloseButton);
        $buttonBar->addButton($splitButton, ButtonBar::BUTTON_POSITION_LEFT, 20);

        // Cancel button
        $closeButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setOnClick('backToList(); return false;')
            ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel'))
            ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-close', Icon::SIZE_SMALL));
        $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 10);

        // Make shortcut:
        $shortButton = $buttonBar->makeShortcutButton()
            ->setModuleName('file_edit')
            ->setGetVariables(['target']);
        $buttonBar->addButton($shortButton);
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
