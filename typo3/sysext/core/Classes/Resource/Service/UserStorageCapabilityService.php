<?php
namespace TYPO3\CMS\Core\Resource\Service;

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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Exception\InvalidPathException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Utility class to render capabilities of the storage.
 */
class UserStorageCapabilityService
{
    /**
     * UserFunc function for rendering field "is_public".
     * There are some edge cases where "is_public" can never be marked as true in the BE,
     * for instance for storage located outside the document root or
     * for storages driven by special driver such as Flickr, ...
     *
     * @param array $propertyArray the array with additional configuration options.
     * @return string
     */
    public function renderIsPublic(array $propertyArray)
    {
        $isPublic = $GLOBALS['TCA']['sys_file_storage']['columns']['is_public']['config']['default'];
        $fileRecord = $propertyArray['row'];

        // Makes sure the storage object can be retrieved which is not the case when new storage.
        if ((int)$propertyArray['row']['uid'] > 0) {
            /** @var LanguageService $lang */
            $lang = $GLOBALS['LANG'];
            /** @var $flashMessageService FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var $defaultFlashMessageQueue FlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            try {
                $storage = ResourceFactory::getInstance()->getStorageObject($fileRecord['uid']);
                $storageRecord = $storage->getStorageRecord();
                $isPublic = $storage->isPublic() && $storageRecord['is_public'];
            } catch (InvalidPathException $e) {
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:filestorage.invalidpathexception.message'),
                    $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:filestorage.invalidpathexception.title'),
                    FlashMessage::ERROR
                );
                $defaultFlashMessageQueue->enqueue($message);
            }

            // Display a warning to the BE User in case settings is not inline with storage capability.
            if ($storageRecord['is_public'] && !$storage->isPublic()) {
                $message = GeneralUtility::makeInstance(
                    FlashMessage::class,
                    $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:warning.message.storage_is_no_public'),
                    $lang->sL('LLL:EXT:lang/Resources/Private/Language/locallang_core.xlf:warning.header.storage_is_no_public'),
                    FlashMessage::WARNING
                );
                $defaultFlashMessageQueue->enqueue($message);
            }
        }

        return $this->renderFileInformationContent($fileRecord, $isPublic);
    }

    /**
     * Renders a HTML block containing the checkbox for field "is_public".
     *
     * @param array $fileRecord
     * @param bool $isPublic
     * @return string
     */
    protected function renderFileInformationContent(array $fileRecord, $isPublic)
    {
        $template = '
		<div class="t3-form-field-item">
			<div class="checkbox">
				<label>
					<input name="data[sys_file_storage][{uid}][is_public]" value="0" type="hidden">
					<input class="checkbox" value="1" name="data[sys_file_storage][{uid}][is_public]_0" type="checkbox" %s>
				</label>
			</div>
		</div>';

        $content = sprintf(
            $template,
            $isPublic ? 'checked="checked"' : ''
        );

        return str_replace('{uid}', $fileRecord['uid'], $content);
    }
}
