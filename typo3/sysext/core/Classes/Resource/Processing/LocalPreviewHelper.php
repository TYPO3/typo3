<?php
namespace TYPO3\CMS\Core\Resource\Processing;

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
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Helper for creating local image previews using TYPO3s image processing classes.
 */
class LocalPreviewHelper
{
    /**
     * @var LocalImageProcessor
     */
    protected $processor;

    /**
     * @param LocalImageProcessor $processor
     */
    public function __construct(LocalImageProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * This method actually does the processing of files locally
     *
     * takes the original file (on remote storages this will be fetched from the remote server)
     * does the IM magic on the local server by creating a temporary typo3temp/ file
     * copies the typo3temp/ file to the processing folder of the target storage
     * removes the typo3temp/ file
     *
     * The returned array has the following structure:
     *   width => 100
     *   height => 200
     *   filePath => /some/path
     *
     * If filePath isn't set but width and height are the original file is used as ProcessedFile
     * with the returned width and height. This is for example useful for SVG images.
     *
     * @param TaskInterface $task
     * @return array|NULL
     */
    public function process(TaskInterface $task)
    {
        $sourceFile = $task->getSourceFile();

        // Merge custom configuration with default configuration
        $configuration = array_merge(['width' => 64, 'height' => 64], $task->getConfiguration());
        $configuration['width'] = MathUtility::forceIntegerInRange($configuration['width'], 1);
        $configuration['height'] = MathUtility::forceIntegerInRange($configuration['height'], 1);

        // Do not scale up if the source file has a size and the target size is larger
        if ($sourceFile->getProperty('width') > 0 && $sourceFile->getProperty('height') > 0
            && $configuration['width'] > $sourceFile->getProperty('width')
            && $configuration['height'] > $sourceFile->getProperty('height')) {
            return null;
        }

        return $this->generatePreviewFromFile($sourceFile, $configuration, $this->getTemporaryFilePath($task));
    }

    /**
     * Returns the path to a temporary file for processing
     *
     * @param TaskInterface $task
     * @return string
     */
    protected function getTemporaryFilePath(TaskInterface $task)
    {
        return GeneralUtility::tempnam('preview_', '.' . $task->getTargetFileExtension());
    }

    /**
     * Generates a preview for a file
     *
     * @param File $file The source file
     * @param array $configuration Processing configuration
     * @param string $targetFilePath Output file path
     * @return array|NULL
     */
    protected function generatePreviewFromFile(File $file, array $configuration, $targetFilePath)
    {
        $originalFileName = $file->getForLocalProcessing(false);

        // Check file extension
        if ($file->getType() != File::FILETYPE_IMAGE &&
            !GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $file->getExtension())) {
            // Create a default image
            $graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
            $graphicalFunctions->getTemporaryImageWithText(
                $targetFilePath,
                'Not imagefile!',
                'No ext!',
                $file->getName()
            );
            $result = [
                'filePath' => $targetFilePath,
            ];
        } elseif ($file->getExtension() === 'svg') {
            /** @var $gifBuilder \TYPO3\CMS\Frontend\Imaging\GifBuilder */
            $gifBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\Imaging\GifBuilder::class);
            $gifBuilder->init();
            $gifBuilder->absPrefix = PATH_site;
            $info = $gifBuilder->getImageDimensions($originalFileName);
            $newInfo = $gifBuilder->getImageScale($info, $configuration['width'], $configuration['height'], []);
            $result = [
                'width' => $newInfo[0],
                'height' => $newInfo[1],
                'filePath' => '' // no file = use original
            ];
        } else {
            // Create the temporary file
            if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) {
                $parameters = '-sample ' . $configuration['width'] . 'x' . $configuration['height'] . ' '
                    . CommandUtility::escapeShellArgument($originalFileName) . '[0] ' . CommandUtility::escapeShellArgument($targetFilePath);

                $cmd = GeneralUtility::imageMagickCommand('convert', $parameters) . ' 2>&1';
                CommandUtility::exec($cmd);

                if (!file_exists($targetFilePath)) {
                    // Create an error gif
                    $graphicalFunctions = GeneralUtility::makeInstance(GraphicalFunctions::class);
                    $graphicalFunctions->getTemporaryImageWithText(
                        $targetFilePath,
                        'No thumb',
                        'generated!',
                        $file->getName()
                    );
                }
            }
            $result = [
                'filePath' => $targetFilePath,
            ];
        }

        return $result;
    }
}
