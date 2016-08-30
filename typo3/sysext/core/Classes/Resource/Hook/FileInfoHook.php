<?php
namespace TYPO3\CMS\Core\Resource\Hook;

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
use TYPO3\CMS\Core\Resource\ResourceFactory;

/**
 * Utility class to render TCEforms information about a sys_file record
 */
class FileInfoHook
{
    /**
     * User function for sys_file (element)
     *
     * @param array $propertyArray the array with additional configuration options.
     * @return string The HTML code for the TCEform field
     */
    public function renderFileInfo(array $propertyArray)
    {
        $fileRecord = $propertyArray['row'];
        $fileObject = null;
        if ($fileRecord['uid'] > 0) {
            $fileObject = ResourceFactory::getInstance()->getFileObject((int)$fileRecord['uid']);
        }
        return $this->renderFileInformationContent($fileObject);
    }

    /**
     * User function for sys_file_meta (element)
     *
     * @param array $propertyArray the array with additional configuration options.
     * @return string The HTML code for the TCEform field
     */
    public function renderFileMetadataInfo(array $propertyArray)
    {
        $fileMetadataRecord = $propertyArray['row'];
        $fileObject = null;
        if (!empty($fileMetadataRecord['file']) && $fileMetadataRecord['file'][0] > 0) {
            $fileObject = ResourceFactory::getInstance()->getFileObject((int)$fileMetadataRecord['file'][0]);
        }

        return $this->renderFileInformationContent($fileObject);
    }

    /**
     * Renders a HTML Block with file information
     *
     * @param \TYPO3\CMS\Core\Resource\File $file
     * @return string
     */
    protected function renderFileInformationContent(\TYPO3\CMS\Core\Resource\File $file = null)
    {
        if ($file !== null) {
            $processedFile = $file->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW, ['width' => 150, 'height' => 150]);
            $previewImage = $processedFile->getPublicUrl(true);
            $content = '';
            if ($file->isMissing()) {
                $content .= '<span class="label label-danger label-space-right">'
                    . htmlspecialchars($lang->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_missing'))
                    . '</span>';
            }
            if ($previewImage) {
                $content .= '<img src="' . htmlspecialchars($previewImage) . '" ' .
                            'width="' . $processedFile->getProperty('width') . '" ' .
                            'height="' . $processedFile->getProperty('height') . '" ' .
                            'alt="" class="t3-tceforms-sysfile-imagepreview" />';
            }
            $content .= '<strong>' . htmlspecialchars($file->getName()) . '</strong>';
            $content .= ' (' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($file->getSize())) . 'bytes)<br />';
            $content .= BackendUtility::getProcessedValue('sys_file', 'type', $file->getType()) . ' (' . $file->getMimeType() . ')<br />';
            $content .= $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:fileMetaDataLocation', true) . ': ';
            $content .= htmlspecialchars($file->getStorage()->getName()) . ' - ' . htmlspecialchars($file->getIdentifier()) . '<br />';
            $content .= '<br />';
        } else {
            $content = '<h2>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:fileMetaErrorInvalidRecord', true) . '</h2>';
        }

        return $content;
    }
}
