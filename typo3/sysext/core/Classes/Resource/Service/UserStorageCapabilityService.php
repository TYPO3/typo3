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

use TYPO3\CMS\Core\Resource\ResourceFactory;

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
            $storage = ResourceFactory::getInstance()->getStorageObject($fileRecord['uid']);
            $storageRecord = $storage->getStorageRecord();
            $isPublic = $storage->isPublic() && $storageRecord['is_public'];

            // Display a warning to the BE User in case settings is not inline with storage capability.
            if ($storageRecord['is_public'] && !$storage->isPublic()) {
                $message = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
                    $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.message.storage_is_no_public'),
                    $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.header.storage_is_no_public'),
                    \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING
                );

                /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
                $flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessageService::class);
                /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
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

        $content = sprintf($template,
            $isPublic ? 'checked="checked"' : ''
        );

        return str_replace('{uid}', $fileRecord['uid'], $content);
    }
}
