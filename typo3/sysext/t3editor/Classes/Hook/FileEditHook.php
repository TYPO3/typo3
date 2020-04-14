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

namespace TYPO3\CMS\T3editor\Hook;

use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\Controller\File\EditFileController;
use TYPO3\CMS\T3editor\Exception\InvalidModeException;
use TYPO3\CMS\T3editor\Registry\ModeRegistry;
use TYPO3\CMS\T3editor\T3editor;

/**
 * File edit hook for t3editor
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
class FileEditHook
{
    /**
     * Hook-function: inject t3editor JavaScript code before the page is compiled
     * called in file_edit module
     *
     * @param array $parameters
     * @param EditFileController $pObj
     *
     * @throws \InvalidArgumentException
     */
    public function preOutputProcessingHook(array $parameters, EditFileController $pObj)
    {
        // Compile and register t3editor configuration
        GeneralUtility::makeInstance(T3editor::class)->registerConfiguration();

        $target = '';
        if (isset($parameters['target']) && is_string($parameters['target'])) {
            $target = $parameters['target'];
        }

        $fileExtension = GeneralUtility::makeInstance(ResourceFactory::class)->retrieveFileOrFolderObject($target)->getExtension();
        $modeRegistry = GeneralUtility::makeInstance(ModeRegistry::class);
        try {
            $mode = $modeRegistry->getByFileExtension($fileExtension);
        } catch (InvalidModeException $e) {
            $mode = $modeRegistry->getDefaultMode();
        }

        $parameters['dataColumnDefinition']['config']['renderType'] = 't3editor';
        $parameters['dataColumnDefinition']['config']['format'] = $mode->getFormatCode();
    }
}
