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

/**
 * Some Backend Utility functions for working with resources
 */
class BackendUtility
{
    /**
     * Create a flash message for a file that is marked as missing
     *
     * @param \TYPO3\CMS\Core\Resource\AbstractFile $file
     * @return \TYPO3\CMS\Core\Messaging\FlashMessage
     */
    public static function getFlashMessageForMissingFile(\TYPO3\CMS\Core\Resource\AbstractFile $file)
    {
        /** @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
        $flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Messaging\FlashMessage::class,
            $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_missing_text') .
            ' <abbr title="' . htmlspecialchars($file->getStorage()->getName() . ' :: ' . $file->getIdentifier()) . '">' .
            htmlspecialchars($file->getName()) . '</abbr>',
            $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_missing'),
            \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
        );

        return $flashMessage;
    }
}
