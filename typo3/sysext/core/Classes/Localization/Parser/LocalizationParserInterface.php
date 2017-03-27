<?php
namespace TYPO3\CMS\Core\Localization\Parser;

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
 * Parser interface.
 */
interface LocalizationParserInterface
{
    /**
     * Returns parsed representation of XML file.
     *
     * @param string $sourcePath Source file path
     * @param string $languageKey Language key
     * @param string $charset Charset, not in use anymore since TYPO3 v8, will be removed in TYPO3 v9 as UTF-8 is expected for all language files
     * @return array
     */
    public function getParsedData($sourcePath, $languageKey, $charset = '');
}
