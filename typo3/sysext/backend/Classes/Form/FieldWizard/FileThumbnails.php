<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Backend\Form\FieldWizard;

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

use TYPO3\CMS\Backend\Form\AbstractNode;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Render thumbnails of selected files,
 * typically used with type=group and internal_type=file and file_reference.
 */
class FileThumbnails extends AbstractNode
{
    /**
     * Render thumbnails of selected files
     *
     * @return array
     */
    public function render(): array
    {
        $result = $this->initializeResultArray();

        $table = $this->data['tableName'];
        $fieldName = $this->data['fieldName'];
        $row = $this->data['databaseRow'];
        $parameterArray = $this->data['parameterArray'];
        $config = $parameterArray['fieldConf']['config'];
        $selectedItems = $parameterArray['itemFormElValue'];

        if (!isset($config['internal_type'])
            || ($config['internal_type'] !== 'file' && $config['internal_type'] !== 'file_reference')
        ) {
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Deprecation logged by TcaMigration class.
            // Thumbnails only make sense on file and file_reference
            return $result;
        }

        $fileFactory = ResourceFactory::getInstance();
        $thumbnailsHtml = [];
        foreach ($selectedItems as $selectedItem) {
            $uidOrPath = $selectedItem['uidOrPath'];
            if (MathUtility::canBeInterpretedAsInteger($uidOrPath)) {
                $fileObject = $fileFactory->getFileObject($uidOrPath);
                if (!$fileObject->isMissing()) {
                    $extension = $fileObject->getExtension();
                    if (GeneralUtility::inList(
                        $GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],
                        $extension
                    )
                    ) {
                        $thumbnailsHtml[] = '<li>';
                        $thumbnailsHtml[] =     '<span class="thumbnail">';
                        $thumbnailsHtml[] =         $fileObject->process(ProcessedFile::CONTEXT_IMAGEPREVIEW, [])->getPublicUrl(true);
                        $thumbnailsHtml[] =     '</span>';
                        $thumbnailsHtml[] = '</li>';
                    }
                }
            } else {
                $rowCopy = [];
                $rowCopy[$fieldName] = $uidOrPath;
                try {
                    $icon = BackendUtility::thumbCode(
                        $rowCopy,
                        $table,
                        $fieldName,
                        '',
                        '',
                        $config['uploadfolder'],
                        0,
                        ' align="middle"'
                    );
                    $thumbnailsHtml[] =
                        '<li>'
                        . '<span class="thumbnail">'
                        . $icon
                        . '</span>'
                        . '</li>';
                } catch (\Exception $exception) {
                    $message = $exception->getMessage();
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        $message,
                        '',
                        FlashMessage::ERROR,
                        true
                    );
                    $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                    $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                    $defaultFlashMessageQueue->enqueue($flashMessage);
                    $this->logger->warning($message, ['table' => $table, 'row' => $row]);
                }
            }
        }

        $html = [];
        if (!empty($thumbnailsHtml)) {
            $html[] = '<ul class="list-inline">';
            $html[] =   implode(LF, $thumbnailsHtml);
            $html[] = '</ul>';
        }

        $result['html'] = implode(LF, $html);
        return $result;
    }
}
