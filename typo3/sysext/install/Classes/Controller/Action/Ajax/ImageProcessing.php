<?php
declare(strict_types=1);
namespace TYPO3\CMS\Install\Controller\Action\Ajax;

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
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Install\Status\ErrorStatus;
use TYPO3\CMS\Install\Status\InfoStatus;
use TYPO3\CMS\Install\Status\StatusInterface;
use TYPO3\CMS\Install\Status\WarningStatus;

/**
 * Execute an image test.
 */
class ImageProcessing extends AbstractAjaxAction
{
    /**
     * Executes the action
     *
     * @return array Rendered content
     */
    protected function executeAction(): array
    {
        $testType = $this->postValues['testType'];
        $testMethod = $testType . 'Test';
        if (!method_exists($this, $testType . 'Test')) {
            throw new \RuntimeException(
                'Test method ' . $testMethod . ' does not exist',
                1502977949
            );
        }
        $result = $this->$testMethod();
        if (!empty($result['referenceFile'])) {
            $fileExt = end(explode('.', $result['referenceFile']));
            $result['referenceFile'] = 'data:image/' . $fileExt . ';base64,' . base64_encode(file_get_contents($result['referenceFile']));
        }
        if (!empty($result['outputFile'])) {
            $fileExt = end(explode('.', $result['outputFile']));
            $result['outputFile'] = 'data:image/' . $fileExt . ';base64,' . base64_encode(file_get_contents($result['outputFile']));
        }
        $result['success'] = true;
        foreach ($result as $variable => $value) {
            $this->view->assign($variable, $value);
        }
        return $this->view->render();
    }

    /**
     * Create true type font test image
     *
     * @return array
     */
    protected function trueTypeTest(): array
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
        $outputFile = PATH_site . 'typo3temp/assets/images/installTool-' . StringUtility::getUniqueId('createTrueTypeFontTestImage') . '.gif';
        imagegif($image, $outputFile);
        return [
            'fileExists' => file_exists($outputFile),
            'outputFile' => $outputFile,
            'referenceFile' => PATH_site . 'typo3/sysext/install/Resources/Public/Images/TestReference/Font.gif',
        ];
    }

    /**
     * Convert to jpg from jpg
     *
     * @return array
     */
    protected function readJpgTest(): array
    {
        return $this->convertImageFormatsToJpg('jpg');
    }

    /**
     * Convert to jpg from gif
     *
     * @return array
     */
    protected function readGifTest(): array
    {
        return $this->convertImageFormatsToJpg('gif');
    }

    /**
     * Convert to jpg from png
     *
     * @return array
     */
    protected function readPngTest(): array
    {
        return $this->convertImageFormatsToJpg('png');
    }

    /**
     * Convert to jpg from tif
     *
     * @return array
     */
    protected function readTifTest(): array
    {
        return $this->convertImageFormatsToJpg('tif');
    }

    /**
     * Convert to jpg from pdf
     *
     * @return array
     */
    protected function readPdfTest(): array
    {
        return $this->convertImageFormatsToJpg('pdf');
    }

    /**
     * Convert to jpg from ai
     *
     * @return array
     */
    protected function readAiTest(): array
    {
        return $this->convertImageFormatsToJpg('ai');
    }

    /**
     * Convert to jpg from given input format
     *
     * @param string $inputFormat
     * @return array
     */
    protected function convertImageFormatsToJpg(string $inputFormat): array
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return [
                'status' => [ $this->imageMagickDisabledMessage() ],
            ];
        }
        if (!GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $inputFormat)) {
            $message = new WarningStatus();
            $message->setTitle('Skipped test');
            $message->setMessage('Handling format ' . $inputFormat . ' must be enabled in TYPO3_CONF_VARS[\'GFX\'][\'imagefile_ext\']');
            return [
                'status' => [ $message ]
            ];
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
                'referenceFile' => PATH_site . 'typo3/sysext/install/Resources/Public/Images/TestReference/Read-' . $inputFormat . '.jpg',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result['status'] = [ $this->imageGenerationFailedMessage() ];
        }
        return $result;
    }

    /**
     * Writing gif test
     *
     * @return array
     */
    protected function writeGifTest(): array
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return [
                'status' => [ $this->imageMagickDisabledMessage() ],
            ];
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $inputFile = $imageBasePath . 'TestInput/Test.gif';
        $imageProcessor = $this->initializeImageProcessor();
        $imageProcessor->imageMagickConvert_forceFileNameBody = StringUtility::getUniqueId('write-gif');
        $imResult = $imageProcessor->imageMagickConvert($inputFile, 'gif', '300', '', '', '', [], true);
        $messages = [];
        if ($imResult !== null && is_file($imResult[3])) {
            if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gif_compress']) {
                clearstatcache();
                $previousSize = GeneralUtility::formatSize(filesize($imResult[3]));
                $methodUsed = GraphicalFunctions::gifCompress($imResult[3], '');
                clearstatcache();
                $compressedSize = GeneralUtility::formatSize(filesize($imResult[3]));
                $message = new InfoStatus();
                $message->setTitle('Compressed gif');
                $message->setMessage(
                    'Method used by compress: ' . $methodUsed . LF
                    . ' Previous filesize: ' . $previousSize . '. Current filesize:' . $compressedSize
                );
                $messages[] = $message;
            } else {
                $message = new InfoStatus();
                $message->setTitle('Gif compression not enabled by [GFX][gif_compress]');
                $messages[] = $message;
            }
            $result = [
                'status' => $message,
                'fileExists' => true,
                'outputFile' => $imResult[3],
                'referenceFile' => PATH_site . 'typo3/sysext/install/Resources/Public/Images/TestReference/Write-gif.gif',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result = [
                'status' => [ $this->imageGenerationFailedMessage() ],
            ];
        }
        return $result;
    }

    /**
     * Writing png test
     *
     * @return array
     */
    protected function writePngTest(): array
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return [
                'status' => [ $this->imageMagickDisabledMessage() ],
            ];
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
                'referenceFile' => PATH_site . 'typo3/sysext/install/Resources/Public/Images/TestReference/Write-png.png',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result = [
                'status' => [ $this->imageGenerationFailedMessage() ],
            ];
        }
        return $result;
    }

    /**
     * Scaling transparent files - gif to gif
     *
     * @return array
     */
    protected function gifToGifTest(): array
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return [
                'status' => [ $this->imageMagickDisabledMessage() ],
            ];
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
                'referenceFile' => PATH_site . 'typo3/sysext/install/Resources/Public/Images/TestReference/Scale-gif.gif',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result = [
                'status' => [ $this->imageGenerationFailedMessage() ],
            ];
        }
        return $result;
    }

    /**
     * Scaling transparent files - png to png
     *
     * @return array
     */
    protected function pngToPngTest(): array
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return [
                'status' => [ $this->imageMagickDisabledMessage() ],
            ];
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
                'referenceFile' => PATH_site . 'typo3/sysext/install/Resources/Public/Images/TestReference/Scale-png.png',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result = [
                'status' => [ $this->imageGenerationFailedMessage() ],
            ];
        }
        return $result;
    }

    /**
     * Scaling transparent files - gif to jpg
     *
     * @return array
     */
    protected function gifToJpgTest(): array
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return [
                'status' => [ $this->imageMagickDisabledMessage() ],
            ];
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
                'referenceFile' => PATH_site . 'typo3/sysext/install/Resources/Public/Images/TestReference/Scale-jpg.jpg',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result = [
                'status' => [ $this->imageGenerationFailedMessage() ],
            ];
        }
        return $result;
    }

    /**
     * Combine images with gif mask
     *
     * @return array
     */
    protected function combineGifMaskTest(): array
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return [
                'status' => [ $this->imageMagickDisabledMessage() ],
            ];
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageProcessor = $this->initializeImageProcessor();
        $inputFile = $imageBasePath . 'TestInput/BackgroundOrange.gif';
        $overlayFile = $imageBasePath . 'TestInput/Test.jpg';
        $maskFile = $imageBasePath . 'TestInput/MaskBlackWhite.gif';
        $resultFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix
            . StringUtility::getUniqueId($imageProcessor->alternativeOutputKey . 'combine1') . '.jpg';
        $imageProcessor->combineExec($inputFile, $overlayFile, $maskFile, $resultFile);
        $imResult = $imageProcessor->getImageDimensions($resultFile);
        if ($imResult) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult[3],
                'referenceFile' => PATH_site . 'typo3/sysext/install/Resources/Public/Images/TestReference/Combine-1.jpg',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result = [
                'status' => [ $this->imageGenerationFailedMessage() ],
            ];
        }
        return $result;
    }

    /**
     * Combine images with jpg mask
     *
     * @return array
     */
    protected function combineJpgMaskTest(): array
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return [
                'status' => [ $this->imageMagickDisabledMessage() ],
            ];
        }
        $imageBasePath = ExtensionManagementUtility::extPath('install') . 'Resources/Public/Images/';
        $imageProcessor = $this->initializeImageProcessor();
        $inputFile = $imageBasePath . 'TestInput/BackgroundCombine.jpg';
        $overlayFile = $imageBasePath . 'TestInput/Test.jpg';
        $maskFile = $imageBasePath . 'TestInput/MaskCombine.jpg';
        $resultFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix
            . StringUtility::getUniqueId($imageProcessor->alternativeOutputKey . 'combine2') . '.jpg';
        $imageProcessor->combineExec($inputFile, $overlayFile, $maskFile, $resultFile);
        $imResult = $imageProcessor->getImageDimensions($resultFile);
        if ($imResult) {
            $result = [
                'fileExists' => true,
                'outputFile' => $imResult[3],
                'referenceFile' => PATH_site . 'typo3/sysext/install/Resources/Public/Images/TestReference/Combine-2.jpg',
                'command' => $imageProcessor->IM_commands,
            ];
        } else {
            $result = [
                'status' => [ $this->imageGenerationFailedMessage() ],
            ];
        }
        return $result;
    }

    /**
     * GD with simple box
     *
     * @return array
     */
    protected function gdlibSimpleTest(): array
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
        $outputFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdSimple') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $imResult = $imageProcessor->getImageDimensions($outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $imResult[3],
            'referenceFile' => PATH_site . 'typo3/sysext/install/Resources/Public/Images/TestReference/Gdlib-simple.' . $gifOrPng,
            'command' => $imageProcessor->IM_commands,
        ];
        return $result;
    }

    /**
     * GD from image with box
     *
     * @return array
     */
    protected function gdlibFromFileTest(): array
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
        $outputFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdBox') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $imResult = $imageProcessor->getImageDimensions($outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $imResult[3],
            'referenceFile' => PATH_site . 'typo3/sysext/install/Resources/Public/Images/TestReference/Gdlib-box.' . $gifOrPng,
            'command' => $imageProcessor->IM_commands,
        ];
        return $result;
    }

    /**
     * GD with text
     *
     * @return array
     */
    protected function gdlibRenderTextTest(): array
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
        $outputFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdText') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $imResult = $imageProcessor->getImageDimensions($outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $imResult[3],
            'referenceFile' => PATH_site . 'typo3/sysext/install/Resources/Public/Images/TestReference/Gdlib-text.' . $gifOrPng,
            'command' => $imageProcessor->IM_commands,
        ];
        return $result;
    }

    /**
     * GD with text, niceText
     *
     * @return array
     */
    protected function gdlibNiceTextTest(): array
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return [
                'status' => [ $this->imageMagickDisabledMessage() ],
            ];
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
        $outputFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdText') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $conf['offset'] = '30,120';
        $conf['niceText'] = 1;
        $imageProcessor->makeText($image, $conf, $workArea);
        $outputFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdNiceText') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $imResult = $imageProcessor->getImageDimensions($outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $imResult[3],
            'referenceFile' => PATH_site . 'typo3/sysext/install/Resources/Public/Images/TestReference/Gdlib-niceText.' . $gifOrPng,
            'command' => $imageProcessor->IM_commands,
        ];
        return $result;
    }

    /**
     * GD with text, niceText, shadow
     *
     * @return array
     */
    protected function gdlibNiceTextShadowTest(): array
    {
        if (!$this->isImageMagickEnabledAndConfigured()) {
            return [
                'status' => [ $this->imageMagickDisabledMessage() ],
            ];
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
        $outputFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdText') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
        $conf['offset'] = '30,120';
        $conf['niceText'] = 1;
        $imageProcessor->makeText($image, $conf, $workArea);
        $outputFile = $this->getImagesPath($imageProcessor) . $imageProcessor->filenamePrefix . StringUtility::getUniqueId('gdNiceText') . '.' . $gifOrPng;
        $imageProcessor->ImageWrite($image, $outputFile);
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
        $imResult = $imageProcessor->getImageDimensions($outputFile);
        $result = [
            'fileExists' => true,
            'outputFile' => $imResult[3],
            'referenceFile' => PATH_site . 'typo3/sysext/install/Resources/Public/Images/TestReference/Gdlib-shadow.' . $gifOrPng,
            'command' => $imageProcessor->IM_commands,
        ];
        return $result;
    }

    /**
     * Initialize image processor
     *
     * @return GraphicalFunctions Initialized image processor
     */
    protected function initializeImageProcessor(): GraphicalFunctions
    {
        $imageProcessor = GeneralUtility::makeInstance(GraphicalFunctions::class);
        $imageProcessor->init();
        $imageProcessor->absPrefix = PATH_site;
        $imageProcessor->dontCheckForExistingTempFile = 1;
        $imageProcessor->filenamePrefix = 'installTool-';
        $imageProcessor->dontCompress = 1;
        $imageProcessor->alternativeOutputKey = 'typo3InstallTest';
        $imageProcessor->noFramePrepended = $GLOBALS['TYPO3_CONF_VARS']['GFX']['processor_allowFrameSelection'];
        return $imageProcessor;
    }

    /**
     * Create a 'image generation failed' message
     *
     * @return StatusInterface
     */
    protected function imageGenerationFailedMessage(): StatusInterface
    {
        /** @var StatusInterface $message */
        $message = GeneralUtility::makeInstance(ErrorStatus::class);
        $message->setTitle('Image generation failed');
        $message->setMessage(
            'ImageMagick / GraphicsMagick handling is enabled, but the execute'
            . ' command returned an error. Please check your settings, especially'
            . ' [\'GFX\'][\'processor_path\'] and [\'GFX\'][\'processor_path_lzw\'] and ensure Ghostscript is installed on your server.'
        );
        return $message;
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
     * @return StatusInterface
     */
    protected function imageMagickDisabledMessage(): StatusInterface
    {
        $message = new ErrorStatus();
        $message->setTitle('Tests not executed');
        $message->setMessage('ImageMagick / GraphicsMagick handling is disabled or not configured correctly.');
        return $message;
    }

    /**
     * Return the temp image dir.
     * If not exist it will be created
     *
     * @param GraphicalFunctions $imageProcessor
     * @return string
     */
    protected function getImagesPath(GraphicalFunctions $imageProcessor): string
    {
        $imagePath = $imageProcessor->absPrefix . 'typo3temp/assets/images/';
        if (!is_dir($imagePath)) {
            GeneralUtility::mkdir_deep($imagePath);
        }
        return $imagePath;
    }
}
