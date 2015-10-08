<?php
namespace TYPO3\CMS\Documentation\Utility;

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
 * Utility for language selection.
 */
class LanguageUtility implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Returns the language from BE User settings.
     *
     * @return string language identifier 2 chars or default (English)
     */
    public function getDocumentationLanguage()
    {
        $backendLanguage = $GLOBALS['BE_USER']->uc['lang'] ?: 'default';
        return $backendLanguage;
    }
}
