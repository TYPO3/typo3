<?php
namespace TYPO3\CMS\Install\Controller\Action\Tool;

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

use TYPO3\CMS\Core\Imaging\GraphicalFunctions;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Install\Controller\Action;

/**
 * Test various system setup settings
 */
class TestSetup extends Action\AbstractAction
{
    /**
     * @var string Absolute path to image folder
     */
    protected $imageBasePath = '';

    /**
     * Executes the tool
     *
     * @return string Rendered content
     */
    protected function executeAction()
    {
        $this->imageBasePath = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';

        $actionMessages = [];
        if (isset($this->postValues['set']['testMail'])) {
            $actionMessages[] = $this->sendTestMail();
            $this->view->assign('postAction', 'testMail');
        }

        if (isset($this->postValues['set']['testTrueTypeFont'])) {
            $this->view->assign('trueTypeFontTested', true);
            $actionMessages[] = $this->createTrueTypeFontTestImage();
        }

        if (isset($this->postValues['set']['testConvertImageFormatsToJpg'])) {
            $this->view->assign('convertImageFormatsToJpgTested', true);
            if ($this->isImageMagickEnabledAndConfigured()) {
                $actionMessages[] = $this->convertImageFormatsToJpg();
            } else {
                $actionMessages[] = $this->imageMagickDisabledMessage();
            }
        }

        if (isset($this->postValues['set']['testWriteGifAndPng'])) {
            $this->view->assign('writeGifAndPngTested', true);
            if ($this->isImageMagickEnabledAndConfigured()) {
                $actionMessages[] = $this->writeGifAndPng();
            } else {
                $actionMessages[] = $this->imageMagickDisabledMessage();
            }
        }

        if (isset($this->postValues['set']['testScalingImages'])) {
            $this->view->assign('scalingImagesTested', true);
            if ($this->isImageMagickEnabledAndConfigured()) {
                $actionMessages[] = $this->scaleImages();
            } else {
                $actionMessages[] = $this->imageMagickDisabledMessage();
            }
        }

        if (isset($this->postValues['set']['testCombiningImages'])) {
            $this->view->assign('combiningImagesTested', true);
            if ($this->isImageMagickEnabledAndConfigured()) {
                $actionMessages[] = $this->combineImages();
            } else {
                $actionMessages[] = $this->imageMagickDisabledMessage();
            }
        }

        if (isset($this->postValues['set']['testGdlib'])) {
            $this->view->assign('gdlibTested', true);
            $actionMessages[] = $this->gdlib();
        }

        $this->view->assign('actionMessages', $actionMessages);
        $this->view->assign('senderEmailAddress', $this->getSenderEmailAddress());
        $this->view->assign('imageConfiguration', $this->getImageConfiguration());

        return $this->view->render();
    }

    /**
     * Send a test mail to specified email address
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function sendTestMail()
    {
        if (
            !isset($this->postValues['values']['testEmailRecipient'])
            || !GeneralUtility::validEmail($this->postValues['values']['testEmailRecipient'])
        ) {
            /** @var $message \TYPO3\CMS\Install\Status\StatusInterface */
            $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
            $message->setTitle('Mail not sent');
            $message->setMessage('Given address is not a valid email address.');
        } else {
            $recipient = $this->postValues['values']['testEmailRecipient'];
            /** @var $mailMessage \TYPO3\CMS\Core\Mail\MailMessage */
            $mailMessage = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Mail\MailMessage::class);
            $mailMessage
                ->addTo($recipient)
                ->addFrom($this->getSenderEmailAddress(), $this->getSenderEmailName())
                ->setSubject($this->getEmailSubject())
                ->setBody('<html><body>html test content</body></html>', 'text/html')
                ->addPart('TEST CONTENT')
                ->send();
            $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\OkStatus::class);
            $message->setTitle('Test mail sent');
            $message->setMessage('Recipient: ' . $recipient);
        }
        return $message;
    }

    /**
     * Get sender address from configuration
     * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
     * If this setting is empty fall back to 'no-reply@example.com'
     *
     * @return string Returns an email address
     */
    protected function getSenderEmailAddress()
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
    protected function getSenderEmailName()
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
    protected function getEmailSubject()
    {
        $name = !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'])
            ? ' from site "' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '"'
            : '';
        return 'Test TYPO3 CMS mail delivery' . $name;
    }

    /**
     * Create true type font test image
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function createTrueTypeFontTestImage()
    {
        $parseTimeStart = GeneralUtility::milliseconds();

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
            \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('install') . 'Resources/Private/Font/vera.ttf',
            'Testing true type'
        );
        $outputFile = PATH_site . 'typo3temp/assets/images/installTool-' . StringUtility::getUniqueId('createTrueTypeFontTestImage') . '.gif';
        imagegif($image, $outputFile);

        /** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
        $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\InfoStatus::class);
        $message->setTitle('True type font');
        $message->setMessage(
            'If the two images below do not look the same, please check your FreeType 2 module.'
        );

        $testResults = [];
        $testResults['ttf'] = [];
        $testResults['ttf']['message'] = $message;
        $testResults['ttf']['title'] = '';
        $testResults['ttf']['outputFile'] = $outputFile;
        $testResults['ttf']['referenceFile'] = $this->imageBasePath . 'TestReference/Font.gif';

        $this->view->assign('testResults', $testResults);
        return $this->imageTestDoneMessage(GeneralUtility::milliseconds() - $parseTimeStart);
    }

    /**
     * Create jpg from various image formats using IM / GM
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function convertImageFormatsToJpg()
    {
        $imageProcessor = $this->initializeImageProcessor();
        $parseTimeStart = GeneralUtility::milliseconds();

        $inputFormatsToTest = ['jpg', 'gif', 'png', 'tif', 'pdf', 'ai'];

        $testResults = [];
        foreach ($inputFormatsToTest as $formatToTest) {
            $result = [];
            if (!GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $formatToTest)) {
                /** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
                $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\WarningStatus::class);
                $message->setTitle('Skipped test');
                $message->setMessage('Handling format ' . $formatToTest . ' must be enabled in TYPO3_CONF_VARS[\'GFX\'][\'imagefile_ext\']');
                $result['error'] = $message;
            } else {
                $imageProcessor->IM_commands = [];
                $inputFile = $this->imageBasePath . 'TestInput/Test.' . $formatToTest;
                $imageProcessor->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('read') . '-' . $formatToTest;
                $imResult = $imageProcessor->imageMagickConvert($inputFile, 'jpg', '300', '', '', '', [], true);
                $result['title'] = 'Read ' . $formatToTest;
                if ($imResult !== null) {
                    $result['outputFile'] = $imResult[3];
                    $result['referenceFile'] = $this->imageBasePath . 'TestReference/Read-' . $formatToTest . '.jpg';
                    $result['command'] = $imageProcessor->IM_commands;
                } else {
                    $result['error'] = $this->imageGenerationFailedMessage();
                }
            }
            $testResults[] = $result;
        }

        $this->view->assign('testResults', $testResults);
        return $this->imageTestDoneMessage(GeneralUtility::milliseconds() - $parseTimeStart);
    }

    /**
     * Write gif and png test
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function writeGifAndPng()
    {
        $imageProcessor = $this->initializeImageProcessor();
        $parseTimeStart = GeneralUtility::milliseconds();

        $testResults = [
            'gif' => [],
            'png' => [],
        ];

        // Gif
        $inputFile = $this->imageBasePath . 'TestInput/Test.gif';
        $imageProcessor->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('write-gif');
        $imResult = $imageProcessor->imageMagickConvert($inputFile, 'gif', '300', '', '', '', [], true);
        if ($imResult !== null && is_file($imResult[3])) {
            if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gif_compress']) {
                clearstatcache();
                $previousSize = GeneralUtility::formatSize(filesize($imResult[3]));
                $methodUsed = GraphicalFunctions::gifCompress($imResult[3], '');
                clearstatcache();
                $compressedSize = GeneralUtility::formatSize(filesize($imResult[3]));
                /** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
                $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\InfoStatus::class);
                $message->setTitle('Compressed gif');
                $message->setMessage(
                    'Method used by compress: ' . $methodUsed . LF
                    . ' Previous filesize: ' . $previousSize . '. Current filesize:' . $compressedSize
                );
            } else {
                /** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
                $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\InfoStatus::class);
                $message->setTitle('Gif compression not enabled by [GFX][gif_compress]');
            }
            $testResults['gif']['message'] = $message;
            $testResults['gif']['title'] = 'Write gif';
            $testResults['gif']['outputFile'] = $imResult[3];
            $testResults['gif']['referenceFile'] = $this->imageBasePath . 'TestReference/Write-gif.gif';
            $testResults['gif']['command'] = $imageProcessor->IM_commands;
        } else {
            $testResults['gif']['error'] = $this->imageGenerationFailedMessage();
        }

        // Png
        $inputFile = $this->imageBasePath . 'TestInput/Test.png';
        $imageProcessor->IM_commands = [];
        $imageProcessor->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('write-png');
        $imResult = $imageProcessor->imageMagickConvert($inputFile, 'png', '300', '', '', '', [], true);
        if ($imResult !== null) {
            $testResults['png']['title'] = 'Write png';
            $testResults['png']['outputFile'] = $imResult[3];
            $testResults['png']['referenceFile'] = $this->imageBasePath . 'TestReference/Write-png.png';
            $testResults['png']['command'] = $imageProcessor->IM_commands;
        } else {
            $testResults['png']['error'] = $this->imageGenerationFailedMessage();
        }

        $this->view->assign('testResults', $testResults);
        return $this->imageTestDoneMessage(GeneralUtility::milliseconds() - $parseTimeStart);
    }

    /**
     * Write gif and png test
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function scaleImages()
    {
        $imageProcessor = $this->initializeImageProcessor();
        $parseTimeStart = GeneralUtility::milliseconds();

        $testResults = [
            'gif-to-gif' => [],
            'png-to-png' => [],
            'gif-to-jpg' => [],
        ];

        $imageProcessor->IM_commands = [];
        $inputFile = $this->imageBasePath . 'TestInput/Transparent.gif';
        $imageProcessor->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('scale-gif');
        $imResult = $imageProcessor->imageMagickConvert($inputFile, 'gif', '300', '', '', '', [], true);
        if ($imResult !== null) {
            $testResults['gif-to-gif']['title'] = 'gif to gif';
            $testResults['gif-to-gif']['outputFile'] = $imResult[3];
            $testResults['gif-to-gif']['referenceFile'] = $this->imageBasePath . 'TestReference/Scale-gif.gif';
            $testResults['gif-to-gif']['command'] = $imageProcessor->IM_commands;
        } else {
            $testResults['gif-to-gif']['error'] = $this->imageGenerationFailedMessage();
        }

        $imageProcessor->IM_commands = [];
        $inputFile = $this->imageBasePath . 'TestInput/Transparent.png';
        $imageProcessor->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('scale-png');
        $imResult = $imageProcessor->imageMagickConvert($inputFile, 'png', '300', '', '', '', [], true);
        if ($imResult !== null) {
            $testResults['png-to-png']['title'] = 'png to png';
            $testResults['png-to-png']['outputFile'] = $imResult[3];
            $testResults['png-to-png']['referenceFile'] = $this->imageBasePath . 'TestReference/Scale-png.png';
            $testResults['png-to-png']['command'] = $imageProcessor->IM_commands;
        } else {
            $testResults['png-to-png']['error'] = $this->imageGenerationFailedMessage();
        }

        $imageProcessor->IM_commands = [];
        $inputFile = $this->imageBasePath . 'TestInput/Transparent.gif';
        $imageProcessor->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('scale-jpg');
        $imResult = $imageProcessor->imageMagickConvert($inputFile, 'jpg', '300', '', '-opaque white -background white -flatten', '', [], true);
        if ($imResult !== null) {
            $testResults['gif-to-jpg']['title'] = 'gif to jpg';
            $testResults['gif-to-jpg']['outputFile'] = $imResult[3];
            $testResults['gif-to-jpg']['referenceFile'] = $this->imageBasePath . 'TestReference/Scale-jpg.jpg';
            $testResults['gif-to-jpg']['command'] = $imageProcessor->IM_commands;
        } else {
            $testResults['gif-to-jpg']['error'] = $this->imageGenerationFailedMessage();
        }

        $this->view->assign('testResults', $testResults);
        return $this->imageTestDoneMessage(GeneralUtility::milliseconds() - $parseTimeStart);
    }

    /**
     * Combine multiple images into one test
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function combineImages()
    {
        $imageProcessor = $this->initializeImageProcessor();
        $parseTimeStart = GeneralUtility::milliseconds();

        $testResults = [
            'combine1' => [],
            'combine2' => [],
        ];

        $inputFile = $this->imageBasePath . 'TestInput/BackgroundOrange.gif';
        $overlayFile = $this->imageBasePath . 'TestInput/Test.jpg';
        $maskFile = $this->imageBasePath . 'TestInput/MaskBlackWhite.gif';
        $resultFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix
            . StringUtility::getUniqueId($imageProcessor->alternativeOutputKey . 'combine1') . '.jpg';
        $imageProcessor->combineExec($inputFile, $overlayFile, $maskFile, $resultFile);
        $result = $imageProcessor->getImageDimensions($resultFile);
        if ($result) {
            $testResults['combine1']['title'] = 'Combine using a GIF mask with only black and white';
            $testResults['combine1']['outputFile'] = $result[3];
            $testResults['combine1']['referenceFile'] = $this->imageBasePath . 'TestReference/Combine-1.jpg';
            $testResults['combine1']['command'] = $imageProcessor->IM_commands;
        } else {
            $testResults['combine1']['error'] = $this->imageGenerationFailedMessage();
        }

        $imageProcessor->IM_commands = [];
        $inputFile = $this->imageBasePath . 'TestInput/BackgroundCombine.jpg';
        $overlayFile = $this->imageBasePath . 'TestInput/Test.jpg';
        $maskFile = $this->imageBasePath . 'TestInput/MaskCombine.jpg';
        $resultFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix
            . StringUtility::getUniqueId($imageProcessor->alternativeOutputKey . 'combine2') . '.jpg';
        $imageProcessor->combineExec($inputFile, $overlayFile, $maskFile, $resultFile);
        $result = $imageProcessor->getImageDimensions($resultFile);
        if ($result) {
            $testResults['combine2']['title'] = 'Combine using a JPG mask with graylevels';
            $testResults['combine2']['outputFile'] = $result[3];
            $testResults['combine2']['referenceFile'] = $this->imageBasePath . 'TestReference/Combine-2.jpg';
            $testResults['combine2']['command'] = $imageProcessor->IM_commands;
        } else {
            $testResults['combine2']['error'] = $this->imageGenerationFailedMessage();
        }

        $this->view->assign('testResults', $testResults);
        return $this->imageTestDoneMessage(GeneralUtility::milliseconds() - $parseTimeStart);
    }

    /**
     * Test gdlib functions
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function gdlib()
    {
        $imageProcessor = $this->initializeImageProcessor();
        $parseTimeStart = GeneralUtility::milliseconds();
        $gifOrPng = $imageProcessor->gifExtension;
        $testResults = [];

        // GD with simple box
        $imageProcessor->IM_commands = [];
        $image = imagecreatetruecolor(300, 225);
        $backgroundColor = imagecolorallocate($image, 0, 0, 0);
        imagefilledrectangle($image, 0, 0, 300, 225, $backgroundColor);
        $workArea = [0, 0, 300, 225];
        $conf = [
            'dimensions' => '10,50,280,50',
            'color' => 'olive',
        ];
        $imageProcessor->makeBox($image, $conf, $workArea);
        $outputFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdSimple') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $result = $imageProcessor->getImageDimensions($outputFile);
        $testResults['simple'] = [];
        $testResults['simple']['title'] = 'Create simple image';
        $testResults['simple']['outputFile'] = $result[3];
        $testResults['simple']['referenceFile'] = $this->imageBasePath . 'TestReference/Gdlib-simple.' . $gifOrPng;
        $testResults['simple']['command'] = $imageProcessor->IM_commands;

        // GD from image with box
        $imageProcessor->IM_commands = [];
        $inputFile = $this->imageBasePath . 'TestInput/Test.' . $gifOrPng;
        $image = $imageProcessor->imageCreateFromFile($inputFile);

        $workArea = [0, 0, 400, 300];
        $conf = [
            'dimensions' => '10,50,380,50',
            'color' => 'olive',
        ];
        $imageProcessor->makeBox($image, $conf, $workArea);
        $outputFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdBox') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $result = $imageProcessor->getImageDimensions($outputFile);
        $testResults['box'] = [];
        $testResults['box']['title'] = 'Create image from file';
        $testResults['box']['outputFile'] = $result[3];
        $testResults['box']['referenceFile'] = $this->imageBasePath . 'TestReference/Gdlib-box.' . $gifOrPng;
        $testResults['box']['command'] = $imageProcessor->IM_commands;

        // GD with text
        $imageProcessor->IM_commands = [];
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
            'fontFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('install') . 'Resources/Private/Font/vera.ttf',
            'offset' => '30,80',
        ];
        $conf['BBOX'] = $imageProcessor->calcBBox($conf);
        $imageProcessor->makeText($image, $conf, $workArea);
        $outputFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdText') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $result = $imageProcessor->getImageDimensions($outputFile);
        $testResults['text'] = [];
        $testResults['text']['title'] = 'Render text with TrueType font';
        $testResults['text']['outputFile'] = $result[3];
        $testResults['text']['referenceFile'] = $this->imageBasePath . 'TestReference/Gdlib-text.' . $gifOrPng;
        $testResults['text']['command'] = $imageProcessor->IM_commands;

        // GD with text, niceText
        $testResults['niceText'] = [];
        if ($this->isImageMagickEnabledAndConfigured()) {
            // Warning: Re-uses $conf from above!
            $conf['offset'] = '30,120';
            $conf['niceText'] = 1;
            $imageProcessor->makeText($image, $conf, $workArea);
            $outputFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdNiceText') . '.' . $gifOrPng;
            $imageProcessor->ImageWrite($image, $outputFile);
            $result = $imageProcessor->getImageDimensions($outputFile);
            $testResults['niceText']['title'] = 'Render text with TrueType font using \'niceText\' option';
            $testResults['niceText']['outputFile'] = $result[3];
            $testResults['niceText']['referenceFile'] = $this->imageBasePath . 'TestReference/Gdlib-niceText.' . $gifOrPng;
            $testResults['niceText']['command'] = $imageProcessor->IM_commands;
            /** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
            $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\InfoStatus::class);
            $message->setTitle('Note on \'niceText\'');
            $message->setMessage(
                '\'niceText\' is a concept that tries to improve the antialiasing of the rendered type by'
                . ' actually rendering the textstring in double size on a black/white mask, downscaling the mask'
                . ' and masking the text onto the image through this mask. This involves'
                . ' ImageMagick \'combine\'/\'composite\' and \'convert\'.'
            );
            $testResults['niceText']['message'] = $message;
        } else {
            $result['niceText']['error'] = $this->imageGenerationFailedMessage();
        }

        // GD with text, niceText, shadow
        $testResults['shadow'] = [];
        if ($this->isImageMagickEnabledAndConfigured()) {
            // Warning: Re-uses $conf from above!
            $conf['offset'] = '30,160';
            $conf['niceText'] = 1;
            $conf['shadow.'] = [
                'offset' => '2,2',
                'blur' => $imageProcessor->NO_IM_EFFECTS ? '90' : '20',
                'opacity' => '50',
                'color' => 'black'
            ];
            // Warning: Re-uses $image from above!
            $imageProcessor->makeShadow($image, $conf['shadow.'], $workArea, $conf);
            $imageProcessor->makeText($image, $conf, $workArea);
            $outputFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('GDwithText-niceText-shadow') . '.' . $gifOrPng;
            $imageProcessor->ImageWrite($image, $outputFile);
            $result = $imageProcessor->getImageDimensions($outputFile);
            $testResults['shadow']['title'] = 'Render \'niceText\' with a shadow under';
            $testResults['shadow']['outputFile'] = $result[3];
            $testResults['shadow']['referenceFile'] = $this->imageBasePath . 'TestReference/Gdlib-shadow.' . $gifOrPng;
            $testResults['shadow']['command'] = $imageProcessor->IM_commands;
            /** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
            $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\InfoStatus::class);
            $message->setTitle('Note on \'shadow\'');
            $message->setMessage(
                'This test makes sense only if the above test had a correct output. But if so, you may not see'
                . ' a soft dropshadow from the third text string as you should. In that case you are most likely'
                . ' using ImageMagick 5 and should set the flag TYPO3_CONF_VARS[GFX][processor_effects].'
            );
            $testResults['shadow']['message'] = $message;
        } else {
            $result['shadow']['error'] = $this->imageGenerationFailedMessage();
        }

        $this->view->assign('testResults', $testResults);
        return $this->imageTestDoneMessage(GeneralUtility::milliseconds() - $parseTimeStart);
    }

    /**
     * Create a 'image test was done' message
     *
     * @param int $parseTime Parse time
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function imageTestDoneMessage($parseTime = 0)
    {
        /** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
        $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\OkStatus::class);
        $message->setTitle('Executed image tests');
        $message->setMessage('Parse time: ' . $parseTime . ' ms');
        return $message;
    }

    /**
     * Create a 'imageMagick disabled' message
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function imageMagickDisabledMessage()
    {
        /** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
        $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
        $message->setTitle('Tests not executed');
        $message->setMessage('ImageMagick / GraphicsMagick handling is disabled or not configured correctly.');
        return $message;
    }

    /**
     * Create a 'image generation failed' message
     *
     * @return \TYPO3\CMS\Install\Status\StatusInterface
     */
    protected function imageGenerationFailedMessage()
    {
        /** @var \TYPO3\CMS\Install\Status\StatusInterface $message */
        $message = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Status\ErrorStatus::class);
        $message->setTitle('Image generation failed');
        $message->setMessage(
            'ImageMagick / GraphicsMagick handling is enabled, but the execute'
            . ' command returned an error. Please check your settings, especially'
            . ' [\'GFX\'][\'processor_path\'] and [\'GFX\'][\'processor_path_lzw\'] and ensure Ghostscript is installed on your server.'
        );
        return $message;
    }

    /**
     * Gather image configuration overview
     *
     * @return array Result array
     */
    protected function getImageConfiguration()
    {
        $result = [];
        $result['processor'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor'] === 'GraphicsMagick' ? 'GraphicsMagick' : 'ImageMagick';
        $result['processorEnabled'] =  $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled'];
        $result['processorPath'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'];
        $result['processorVersion'] = $this->determineImageMagickVersion();
        $result['processorEffects'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_effects'];
        $result['gdlibEnabled'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'];
        $result['gdlibPng'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png'];
        $result['fileFormats'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'];
        return $result;
    }

    /**
     * Initialize image processor
     *
     * @return GraphicalFunctions Initialized image processor
     */
    protected function initializeImageProcessor()
    {
        /** @var GraphicalFunctions $imageProcessor */
        $imageProcessor = GeneralUtility::makeInstance(GraphicalFunctions::class);
        $imageProcessor->init();
        $imageProcessor->absPrefix = PATH_site;
        $imageProcessor->dontCheckForExistingTempFile = 1;
        $imageProcessor->filenamePrefix = 'installTool-';
        $imageProcessor->dontCompress = 1;
        $imageProcessor->alternativeOutputKey = 'typo3InstallTest';
        return $imageProcessor;
    }

    /**
     * Find out if ImageMagick or GraphicsMagick is enabled and set up
     *
     * @return bool TRUE if enabled and path is set
     */
    protected function isImageMagickEnabledAndConfigured()
    {
        $enabled = $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_enabled'];
        $path = $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_path'];
        return $enabled && $path;
    }

    /**
     * Determine ImageMagick / GraphicsMagick version
     *
     * @return string Version
     */
    protected function determineImageMagickVersion()
    {
        $command = \TYPO3\CMS\Core\Utility\CommandUtility::imageMagickCommand('identify', '-version');
        \TYPO3\CMS\Core\Utility\CommandUtility::exec($command, $result);
        $string = $result[0];
        list(, $version) = explode('Magick', $string);
        list($version) = explode(' ', trim($version));
        return trim($version);
    }

    /**
     * Return the temp image dir.
     * If not exist it will be created
     *
     * @param GraphicalFunctions $imageProcessor
     * @return string
     */
    protected function getImagesPath(GraphicalFunctions $imageProcessor)
    {
        $imagePath = $imageProcessor->absPrefix . 'typo3temp/assets/images/';
        if (!is_dir($imagePath)) {
            GeneralUtility::mkdir_deep($imagePath);
        }
        return $imagePath;
    }
}
