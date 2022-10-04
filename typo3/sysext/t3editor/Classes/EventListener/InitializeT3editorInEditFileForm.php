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

namespace TYPO3\CMS\T3editor\EventListener;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\Event\ModifyEditFileFormDataEvent;
use TYPO3\CMS\T3editor\Exception\InvalidModeException;
use TYPO3\CMS\T3editor\Registry\ModeRegistry;
use TYPO3\CMS\T3editor\T3editor;

/**
 * Listener which modifies the form data to initialize t3editor with
 * the resolved format option (based on the file extension).
 */
final class InitializeT3editorInEditFileForm
{
    public function __construct(private readonly ModeRegistry $modeRegistry)
    {
    }

    public function __invoke(ModifyEditFileFormDataEvent $event): void
    {
        // Compile and register t3editor configuration
        GeneralUtility::makeInstance(T3editor::class)->registerConfiguration();

        $fileExtension = $event->getFile()->getExtension();

        try {
            $mode = $this->modeRegistry->getByFileExtension($fileExtension);
        } catch (InvalidModeException $e) {
            $mode = $this->modeRegistry->getDefaultMode();
        }

        $formData = $event->getFormData();
        $formData['processedTca']['columns']['data']['config']['renderType'] = 't3editor';
        $formData['processedTca']['columns']['data']['config']['format'] = $mode->getFormatCode();
        $event->setFormData($formData);
    }
}
