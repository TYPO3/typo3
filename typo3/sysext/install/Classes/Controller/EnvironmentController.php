<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Install\Controller;

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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\FormProtection\InstallToolFormProtection;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;
use TYPO3\CMS\Install\FolderStructure\DefaultPermissionsCheck;
use TYPO3\CMS\Install\SystemEnvironment\Check;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck;
use TYPO3\CMS\Install\SystemEnvironment\SetupCheck;

/**
 * Environment controller
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class EnvironmentController extends AbstractController
{
    /**
     * Main "show the cards" view
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function cardsAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Environment/Cards.html');
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * System Information Get Data action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function systemInformationGetDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Environment/SystemInformation.html');
        $view->assignMultiple([
            'systemInformationCgiDetected', GeneralUtility::isRunningOnCgiServerApi(),
            'systemInformationDatabaseConnections' => $this->getDatabaseConnectionInformation(),
            'systemInformationOperatingSystem' => Environment::isWindows() ? 'Windows' : 'Unix',
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * System Information Get Data action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function phpInfoGetDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Environment/PhpInfo.html');
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * Get environment status
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function environmentCheckGetStatusAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Environment/EnvironmentCheck.html');
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
        return new JsonResponse([
            'success' => true,
            'status' => [
                'error' => $messageQueue->getAllMessages(FlashMessage::ERROR),
                'warning' => $messageQueue->getAllMessages(FlashMessage::WARNING),
                'ok' => $messageQueue->getAllMessages(FlashMessage::OK),
                'information' => $messageQueue->getAllMessages(FlashMessage::INFO),
                'notice' => $messageQueue->getAllMessages(FlashMessage::NOTICE),
            ],
            'html' => $view->render(),
        ]);
    }

    /**
     * Get folder structure status
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function folderStructureGetStatusAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Environment/FolderStructure.html');
        $folderStructureFactory = GeneralUtility::makeInstance(DefaultFactory::class);
        $structureFacade = $folderStructureFactory->getStructure();

        $structureMessages = $structureFacade->getStatus();
        $errorQueue = new FlashMessageQueue('install');
        $okQueue = new FlashMessageQueue('install');
        foreach ($structureMessages as $message) {
            if ($message->getSeverity() === FlashMessage::ERROR
                || $message->getSeverity() === FlashMessage::WARNING
            ) {
                $errorQueue->enqueue($message);
            } else {
                $okQueue->enqueue($message);
            }
        }

        $permissionCheck = GeneralUtility::makeInstance(DefaultPermissionsCheck::class);

        $view->assign('publicPath', Environment::getPublicPath());

        return new JsonResponse([
            'success' => true,
            'errorStatus' => $errorQueue,
            'okStatus' => $okQueue,
            'folderStructureFilePermissionStatus' => $permissionCheck->getMaskStatus('fileCreateMask'),
            'folderStructureDirectoryPermissionStatus' => $permissionCheck->getMaskStatus('folderCreateMask'),
            'html' => $view->render(),
        ]);
    }

    /**
     * Try to fix folder structure errors
     *
     * @return ResponseInterface
     */
    public function folderStructureFixAction(): ResponseInterface
    {
        $folderStructureFactory = GeneralUtility::makeInstance(DefaultFactory::class);
        $structureFacade = $folderStructureFactory->getStructure();
        $fixedStatusObjects = $structureFacade->fix();
        return new JsonResponse([
            'success' => true,
            'fixedStatus' => $fixedStatusObjects,
        ]);
    }

    /**
     * System Information Get Data action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function mailTestGetDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Environment/MailTest.html');
        $formProtection = FormProtectionFactory::get(InstallToolFormProtection::class);
        $view->assignMultiple([
            'mailTestToken' => $formProtection->generateToken('installTool', 'mailTest'),
            'mailTestSenderAddress' => $this->getSenderEmailAddress(),
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     *  Send a test mail
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function mailTestAction(ServerRequestInterface $request): ResponseInterface
    {
        $messages = new FlashMessageQueue('install');
        $recipient = $request->getParsedBody()['install']['email'];
        if (empty($recipient) || !GeneralUtility::validEmail($recipient)) {
            $messages->enqueue(new FlashMessage(
                'Given address is not a valid email address.',
                'Mail not sent',
                FlashMessage::ERROR
            ));
        } else {
            $mailMessage = GeneralUtility::makeInstance(MailMessage::class);
            $mailMessage
                ->addTo($recipient)
                ->addFrom($this->getSenderEmailAddress(), $this->getSenderEmailName())
                ->setSubject($this->getEmailSubject())
                ->setBody('<html><body>html test content</body></html>', 'text/html')
                ->addPart('plain test content', 'text/plain')
                ->send();
            $messages->enqueue(new FlashMessage(
                'Recipient: ' . $recipient,
                'Test mail sent'
            ));
        }
        return new JsonResponse([
            'success' => true,
            'status' => $messages,
        ]);
    }

    /**
     * System Information Get Data action
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    public function imageProcessingGetDataAction(ServerRequestInterface $request): ResponseInterface
    {
        $view = $this->initializeStandaloneView($request, 'Environment/ImageProcessing.html');
        $view->assignMultiple([
            'imageProcessingProcessor' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] === 'GraphicsMagick' ? 'GraphicsMagick' : 'ImageMagick',
            'imageProcessingEnabled' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled'],
            'imageProcessingPath' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'],
            'imageProcessingVersion' => $this->determineImageMagickVersion(),
            'imageProcessingEffects' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_effects'],
            'imageProcessingGdlibEnabled' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'],
            'imageProcessingGdlibPng' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png'],
            'imageProcessingFileFormats' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
        ]);
        return new JsonResponse([
            'success' => true,
            'html' => $view->render(),
        ]);
    }

    /**
     * Create true type font test image
     *
     * @return ResponseInterface
     */
    public function imageProcessingTrueTypeAction(): ResponseInterface
    {
        $image = @imagecreate(200, 50);
        imagecolorallocate($image, 255, 255, 55);
        $textColor = imagecolorallocate($image, 233, 14, 91);
        @imagettftext(
            $image,
            20 / 96.0 * 72, // As in  compensateFontSizeiBasedOnFreetypeDpi
            0,
            10,
            20,
            $textColor,
            ExtensionManagementUtility::extPath('install') . 'Resources/Private/Font/vera.ttf',
            'Testing true type'
        );
        $outputFile = Environment::getPublicPath() . '/typo3temp/assets/images/installTool-' . StringUtility::getUniqueId('createTrueTypeFontTestImage') . '.gif';
        imagegif($image, $outputFile);
        $fileExists = file_exists($outputFile);
        if ($fileExists) {
            GeneralUtility::fixPermissions($outputFile);
        }
        return $this->getImageTestResponse([
            'fileExists' => $fileExists,
            'outputFile' => $outputFile,
            'referenceFile' => Environment::getFrameworkBasePath() . '/install/Resources/Public/Images/TestReference/Font.gif',
        ]);
    }

    /**
     * Convert to jpg from jpg
     *
     * @return ResponseInterface
     */
    public function imageProcessingReadJpgAction(): ResponseInterface
    {
        return $this->convertImageFormatsToJpg('jpg');
    }

    /**
     * Convert to jpg from gif
     *
     * @return ResponseInterface
     */
    public function imageProcessingReadGifAction(): ResponseInterface
    {
        return $this->convertImageFormatsToJpg('gif');
    }

    /**
     * Convert to jpg from png
     *
     * @return ResponseInterface
     */
    public function imageProcessingReadPngAction(): ResponseInterface
    {
        return $this->convertImageFormatsToJpg('png');
    }

    /**
     * Convert to jpg from tif
     *
     * @return ResponseInterface
     */
    public function imageProcessingReadTifAction(): ResponseInterface
    {
        return $this->convertImageFormatsToJpg('tif');
    }

    /**
     * Convert to jpg from pdf
     *
     * @return ResponseInterface
     */
    public function imageProcessingReadPdfAction(): ResponseInterface
    {
        return $this->convertImageFormatsToJpg('pdf');
    }

    /**
     * Convert to jpg from ai
     *
     * @return ResponseInterface
     */
    public function imageProcessingReadAiAction(): ResponseInterface
    {
        return $this->convertImageFormatsToJpg('ai');
    }

    /**
     * Writing gif test
     *
     * @return ResponseInterface
     */
    public function imageProcessingWriteGifAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $inputFile = $imageBasePath . 'TestInput/Test.gif';
        $imageProcessor = $this->initializeImageProcessor();
        $imageProcessor->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('write-gif');
        $imResult = $imageProcessor->imageMagickConvert($inputFile, 'gif', '300', '', '', '', [], true);
        $messages = new FlashMessageQueue('install');
        if ($imResult !== null && is_file($imResult[3])) {
            if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gif_compress']) {
                clearstatcache();
                $previousSize = GeneralUtility::formatSize(filesize($imResult[3]));
                $methodUsed = GraphicalFunctions::gifCompress($imResult[3], '');
                clearstatcache();
                $compressedSize = GeneralUtility::formatSize(filesize($imResult[3]));
                $messages->enqueue(new FlashMessage(
                    'Method used by compress: ' . $methodUsed . LF
                    . ' Previous filesize: ' . $previousSize . '. Current filesize:' . $compressedSize,
                    'Compressed gif',
                    FlashMessage::INFO
                ));
            } else {
                $messages->enqueue(new FlashMessage(
                    '',
                    'Gif compression not enabled by [GFX][gif_compress]',
                    FlashMessage::INFO
                ));
            }
            $result = [
                'status' => $messages,
                'fileExists' => true,
                'outputFile' => $imResult[3],
                'referenceFile' => Environment::getFrameworkBasePath() . '/install/Resources/Public/Images/TestReference/Write-gif.gif',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageProcessor->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Writing png test
     *
     * @return ResponseInterface
     */
    public function imageProcessingWritePngAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $inputFile = $imageBasePath . 'TestInput/Test.png';
        $imageProcessor = $this->initializeImageProcessor();
        $imageProcessor->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('write-png');
        $imResult = $imageProcessor->imageMagickConvert($inputFile, 'png', '300', '', '', '', [], true);
        if ($imResult !== null && is_file($imResult[3])) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult[3],
                'referenceFile' => Environment::getFrameworkBasePath() . '/install/Resources/Public/Images/TestReference/Write-png.png',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageProcessor->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Scaling transparent files - gif to gif
     *
     * @return ResponseInterface
     */
    public function imageProcessingGifToGifAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageProcessor = $this->initializeImageProcessor();
        $inputFile = $imageBasePath . 'TestInput/Transparent.gif';
        $imageProcessor->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('scale-gif');
        $imResult = $imageProcessor->imageMagickConvert($inputFile, 'gif', '300', '', '', '', [], true);
        if ($imResult !== null && file_exists($imResult[3])) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult[3],
                'referenceFile' => Environment::getFrameworkBasePath() . '/install/Resources/Public/Images/TestReference/Scale-gif.gif',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageProcessor->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Scaling transparent files - png to png
     *
     * @return ResponseInterface
     */
    public function imageProcessingPngToPngAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageProcessor = $this->initializeImageProcessor();
        $inputFile = $imageBasePath . 'TestInput/Transparent.png';
        $imageProcessor->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('scale-png');
        $imResult = $imageProcessor->imageMagickConvert($inputFile, 'png', '300', '', '', '', [], true);
        if ($imResult !== null && file_exists($imResult[3])) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult[3],
                'referenceFile' => Environment::getFrameworkBasePath() . '/install/Resources/Public/Images/TestReference/Scale-png.png',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageProcessor->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Scaling transparent files - gif to jpg
     *
     * @return ResponseInterface
     */
    public function imageProcessingGifToJpgAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageProcessor = $this->initializeImageProcessor();
        $inputFile = $imageBasePath . 'TestInput/Transparent.gif';
        $imageProcessor->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('scale-jpg');
        $imResult = $imageProcessor->imageMagickConvert($inputFile, 'jpg', '300', '', '-opaque white -background white -flatten', '', [], true);
        if ($imResult !== null && file_exists($imResult[3])) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult[3],
                'referenceFile' => Environment::getFrameworkBasePath() . '/install/Resources/Public/Images/TestReference/Scale-jpg.jpg',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageProcessor->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Combine images with gif mask
     *
     * @return ResponseInterface
     */
    public function imageProcessingCombineGifMaskAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageProcessor = $this->initializeImageProcessor();
        $inputFile = $imageBasePath . 'TestInput/BackgroundOrange.gif';
        $overlayFile = $imageBasePath . 'TestInput/Test.jpg';
        $maskFile = $imageBasePath . 'TestInput/MaskBlackWhite.gif';
        $resultFile = $this->getImagesPath() . $imageProcessor->filenamePrefix
            . StringUtility::getUniqueId($imageProcessor->alternativeOutputKey . 'combine1') . '.jpg';
        $imageProcessor->combineExec($inputFile, $overlayFile, $maskFile, $resultFile);
        $imResult = $imageProcessor->getImageDimensions($resultFile);
        if ($imResult) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult[3],
                'referenceFile' => Environment::getFrameworkBasePath() . '/install/Resources/Public/Images/TestReference/Combine-1.jpg',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageProcessor->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Combine images with jpg mask
     *
     * @return ResponseInterface
     */
    public function imageProcessingCombineJpgMaskAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageProcessor = $this->initializeImageProcessor();
        $inputFile = $imageBasePath . 'TestInput/BackgroundCombine.jpg';
        $overlayFile = $imageBasePath . 'TestInput/Test.jpg';
        $maskFile = $imageBasePath . 'TestInput/MaskCombine.jpg';
        $resultFile = $this->getImagesPath() . $imageProcessor->filenamePrefix
            . StringUtility::getUniqueId($imageProcessor->alternativeOutputKey . 'combine2') . '.jpg';
        $imageProcessor->combineExec($inputFile, $overlayFile, $maskFile, $resultFile);
        $imResult = $imageProcessor->getImageDimensions($resultFile);
        if ($imResult) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult[3],
                'referenceFile' => Environment::getFrameworkBasePath() . '/install/Resources/Public/Images/TestReference/Combine-2.jpg',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageProcessor->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * GD with simple box
     *
     * @return ResponseInterface
     */
    public function imageProcessingGdlibSimpleAction(): ResponseInterface
    {
        $imageProcessor = $this->initializeImageProcessor();
        $gifOrPng = $imageProcessor->gifExtension;
        $image = imagecreatetruecolor(300, 225);
        $backgroundColor = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, 300, 225, $backgroundColor);
        $workArea = [0, 0, 300, 225];
        $conf = [
            'dimensions' => '10,50,280,50',
            'color' => 'olive',
        ];
        $imageProcessor->makeBox($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdSimple') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $imResult = $imageProcessor->getImageDimensions($outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $imResult[3],
            'referenceFile' => Environment::getFrameworkBasePath() . '/install/Resources/Public/Images/TestReference/Gdlib-simple.' . $gifOrPng,
            'command' => $imageProcessor->IM_commands,
        ];
        return $this->getImageTestResponse($result);
    }

    /**
     * GD from image with box
     *
     * @return ResponseInterface
     */
    public function imageProcessingGdlibFromFileAction(): ResponseInterface
    {
        $imageProcessor = $this->initializeImageProcessor();
        $gifOrPng = $imageProcessor->gifExtension;
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $inputFile = $imageBasePath . 'TestInput/Test.' . $gifOrPng;
        $image = $imageProcessor->imageCreateFromFile($inputFile);
        $workArea = [0, 0, 400, 300];
        $conf = [
            'dimensions' => '10,50,380,50',
            'color' => 'olive',
        ];
        $imageProcessor->makeBox($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdBox') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $imResult = $imageProcessor->getImageDimensions($outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $imResult[3],
            'referenceFile' => Environment::getFrameworkBasePath() . '/install/Resources/Public/Images/TestReference/Gdlib-box.' . $gifOrPng,
            'command' => $imageProcessor->IM_commands,
        ];
        return $this->getImageTestResponse($result);
    }

    /**
     * GD with text
     *
     * @return ResponseInterface
     */
    public function imageProcessingGdlibRenderTextAction(): ResponseInterface
    {
        $imageProcessor = $this->initializeImageProcessor();
        $gifOrPng = $imageProcessor->gifExtension;
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
        $conf['BBOX'] = $imageProcessor->calcBBox($conf);
        $imageProcessor->makeText($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdText') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $imResult = $imageProcessor->getImageDimensions($outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $imResult[3],
            'referenceFile' => Environment::getFrameworkBasePath() . '/install/Resources/Public/Images/TestReference/Gdlib-text.' . $gifOrPng,
            'command' => $imageProcessor->IM_commands,
        ];
        return $this->getImageTestResponse($result);
    }

    /**
     * GD with text, niceText
     *
     * @return ResponseInterface
     */
    public function imageProcessingGdlibNiceTextAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageProcessor = $this->initializeImageProcessor();
        $gifOrPng = $imageProcessor->gifExtension;
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
        $conf['BBOX'] = $imageProcessor->calcBBox($conf);
        $imageProcessor->makeText($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdText') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $conf['offset'] = '30,120';
        $conf['niceText'] = 1;
        $imageProcessor->makeText($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdNiceText') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $imResult = $imageProcessor->getImageDimensions($outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $imResult[3],
            'referenceFile' => Environment::getFrameworkBasePath() . '/install/Resources/Public/Images/TestReference/Gdlib-niceText.' . $gifOrPng,
            'command' => $imageProcessor->IM_commands,
        ];
        return $this->getImageTestResponse($result);
    }

    /**
     * GD with text, niceText, shadow
     *
     * @return ResponseInterface
     */
    public function imageProcessingGdlibNiceTextShadowAction(): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        $imageProcessor = $this->initializeImageProcessor();
        $gifOrPng = $imageProcessor->gifExtension;
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
        $conf['BBOX'] = $imageProcessor->calcBBox($conf);
        $imageProcessor->makeText($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdText') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $conf['offset'] = '30,120';
        $conf['niceText'] = 1;
        $imageProcessor->makeText($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdNiceText') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $conf['offset'] = '30,160';
        $conf['niceText'] = 1;
        $conf['shadow.'] = [
            'offset' => '2,2',
            'blur' => '20',
            'opacity' => '50',
            'color' => 'black'
        ];
        // Warning: Re-uses $image from above!
        $imageProcessor->makeShadow($image, $conf['shadow.'], $workArea, $conf);
        $imageProcessor->makeText($image, $conf, $workArea);
        $outputFile = $this->getImagesPath() . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('GDwithText-niceText-shadow') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $imResult = $imageProcessor->getImageDimensions($outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $imResult[3],
            'referenceFile' => Environment::getFrameworkBasePath() . '/install/Resources/Public/Images/TestReference/Gdlib-shadow.' . $gifOrPng,
            'command' => $imageProcessor->IM_commands,
        ];
        return $this->getImageTestResponse($result);
    }

    /**
     * Initialize image processor
     *
     * @return GraphicalFunctions Initialized image processor
     */
    protected function initializeImageProcessor(): GraphicalFunctions
    {
        $imageProcessor = GeneralUtility::makeInstance(GraphicalFunctions::class);
        $imageProcessor->dontCheckForExistingTempFile = true;
        $imageProcessor->filenamePrefix = 'installTool-';
        $imageProcessor->dontCompress = true;
        $imageProcessor->alternativeOutputKey = 'typo3InstallTest';
        return $imageProcessor;
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
        $string = $result[0];
        $version = '';
        if (!empty($string)) {
            list(, $version) = explode('Magick', $string);
            list($version) = explode(' ', trim($version));
            $version = trim($version);
        }
        return $version;
    }

    /**
     * Convert to jpg from given input format
     *
     * @param string $inputFormat
     * @return ResponseInterface
     */
    protected function convertImageFormatsToJpg(string $inputFormat): ResponseInterface
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return new JsonResponse([
                'status' => [$this->imageMagickDisabledMessage()],
            ]);
        }
        if (!GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $inputFormat)) {
            return new JsonResponse([
                'status' => [
                    new FlashMessage(
                        'Handling format ' . $inputFormat . ' must be enabled in TYPO3_CONF_VARS[\'GFX\'][\'imagefile_ext\']',
                        'Skipped test',
                        FlashMessage::WARNING
                    )
                ]
            ]);
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageProcessor = $this->initializeImageProcessor();
        $inputFile = $imageBasePath . 'TestInput/Test.' . $inputFormat;
        $imageProcessor->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('read') . '-' . $inputFormat;
        $imResult = $imageProcessor->imageMagickConvert($inputFile, 'jpg', '300', '', '', '', [], true);
        $result = [];
        if ($imResult !== null) {
            $result = [
                'fileExists' => file_exists($imResult[3]),
                'outputFile' => $imResult[3],
                'referenceFile' => Environment::getFrameworkBasePath() . '/install/Resources/Public/Images/TestReference/Read-' . $inputFormat . '.jpg',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result = [
                'status' => [$this->imageGenerationFailedMessage()],
                'command' => $imageProcessor->IM_commands,
            ];
        }
        return $this->getImageTestResponse($result);
    }

    /**
     * Get details about all configured database connections
     *
     * @return array
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
                'version' => $connection->getServerVersion(),
                'databaseName' => $connection->getDatabase(),
                'username' => $connection->getUsername(),
                'host' => $connection->getHost(),
                'port' => $connection->getPort(),
                'socket' => $connectionParameters['unix_socket'] ?? '',
                'numberOfTables' => count($connection->getSchemaManager()->listTableNames()),
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
     * Get sender address from configuration
     * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
     * If this setting is empty fall back to 'no-reply@example.com'
     *
     * @return string Returns an email address
     */
    protected function getSenderEmailAddress(): string
    {
        return !empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])
            ? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
            : 'no-reply@example.com';
    }

    /**
     * Gets sender name from configuration
     * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromName']
     * If this setting is empty, it falls back to a default string.
     *
     * @return string
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
     *
     * @return string
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
     *
     * @param array $testResult
     * @return ResponseInterface
     */
    protected function getImageTestResponse(array $testResult): ResponseInterface
    {
        $responseData = [
            'success' => true,
        ];
        foreach ($testResult as $resultKey => $value) {
            if ($resultKey === 'referenceFile') {
                $fileExt = end(explode('.', $testResult['referenceFile']));
                $responseData['referenceFile'] = 'data:image/' . $fileExt . ';base64,' . base64_encode(file_get_contents($testResult['referenceFile']));
            } elseif ($resultKey === 'outputFile') {
                $fileExt = end(explode('.', $testResult['outputFile']));
                $responseData['outputFile'] = 'data:image/' . $fileExt . ';base64,' . base64_encode(file_get_contents($testResult['outputFile']));
            } else {
                $responseData[$resultKey] = $value;
            }
        }
        return new JsonResponse($responseData);
    }

    /**
     * Create a 'image generation failed' message
     *
     * @return FlashMessage
     */
    protected function imageGenerationFailedMessage(): FlashMessage
    {
        return new FlashMessage(
            'ImageMagick / GraphicsMagick handling is enabled, but the execute'
            . ' command returned an error. Please check your settings, especially'
            . ' [\'GFX\'][\'processor_path\'] and [\'GFX\'][\'processor_path_lzw\'] and ensure Ghostscript is installed on your server.',
            'Image generation failed',
            FlashMessage::ERROR
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
     *
     * @return FlashMessage
     */
    protected function imageMagickDisabledMessage(): FlashMessage
    {
        return new FlashMessage(
            'ImageMagick / GraphicsMagick handling is disabled or not configured correctly.',
            'Tests not executed',
            FlashMessage::ERROR
        );
    }

    /**
     * Return the temp image dir.
     * If not exist it will be created
     *
     * @return string
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
