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

namespace TYPO3\CMS\Backend\EventListener;

use TYPO3\CMS\Backend\CodeEditor\CodeEditor;
use TYPO3\CMS\Backend\CodeEditor\Exception\InvalidModeException;
use TYPO3\CMS\Backend\CodeEditor\Registry\ModeRegistry;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Filelist\Event\ModifyEditFileFormDataEvent;

/**
 * Listener which modifies the form data to initialize code editor with
 * the resolved format option (based on the file extension).
 */
final readonly class InitializeCodeEditorInEditFileForm
{
    public function __construct(private ModeRegistry $modeRegistry) {}

    #[AsEventListener('typo3-codeeditor/initialize-code-editor-in-edit-file-form')]
    public function __invoke(ModifyEditFileFormDataEvent $event): void
    {
        // Compile and register code editor configuration
        GeneralUtility::makeInstance(CodeEditor::class)->registerConfiguration();

        $fileExtension = $event->getFile()->getExtension();

        try {
            $mode = $this->modeRegistry->getByFileExtension($fileExtension);
        } catch (InvalidModeException $e) {
            $mode = $this->modeRegistry->getDefaultMode();
        }

        $formData = $event->getFormData();
        $formData['processedTca']['columns']['data']['config']['renderType'] = 'codeEditor';
        $formData['processedTca']['columns']['data']['config']['format'] = $mode->getFormatCode();
        $event->setFormData($formData);
    }
}
