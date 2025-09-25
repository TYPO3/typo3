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

namespace TYPO3\CMS\Install\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Exception\RfcComplianceException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Toolbar\InformationStatus;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Mail\FluidEmail;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Type\File\ImageInfo;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Frontend\Imaging\GifBuilder;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;
use TYPO3\CMS\Install\FolderStructure\DefaultPermissionsCheck;
use TYPO3\CMS\Install\Service\LateBootService;
use TYPO3\CMS\Install\SystemEnvironment\Check;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck;
use TYPO3\CMS\Install\SystemEnvironment\ServerResponse\ServerResponseCheck;
use TYPO3\CMS\Install\SystemEnvironment\SetupCheck;
use TYPO3\CMS\Install\WebserverType;

/**
 * Environment controller
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class EnvironmentController extends AbstractController
{
    private const IMAGE_FILE_EXT = ['gif', 'jpg', 'png', 'tif', 'ai', 'pdf', 'webp', 'avif'];
    private const TEST_REFERENCE_PATH = __DIR__ . '/../../Resources/Public/Images/TestReference';

    public function __construct(
        private readonly LateBootService $lateBootService,
        private readonly FormProtectionFactory $formProtectionFactory,
        private readonly MailerInterface $mailer,
    ) {}

    /**
     * Main "show the cards" view
     */
    public function cardsAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView($request);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Environment/Cards'),
        ]);
    }

    /**
     * System Information Get Data action
     */
    public function systemInformationGetDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView($request);
        $view->assignMultiple([
            'systemInformationCgiDetected' => Environment::isRunningOnCgiServer(),
            'systemInformationDatabaseConnections' => $this->getDatabaseConnectionInformation(),
            'systemInformationOperatingSystem' => Environment::isWindows() ? 'Windows' : 'Unix',
            'systemInformationApplicationContext' => $this->getApplicationContextInformation(),
            'phpVersion' => PHP_VERSION,
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Environment/SystemInformation'),
        ]);
    }

    /**
     * System Information Get Data action
     */
    public function phpInfoGetDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView($request);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Environment/PhpInfo'),
        ]);
    }

    /**
     * Get environment status
     */
    public function environmentCheckGetStatusAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView($request);
        $messageQueue = new FlashMessageQueue('install');
        $checkMessages = (new Check())->getStatus();
        foreach ($checkMessages as $message) {
            $messageQueue->enqueue($message);
        }
        $setupMessages = (new SetupCheck())->getStatus();
        foreach ($setupMessages as $message) {
            $messageQueue->enqueue($message);
        }
        $databaseMessages = (new DatabaseCheck())->getStatus();
        foreach ($databaseMessages as $message) {
            $messageQueue->enqueue($message);
        }
        $container = $this->lateBootService->getContainer();
        $uriBuilder = $container->get(UriBuilder::class);
        $serverResponseMessages = (new ServerResponseCheck($uriBuilder, false))->getStatus();
        foreach ($serverResponseMessages as $message) {
            $messageQueue->enqueue($message);
        }
        return new JsonResponse([
            'success' => true,
            'status' => [
                'error' => $messageQueue->getAllMessages(ContextualFeedbackSeverity::ERROR),
                'warning' => $messageQueue->getAllMessages(ContextualFeedbackSeverity::WARNING),
                'ok' => $messageQueue->getAllMessages(ContextualFeedbackSeverity::OK),
                'information' => $messageQueue->getAllMessages(ContextualFeedbackSeverity::INFO),
                'notice' => $messageQueue->getAllMessages(ContextualFeedbackSeverity::NOTICE),
            ],
            'html' => $view->render('Environment/EnvironmentCheck'),
            'buttons' => [
                [
                    'btnClass' => 'btn-default t3js-environmentCheck-execute',
                    'text' => 'Run tests again',
                ],
            ],
        ]);
    }

    /**
     * Get folder structure status
     */
    public function folderStructureGetStatusAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView($request);
        $folderStructureFactory = GeneralUtility::makeInstance(DefaultFactory::class);
        $structureFacade = $folderStructureFactory->getStructure(WebserverType::fromRequest($request));

        $structureMessages = $structureFacade->getStatus();
        $errorQueue = new FlashMessageQueue('install');
        $okQueue = new FlashMessageQueue('install');
        foreach ($structureMessages as $message) {
            if ($message->getSeverity() === ContextualFeedbackSeverity::ERROR
                || $message->getSeverity() === ContextualFeedbackSeverity::WARNING
            ) {
                $errorQueue->enqueue($message);
            } else {
                $okQueue->enqueue($message);
            }
        }

        $permissionCheck = GeneralUtility::makeInstance(DefaultPermissionsCheck::class);

        $view->assign('publicPath', Environment::getPublicPath());

        $buttons = [];
        if ($errorQueue->count() > 0) {
            $buttons[] = [
                'btnClass' => 'btn-default t3js-folderStructure-errors-fix',
                'text' => 'Try to fix file and folder permissions',
            ];
        }

        return new JsonResponse([
            'success' => true,
            'errorStatus' => $errorQueue,
            'okStatus' => $okQueue,
            'folderStructureFilePermissionStatus' => $permissionCheck->getMaskStatus('fileCreateMask'),
            'folderStructureDirectoryPermissionStatus' => $permissionCheck->getMaskStatus('folderCreateMask'),
            'html' => $view->render('Environment/FolderStructure'),
            'buttons' => $buttons,
        ]);
    }

    /**
     * Try to fix folder structure errors
     */
    public function folderStructureFixAction(ServerRequestInterface $request): ResponseInterface
    {
        $folderStructureFactory = GeneralUtility::makeInstance(DefaultFactory::class);
        $structureFacade = $folderStructureFactory->getStructure(WebserverType::fromRequest($request));
        $fixedStatusObjects = $structureFacade->fix();
        return new JsonResponse([
            'success' => true,
            'fixedStatus' => $fixedStatusObjects,
        ]);
    }

    /**
     * System Information Get Data action
     */
    public function mailTestGetDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView($request);
        $formProtection = $this->formProtectionFactory->createFromRequest($request);
        $isSendPossible = true;
        $messages = new FlashMessageQueue('install');
        $senderEmail = $this->getSenderEmailAddress();
        if ($senderEmail === '') {
            $messages->enqueue(new FlashMessage(
                'Sender email address is not configured. Please configure $GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][\'defaultMailFromAddress\'].',
                'Can not send mail',
                ContextualFeedbackSeverity::ERROR
            ));
            $isSendPossible = false;
        } elseif (!GeneralUtility::validEmail($senderEmail)) {
            $messages->enqueue(new FlashMessage(
                sprintf(
                    'Sender email address <%s> is configured, but is not a valid email. Please use a valid email address in $GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][\'defaultMailFromAddress\'].',
                    $senderEmail
                ),
                'Can not send mail',
                ContextualFeedbackSeverity::ERROR
            ));
            $isSendPossible = false;
        }

        $view->assignMultiple([
            'mailTestToken' => $formProtection->generateToken('installTool', 'mailTest'),
            'mailTestSenderAddress' => $this->getSenderEmailAddress(),
            'isSendPossible' => $isSendPossible,
        ]);

        return new JsonResponse([
            'success' => true,
            'messages' => $messages,
            'sendPossible' => $isSendPossible,
            'html' => $view->render('Environment/MailTest'),
            'buttons' => [
                [
                    'btnClass' => 'btn-default t3js-mailTest-execute',
                    'text' => 'Send test mail',
                ],
            ],
        ]);
    }

    /**
     *  Send a test mail
     */
    public function mailTestAction(ServerRequestInterface $request): ResponseInterface
    {
        $container = $this->lateBootService->getContainer();
        $backup = $this->lateBootService->makeCurrent($container);
        $messages = new FlashMessageQueue('install');
        $recipient = $request->getParsedBody()['install']['email'];

        if (empty($recipient) || !GeneralUtility::validEmail($recipient)) {
            $messages->enqueue(new FlashMessage(
                'Given recipient address is not a valid email address.',
                'Mail not sent',
                ContextualFeedbackSeverity::ERROR
            ));
        } else {
            try {
                $variables = [
                    'headline' => 'TYPO3 Test Mail',
                    'introduction' => 'Hey TYPO3 Administrator',
                    'content' => 'Seems like your favorite TYPO3 installation can send out emails!',
                ];
                $mailMessage = GeneralUtility::makeInstance(FluidEmail::class);
                $mailMessage
                    ->to($recipient)
                    ->from(new Address($this->getSenderEmailAddress(), $this->getSenderEmailName()))
                    ->subject($this->getEmailSubject())
                    ->setRequest($request)
                    ->assignMultiple($variables);

                $this->mailer->send($mailMessage);
                $messages->enqueue(new FlashMessage(
                    'Recipient: ' . $recipient,
                    'Test mail sent'
                ));
            } catch (RfcComplianceException $exception) {
                $messages->enqueue(new FlashMessage(
                    'Please verify $GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][\'defaultMailFromAddress\'] is a valid mail address.'
                    . ' Error message: ' . $exception->getMessage(),
                    'RFC compliance problem',
                    ContextualFeedbackSeverity::ERROR
                ));
            } catch (\Throwable $throwable) {
                $messages->enqueue(new FlashMessage(
                    'Please verify $GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][*] settings are valid.'
                    . ' Error message: ' . $throwable->getMessage(),
                    'Could not deliver mail',
                    ContextualFeedbackSeverity::ERROR
                ));
            }
        }
        $this->lateBootService->makeCurrent(null, $backup);
        return new JsonResponse([
            'success' => true,
            'status' => $messages,
        ]);
    }

    /**
     * System Information Get Data action
     */
    public function imageProcessingGetDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeView($request);
        $view->assignMultiple([
            'imageProcessingProcessor' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] === 'GraphicsMagick' ? 'GraphicsMagick' : 'ImageMagick',
            'imageProcessingEnabled' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled'],
            'imageProcessingPath' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'],
            'imageProcessingVersion' => $this->determineImageMagickVersion(),
            'imageProcessingEffects' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_effects'],
            'imageProcessingGdlibEnabled' => class_exists(\GdImage::class),
            'imageProcessingFileFormats' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render('Environment/ImageProcessing'),
            'buttons' => [
                [
                    'btnClass' => 'btn-default disabled t3js-imageProcessing-execute',
                    'text' => 'Run image tests again',
                ],
            ],
        ]);
    }

    /**
     * Create true type font test image
     */
    public function imageProcessingTrueTypeAction(): ResponseInterface
    {
        $image = @imagecreate(200, 50);
        imagecolorallocate($image, 255, 255, 55);
        $textColor = imagecolorallocate($image, 233, 14, 91);
        @imagettftext(
            $image,
            20 / 96.0 * 72, // As in compensateFontSizeBasedOnFreetypeDpi
            0,
            10,
            20,
            $textColor,
            ExtensionManagementUtility::extPath('install') . 'Resources/Private/Font/vera.ttf',
            'Testing true type'
        );
        $outputFile = Environment::getPublicPath() . '/typo3temp/assets/images/installTool-' . StringUtility::getUniqueId('createTrueTypeFontTestImage') . '.gif';
        @imagegif($image, $outputFile);
        $fileExists = file_exists($outputFile);
        if ($fileExists) {
            GeneralUtility::fixPermissions($outputFile);
        }
        $result = [
            'fileExists' => $fileExists,
            'referenceFile' => self::TEST_REFERENCE_PATH . '/Font.gif',
        ];
        if ($fileExists) {
            $result['outputFile'] = $outputFile;
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Convert to jpg from jpg
     */
    public function imageProcessingReadJpgAction(): ResponseInterface
    {
        return $this->convertImageFormatsToJpg('jpg');
    }

    /**
     * Convert to jpg from webp
     */
    public function imageProcessingReadWebpAction(): ResponseInterface
    {
        return $this->convertImageFormatsToJpg('webp');
    }

    /**
     * Convert to jpg from avif
     */
    public function imageProcessingReadAvifAction(): ResponseInterface
    {
        return $this->convertImageFormatsToJpg('avif');
    }

    /**
     * Convert to jpg from gif
     */
    public function imageProcessingReadGifAction(): ResponseInterface
    {
        return $this->convertImageFormatsToJpg('gif');
    }

    /**
     * Convert to jpg from png
     */
    public function imageProcessingReadPngAction(): ResponseInterface
    {
        return $this->convertImageFormatsToJpg('png');
    }

    /**
     * Convert to jpg from tif
     */
    public function imageProcessingReadTifAction(): ResponseInterface
    {
        return $this->convertImageFormatsToJpg('tif');
    }

    /**
     * Convert to jpg from pdf
     */
    public function imageProcessingReadPdfAction(): ResponseInterface
    {
        return $this->convertImageFormatsToJpg('pdf');
    }

    /**
     * Convert to jpg from ai
     */
    public function imageProcessingReadAiAction(): ResponseInterface
    {
        return $this->convertImageFormatsToJpg('ai');
    }

    /**
     * Writing gif test
     */
    public function imageProcessingWriteGifAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $inputFile = $imageBasePath . 'TestInput/Test.gif';
        $imageService = $this->initializeGraphicalFunctions();
        $imageService->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('write-gif');
        $imResult = $imageService->resize($inputFile, 'gif', '300', '', '', [], true);
        $messages = new FlashMessageQueue('install');
        if ($imResult !== null && $imResult->isFile()) {
            $result = [
                'status' => $messages,
                'fileExists' => true,
                'outputFile' => $imResult->getRealPath(),
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Write-gif.gif',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Writing png test
     */
    public function imageProcessingWritePngAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $inputFile = $imageBasePath . 'TestInput/Test.png';
        $imageService = $this->initializeGraphicalFunctions();
        $imageService->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('write-png');
        $imResult = $imageService->resize($inputFile, 'png', '300', '', '', [], true);
        if ($imResult !== null && $imResult->isFile()) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult->getRealPath(),
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Write-png.png',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }
    /**
     * Writing webp test
     */
    public function imageProcessingWriteWebpAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $inputFile = $imageBasePath . 'TestInput/Test.webp';
        $imageService = $this->initializeGraphicalFunctions();
        $imageService->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('write-webp');
        $imResult = $imageService->resize($inputFile, 'webp', '300', '', '', [], true);
        if ($imResult !== null && $imResult->isFile()) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult->getRealPath(),
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Write-webp.webp',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Writing avif test
     */
    public function imageProcessingWriteAvifAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $inputFile = $imageBasePath . 'TestInput/Test.avif';
        $imageService = $this->initializeGraphicalFunctions();
        $imageService->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('write-avif');
        $imResult = $imageService->resize($inputFile, 'avif', '300', '', '', [], true);
        if ($imResult !== null && $imResult->isFile()) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult->getRealPath(),
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Write-avif.avif',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->avifImageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Scaling transparent files - gif to gif
     */
    public function imageProcessingGifToGifAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageService = $this->initializeGraphicalFunctions();
        $inputFile = $imageBasePath . 'TestInput/Transparent.gif';
        $imageService->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('scale-gif');
        $imResult = $imageService->resize($inputFile, 'gif', '300', '', '', [], true);
        if ($imResult !== null && $imResult->isFile()) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult->getRealPath(),
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Scale-gif.gif',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Scaling transparent files - png to png
     */
    public function imageProcessingPngToPngAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageService = $this->initializeGraphicalFunctions();
        $inputFile = $imageBasePath . 'TestInput/Transparent.png';
        $imageService->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('scale-png');
        $imResult = $imageService->resize($inputFile, 'png', '300', '', '', [], true);
        if ($imResult !== null && $imResult->isFile()) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult->getRealPath(),
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Scale-png.png',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Scaling transparent files - gif to jpg
     */
    public function imageProcessingGifToJpgAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageService = $this->initializeGraphicalFunctions();
        $inputFile = $imageBasePath . 'TestInput/Transparent.gif';
        $imageService->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('scale-jpg');
        $imResult = $imageService->resize($inputFile, 'jpg', '300', '', '-opaque white -background white -flatten', [], true);
        if ($imResult !== null && $imResult->isFile()) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult->getRealPath(),
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Scale-jpg.jpg',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Scaling transparent files - svg to webp (note black/white background)
     */
    public function imageProcessingSvgToWebpAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageService = $this->initializeGraphicalFunctions();
        $inputFile = $imageBasePath . 'TestInput/Transparent.svg';
        $imageService->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('transparent-svg-webp');
        $imResult = $imageService->resize($inputFile, 'webp', '300', '', '-flatten', [], true);
        if ($imResult !== null && $imResult->isFile()) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult->getRealPath(),
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Convert-svg-transparent.webp',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Scaling transparent files - svg to png (note black/white background)
     */
    public function imageProcessingSvgToPngAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageService = $this->initializeGraphicalFunctions();
        $inputFile = $imageBasePath . 'TestInput/Transparent.svg';
        $imageService->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('transparent-svg-webp');
        $imResult = $imageService->resize($inputFile, 'png', '300', '', '-flatten', [], true);
        if ($imResult !== null && $imResult->isFile()) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult->getRealPath(),
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Convert-svg-transparent.png',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Converting jpg to webp
     */
    public function imageProcessingJpgToWebpAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageService = $this->initializeGraphicalFunctions();
        $inputFile = $imageBasePath . 'TestInput/Test.jpg';
        $imageService->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('read-webp');
        $imResult = $imageService->resize($inputFile, 'webp', '300', '', '', [], true);
        if ($imResult !== null) {
            $result = [
                'fileExists' => $imResult->isFile(),
                'outputFile' => $imResult->getRealPath(),
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Convert-webp.webp',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Converting jpg to avif
     */
    public function imageProcessingJpgToAvifAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageService = $this->initializeGraphicalFunctions();
        $inputFile = $imageBasePath . 'TestInput/Test.jpg';
        $imageService->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('read-avif');
        $imResult = $imageService->resize($inputFile, 'avif', '300', '', '', [], true);
        if ($imResult !== null) {
            $result = [
                'fileExists' => $imResult->isFile(),
                'outputFile' => $imResult->getRealPath(),
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Convert-avif.avif',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->avifImageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Converting png to webp
     */
    public function imageProcessingPngToWebpAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageService = $this->initializeGraphicalFunctions();
        $inputFile = $imageBasePath . 'TestInput/Transparent.png';
        $imageService->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('read-webp');
        $imResult = $imageService->resize($inputFile, 'webp', '300', '', '', [], true);
        if ($imResult !== null) {
            $result = [
                'fileExists' => $imResult->isFile(),
                'outputFile' => $imResult->getRealPath(),
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Convert-webp-transparent.webp',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Converting png to avif
     */
    public function imageProcessingPngToAvifAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageService = $this->initializeGraphicalFunctions();
        $inputFile = $imageBasePath . 'TestInput/Transparent.png';
        $imageService->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('read-avif');
        $imResult = $imageService->resize($inputFile, 'avif', '300', '', '', [], true);
        if ($imResult !== null) {
            $result = [
                'fileExists' => $imResult->isFile(),
                'outputFile' => $imResult->getRealPath(),
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Convert-avif-transparent.avif',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->avifImageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Combine images with gif mask
     */
    public function imageProcessingCombineGifMaskAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageService = $this->initializeGraphicalFunctions();
        $inputFile = $imageBasePath . 'TestInput/BackgroundOrange.gif';
        $overlayFile = $imageBasePath . 'TestInput/Test.jpg';
        $maskFile = $imageBasePath . 'TestInput/MaskBlackWhite.gif';
        $resultFile = $this->getImagesPath() . 'installTool-' . StringUtility::getUniqueId($imageService->alternativeOutputKey . 'combine1') . '.jpg';
        $imageService->combineExec($inputFile, $overlayFile, $maskFile, $resultFile);
        $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $resultFile);
        if ($imageInfo->getWidth() > 0) {
            $result = [
                'fileExists' => true,
                'outputFile' => $resultFile,
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Combine-1.jpg',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Combine images with jpg mask
     */
    public function imageProcessingCombineJpgMaskAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageService = $this->initializeGraphicalFunctions();
        $inputFile = $imageBasePath . 'TestInput/BackgroundCombine.jpg';
        $overlayFile = $imageBasePath . 'TestInput/Test.jpg';
        $maskFile = $imageBasePath . 'TestInput/MaskCombine.jpg';
        $resultFile = $this->getImagesPath() . 'installTool-' . StringUtility::getUniqueId($imageService->alternativeOutputKey . 'combine2') . '.jpg';
        $imageService->combineExec($inputFile, $overlayFile, $maskFile, $resultFile);
        $imageInfo = GeneralUtility::makeInstance(ImageInfo::class, $resultFile);
        if ($imageInfo->getWidth() > 0) {
            $result = [
                'fileExists' => true,
                'outputFile' => $resultFile,
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Combine-2.jpg',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * GD with simple box
     */
    public function imageProcessingGdlibSimpleAction(): ResponseInterface
    {
        $gifBuilder = $this->initializeGifBuilder();
        $image = imagecreatetruecolor(300, 225);
        $backgroundColor = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, 300, 225, $backgroundColor);
        $workArea = [0, 0, 300, 225];
        $conf = [
            'dimensions' => '10,50,280,50',
            'color' => 'olive',
        ];
        $gifBuilder->makeBox($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . 'installTool-' . StringUtility::getUniqueId('gdSimple') . '.png';
        $gifBuilder->ImageWrite($image, $outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $outputFile,
            'referenceFile' => self::TEST_REFERENCE_PATH . '/Gdlib-simple.png',
            'command' => $gifBuilder->getGraphicalFunctions()->IM_commands,
        ];
        return $this->getImageTestResponse($result);
    }

    /**
     * GD from image with box
     */
    public function imageProcessingGdlibFromFileAction(): ResponseInterface
    {
        $gifBuilder = $this->initializeGifBuilder();
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $inputFile = $imageBasePath . 'TestInput/Test.png';
        $image = $gifBuilder->imageCreateFromFile($inputFile);
        $workArea = [0, 0, 400, 300];
        $conf = [
            'dimensions' => '10,50,380,50',
            'color' => 'olive',
        ];
        $gifBuilder->makeBox($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . 'installTool-' . StringUtility::getUniqueId('gdBox') . '.png';
        $gifBuilder->ImageWrite($image, $outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $outputFile,
            'referenceFile' => self::TEST_REFERENCE_PATH . '/Gdlib-box.png',
            'command' => $gifBuilder->getGraphicalFunctions()->IM_commands,
        ];
        return $this->getImageTestResponse($result);
    }

    /**
     * GD from image with box exported as webp file
     */
    public function imageProcessingGdlibFromFileToWebpAction(): ResponseInterface
    {
        $gifBuilder = $this->initializeGifBuilder();
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $inputFile = $imageBasePath . 'TestInput/Test.webp';
        $image = $gifBuilder->imageCreateFromFile($inputFile);
        $workArea = [0, 0, 400, 300];
        $conf = [
            'dimensions' => '20,100,740,100',
            'color' => 'olive',
        ];
        $gifBuilder->makeBox($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . 'installTool-' . StringUtility::getUniqueId('gdBox') . '.webp';
        $gifBuilder->ImageWrite($image, $outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $outputFile,
            'referenceFile' => self::TEST_REFERENCE_PATH . '/Gdlib-box.webp',
            'command' => $gifBuilder->getGraphicalFunctions()->IM_commands,
        ];
        return $this->getImageTestResponse($result);
    }

    /**
     * GD from image with box exported as AVIF file
     */
    public function imageProcessingGdlibFromFileToAvifAction(): ResponseInterface
    {
        $gifBuilder = $this->initializeGifBuilder();
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $inputFile = $imageBasePath . 'TestInput/Test.avif';
        $image = $gifBuilder->imageCreateFromFile($inputFile);
        $workArea = [0, 0, 400, 300];
        $conf = [
            'dimensions' => '10,50,380,50',
            'color' => 'olive',
        ];
        $gifBuilder->makeBox($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . 'installTool-' . StringUtility::getUniqueId('gdBox') . '.avif';
        $success = $gifBuilder->ImageWrite($image, $outputFile);
        if ($success) {
            $result = [
                'fileExists' => true,
                'outputFile' => $outputFile,
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Gdlib-box.avif',
                'command' => $gifBuilder->getGraphicalFunctions()->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->avifImageGenerationFailedMessage()],
                'command' => $gifBuilder->getGraphicalFunctions()->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * GD with text
     */
    public function imageProcessingGdlibRenderTextAction(): ResponseInterface
    {
        $gifBuilder = $this->initializeGifBuilder();
        $image = imagecreatetruecolor(300, 225);
        $backgroundColor = imagecolorallocate($image, 128, 128, 150);
        imagefilledrectangle($image, 0, 0, 300, 225, $backgroundColor);
        $workArea = [0, 0, 300, 225];
        $conf = [
            'iterations' => 1,
            'angle' => 0,
            'antiAlias' => 1,
            'text' => 'HELLO WORLD',
            'fontColor' => '#003366',
            'fontSize' => 30,
            'fontFile' => ExtensionManagementUtility::extPath('install') . 'Resources/Private/Font/vera.ttf',
            'offset' => '30,80',
        ];
        $conf['BBOX'] = $gifBuilder->calcBBox($conf);
        $gifBuilder->makeText($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . 'installTool-' . StringUtility::getUniqueId('gdText') . '.png';
        $gifBuilder->ImageWrite($image, $outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $outputFile,
            'referenceFile' => self::TEST_REFERENCE_PATH . '/Gdlib-text.png',
            'command' => $gifBuilder->getGraphicalFunctions()->IM_commands,
        ];
        return $this->getImageTestResponse($result);
    }

    /**
     * GD with text, niceText
     */
    public function imageProcessingGdlibNiceTextAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $gifBuilder = $this->initializeGifBuilder();
        $image = imagecreatetruecolor(300, 225);
        $backgroundColor = imagecolorallocate($image, 128, 128, 150);
        imagefilledrectangle($image, 0, 0, 300, 225, $backgroundColor);
        $workArea = [0, 0, 300, 225];
        $conf = [
            'iterations' => 1,
            'angle' => 0,
            'antiAlias' => 1,
            'text' => 'HELLO WORLD',
            'fontColor' => '#003366',
            'fontSize' => 30,
            'fontFile' => ExtensionManagementUtility::extPath('install') . 'Resources/Private/Font/vera.ttf',
            'offset' => '30,80',
        ];
        $conf['BBOX'] = $gifBuilder->calcBBox($conf);
        $gifBuilder->makeText($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . 'installTool-' . StringUtility::getUniqueId('gdText') . '.png';
        $gifBuilder->ImageWrite($image, $outputFile);
        $conf['offset'] = '30,120';
        $conf['niceText'] = 1;
        $gifBuilder->makeText($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . 'installTool-' . StringUtility::getUniqueId('gdNiceText') . '.png';
        $gifBuilder->ImageWrite($image, $outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $outputFile,
            'referenceFile' => self::TEST_REFERENCE_PATH . '/Gdlib-niceText.png',
            'command' => $gifBuilder->getGraphicalFunctions()->IM_commands,
        ];
        return $this->getImageTestResponse($result);
    }

    /**
     * GD with text, niceText, shadow
     */
    public function imageProcessingGdlibNiceTextShadowAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $gifBuilder = $this->initializeGifBuilder();
        $image = imagecreatetruecolor(300, 225);
        $backgroundColor = imagecolorallocate($image, 128, 128, 150);
        imagefilledrectangle($image, 0, 0, 300, 225, $backgroundColor);
        $workArea = [0, 0, 300, 225];
        $conf = [
            'iterations' => 1,
            'angle' => 0,
            'antiAlias' => 1,
            'text' => 'HELLO WORLD',
            'fontColor' => '#003366',
            'fontSize' => 30,
            'fontFile' => ExtensionManagementUtility::extPath('install') . 'Resources/Private/Font/vera.ttf',
            'offset' => '30,80',
        ];
        $conf['BBOX'] = $gifBuilder->calcBBox($conf);
        $gifBuilder->makeText($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . 'installTool-' . StringUtility::getUniqueId('gdText') . '.png';
        $gifBuilder->ImageWrite($image, $outputFile);
        $conf['offset'] = '30,120';
        $conf['niceText'] = 1;
        $gifBuilder->makeText($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . 'installTool-' . StringUtility::getUniqueId('gdNiceText') . '.png';
        $gifBuilder->ImageWrite($image, $outputFile);
        $conf['offset'] = '30,160';
        $conf['niceText'] = 1;
        $conf['shadow.'] = [
            'offset' => '2,2',
            'blur' => '20',
            'opacity' => '50',
            'color' => 'black',
        ];
        // Warning: Re-uses $image from above!
        $gifBuilder->makeShadow($image, $conf['shadow.'], $workArea, $conf);
        $gifBuilder->makeText($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . 'installTool-' . StringUtility::getUniqueId('GDwithText-niceText-shadow') . '.png';
        $gifBuilder->ImageWrite($image, $outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $outputFile,
            'referenceFile' => self::TEST_REFERENCE_PATH . '/Gdlib-shadow.png',
            'command' => $gifBuilder->getGraphicalFunctions()->IM_commands,
        ];
        return $this->getImageTestResponse($result);
    }

    /**
     * Initialize GifBuilder for image manipulation tests
     */
    protected function initializeGifBuilder(): GifBuilder
    {
        return GeneralUtility::makeInstance(GifBuilder::class);
    }

    /**
     * Initialize GraphicalFunctions for image manipulation tests
     */
    protected function initializeGraphicalFunctions(): GraphicalFunctions
    {
        $imageService = GeneralUtility::makeInstance(GraphicalFunctions::class);
        $imageService->dontCheckForExistingTempFile = true;
        $imageService->filenamePrefix = 'installTool-';
        $imageService->alternativeOutputKey = 'typo3InstallTest';
        $imageService->setImageFileExt(self::IMAGE_FILE_EXT);
        return $imageService;
    }

    /**
     * Determine ImageMagick / GraphicsMagick version
     *
     * @return string Version
     */
    protected function determineImageMagickVersion(): string
    {
        $command = CommandUtility::imageMagickCommand('identify', '-version');
        CommandUtility::exec($command, $result);
        $string = $result[0] ?? '';
        $version = '';
        if (!empty($string)) {
            [, $version] = explode('Magick', $string);
            [$version] = explode(' ', trim($version));
            $version = trim($version);
        }
        return $version;
    }

    /**
     * Convert to jpg from given input format
     */
    protected function convertImageFormatsToJpg(string $inputFormat): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'success' => true,
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        if (!GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $inputFormat)) {
            return new JsonResponse([
                'success' => true,
                'status' => [
                    new FlashMessage(
                        'Handling format ' . $inputFormat . ' must be enabled in TYPO3_CONF_VARS[\'GFX\'][\'imagefile_ext\']',
                        'Skipped test',
                        ContextualFeedbackSeverity::WARNING
                    ),
                ],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageService = $this->initializeGraphicalFunctions();
        $inputFile = $imageBasePath . 'TestInput/Test.' . $inputFormat;
        $imageService->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('read') . '-' . $inputFormat;
        $imResult = $imageService->resize($inputFile, 'jpg', '300', '', '', [], true);
        if ($imResult !== null) {
            $result = [
                'fileExists' => $imResult->isFile(),
                'outputFile' => $imResult->getRealPath(),
                'referenceFile' => self::TEST_REFERENCE_PATH . '/Read-' . $inputFormat . '.jpg',
                'command' => $imageService->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageService->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Get details about all configured database connections
     */
    protected function getDatabaseConnectionInformation(): array
    {
        $connectionInfos = [];
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        foreach ($connectionPool->getConnectionNames() as $connectionName) {
            $connection = $connectionPool->getConnectionByName($connectionName);
            $connectionParameters = $connection->getParams();
            $connectionInfo = [
                'connectionName' => $connectionName,
                'version' => $connection->getPlatformServerVersion(),
                'databaseName' => $connection->getDatabase(),
                'username' => $connectionParameters['user'] ?? '',
                'host' => $connectionParameters['host'] ?? '',
                'port' => $connectionParameters['port'] ?? '',
                'socket' => $connectionParameters['unix_socket'] ?? '',
                'numberOfTables' => count($connection->createSchemaManager()->listTableNames()),
                'numberOfMappedTables' => 0,
            ];
            if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'])
                && is_array($GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'])
            ) {
                // Count number of array keys having $connectionName as value
                $connectionInfo['numberOfMappedTables'] = count(array_intersect(
                    $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'],
                    [$connectionName]
                ));
            }
            $connectionInfos[] = $connectionInfo;
        }
        return $connectionInfos;
    }

    /**
     * Get details about the application context
     */
    protected function getApplicationContextInformation(): array
    {
        $applicationContext = Environment::getContext();
        $status = $applicationContext->isProduction() ? InformationStatus::OK : InformationStatus::WARNING;

        return [
            'context' => (string)$applicationContext,
            'status' => $status->value,
        ];
    }

    /**
     * Get sender address from configuration
     * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
     * If this setting is empty, return an empty string.
     *
     * Email servers often reject mails with an invalid sender email address (or an address which does not correspondent
     * to the email account). In any case, it is not good practice to send emails with arbitrary sender addresses.
     * This is why a default like 'no-reply@example.com' is no longer being used here. The sender address should
     * be configured explicitly via ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'].
     *
     * @return string Returns an email address
     */
    protected function getSenderEmailAddress(): string
    {
        return $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'] ?? '';
    }

    /**
     * Gets sender name from configuration
     * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
     * If this setting is empty, it falls back to a default string.
     */
    protected function getSenderEmailName(): string
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName'])
            ? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
            : 'TYPO3 CMS install tool';
    }

    /**
     * Gets email subject from configuration
     * ['TYPO3_CONF_VARS']['SYS']['sitename']
     * If this setting is empty, it falls back to a default string.
     */
    protected function getEmailSubject(): string
    {
        $name = !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'])
            ? ' from site "' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '"'
            : '';
        return 'Test TYPO3 CMS mail delivery' . $name;
    }

    /**
     * Create a JsonResponse from single image tests
     */
    protected function getImageTestResponse(array $testResult): ResponseInterface
    {
        $responseData = [
            'success' => true,
        ];
        foreach ($testResult as $resultKey => $value) {
            if ($resultKey === 'referenceFile' && !empty($testResult['referenceFile'])) {
                $referenceFileArray = explode('.', $testResult['referenceFile']);
                $fileExt = end($referenceFileArray);
                $responseData['referenceFile'] = 'data:image/' . $fileExt . ';base64,' . base64_encode((string)file_get_contents($testResult['referenceFile']));
            } elseif ($resultKey === 'outputFile' && !empty($testResult['outputFile'])) {
                $outputFileArray = explode('.', $testResult['outputFile']);
                $fileExt = end($outputFileArray);
                $responseData['outputFile'] = 'data:image/' . $fileExt . ';base64,' . base64_encode((string)file_get_contents($testResult['outputFile']));
            } else {
                $responseData[$resultKey] = $value;
            }
        }
        return new JsonResponse($responseData);
    }

    /**
     * Create a 'image generation failed' message
     */
    protected function imageGenerationFailedMessage(): FlashMessage
    {
        return new FlashMessage(
            'ImageMagick / GraphicsMagick handling is enabled, but the execute'
            . ' command returned an error. Please check your settings, especially'
            . ' [\'GFX\'][\'processor_path\'] and ensure Ghostscript is installed on your server.'
            . ' Also ensure that possible codecs needed for specific image/video formats are available on your system.',
            'Image generation failed',
            ContextualFeedbackSeverity::ERROR
        );
    }

    protected function avifImageGenerationFailedMessage(): FlashMessage
    {
        return new FlashMessage(
            'Writing AVIF format failed. Please check whether ImageMagick / GraphicsMagick and your system provides and utilizes the necessary codec libraries for writing.',
            'Skipped test',
            ContextualFeedbackSeverity::INFO
        );
    }

    /**
     * Find out if ImageMagick or GraphicsMagick is enabled and set up
     *
     * @return bool TRUE if enabled and path is set
     */
    protected function isImageMagickEnabledAndConfigured(): bool
    {
        $enabled = $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled'];
        $path = $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'];
        return $enabled && $path;
    }

    /**
     * Create a 'imageMagick disabled' message
     */
    protected function imageMagickDisabledMessage(): FlashMessage
    {
        return new FlashMessage(
            'ImageMagick / GraphicsMagick handling is disabled or not configured correctly.',
            'Tests not executed',
            ContextualFeedbackSeverity::ERROR
        );
    }

    /**
     * Return the temp image dir.
     * If not exist it will be created
     */
    protected function getImagesPath(): string
    {
        $imagePath = Environment::getPublicPath() . '/typo3temp/assets/images/';
        if (!is_dir($imagePath)) {
            GeneralUtility::mkdir_deep($imagePath);
        }
        return $imagePath;
    }
}
