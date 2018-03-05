<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Form\Element;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This renderType is used with type=user in FAL for table sys_file and
 * sys_file_metadata, for field fileinfo and renders an informational
 * element with image preview, filename, size and similar.
 */
class FileInfoElement extends AbstractFormElement
{

    /**
     * Handler for single nodes
     *
     * @return array As defined in initializeResultArray() of AbstractNode
     */
    public function render(): array
    {
        $resultArray = $this->initializeResultArray();

        $fileUid = 0;
        if ($this->data['tableName'] === 'sys_file') {
            $fileUid = (int)$this->data['databaseRow']['uid'];
        } elseif ($this->data['tableName'] === 'sys_file_metadata') {
            $fileUid = (int)$this->data['databaseRow']['file'][0];
        }

        $fileObject = null;
        if ($fileUid > 0) {
            $fileObject = ResourceFactory::getInstance()->getFileObject($fileUid);
        }
        $resultArray['html'] = $this->renderFileInformationContent($fileObject);
        return $resultArray;
    }

    /**
     * Renders a HTML Block with file information
     *
     * @param File $file
     * @return string
     */
    protected function renderFileInformationContent(File $file = null): string
    {
        /** @var LanguageService $lang */
        $lang = $GLOBALS['LANG'];

        if ($file !== null) {
            $content = '';
            if ($file->isMissing()) {
                $content .= '<span class="label label-danger label-space-right">'
                    . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing'))
                    . '</span>';
            }
            if (GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $file->getExtension())) {
                $processedFile = $file->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, ['width' => 150, 'height' => 150]);
                $previewImage = $processedFile->getPublicUrl(true);
                if ($previewImage) {
                    $content .= '<img src="' . htmlspecialchars($previewImage) . '" ' .
                        'width="' . $processedFile->getProperty('width') . '" ' .
                        'height="' . $processedFile->getProperty('height') . '" ' .
                        'alt="" class="t3-tceforms-sysfile-imagepreview" />';
                }
            }
            $content .= '<strong>' . htmlspecialchars($file->getName()) . '</strong>';
            $content .= ' (' . htmlspecialchars(GeneralUtility::formatSize($file->getSize())) . 'bytes)<br />';
            $content .= BackendUtility::getProcessedValue('sys_file', 'type', $file->getType()) . ' (' . $file->getMimeType() . ')<br />';
            $content .= htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:fileMetaDataLocation')) . ': ';
            $content .= htmlspecialchars($file->getStorage()->getName()) . ' - ' . htmlspecialchars($file->getIdentifier()) . '<br />';
            $content .= '<br />';
        } else {
            $content = '<h2>' . htmlspecialchars($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:fileMetaErrorInvalidRecord')) . '</h2>';
        }

        return $content;
    }
}
