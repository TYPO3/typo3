<?php
namespace TYPO3\CMS\Core\Resource\Utility;

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

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\AbstractFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Some Backend Utility functions for working with resources
 */
class BackendUtility
{
    /**
     * Create a flash message for a file that is marked as missing
     *
     * @param AbstractFile $file
     * @return FlashMessage
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0.
     */
    public static function getFlashMessageForMissingFile(AbstractFile $file)
    {
        trigger_error('This method will be removed in TYPO3 v10.0, create the flash message code in your own custom code in the correct context.', E_USER_DEPRECATED);

        /** @var LanguageService $lang */
        $lang = $GLOBALS['LANG'];

        /** @var FlashMessage $flashMessage */
        $flashMessage = GeneralUtility::makeInstance(
            FlashMessage::class,
            $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing_text') .
            ' <abbr title="' . htmlspecialchars($file->getStorage()->getName() . ' :: ' . $file->getIdentifier()) . '">' .
            htmlspecialchars($file->getName()) . '</abbr>',
            $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:warning.file_missing'),
            FlashMessage::ERROR
        );

        return $flashMessage;
    }
}
