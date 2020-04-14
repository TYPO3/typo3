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

namespace TYPO3\CMS\Core\Resource\Security;

/**
 * Ensures that any filename that an editor chooses for naming (or uses for uploading a file) is valid, meaning
 * that no invalid characters (null-bytes) are added, or that the file does not contain an invalid file extension.
 */
class FileNameValidator
{
    /**
     * Previously this was used within SystemEnvironmentBuilder
     */
    public const DEFAULT_FILE_DENY_PATTERN = '\\.(php[3-8]?|phpsh|phtml|pht|phar|shtml|cgi)(\\..*)?$|\\.pl$|^\\.htaccess$';

    /**
     * @var string
     */
    protected $fileDenyPattern;

    public function __construct(string $fileDenyPattern = null)
    {
        if ($fileDenyPattern !== null) {
            $this->fileDenyPattern = $fileDenyPattern;
        } elseif (isset($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'])) {
            $this->fileDenyPattern = (string)$GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'];
        } else {
            $this->fileDenyPattern = static::DEFAULT_FILE_DENY_PATTERN;
        }
    }

    /**
     * Verifies the input filename against the 'fileDenyPattern'
     *
     * Filenames are not allowed to contain control characters. Therefore we
     * always filter on [[:cntrl:]].
     *
     * @param string $fileName File path to evaluate
     * @return bool Returns TRUE if the file name is OK.
     */
    public function isValid(string $fileName): bool
    {
        $pattern = '/[[:cntrl:]]/';
        if ($fileName !== '' && $this->fileDenyPattern !== '') {
            $pattern = '/(?:[[:cntrl:]]|' . $this->fileDenyPattern . ')/iu';
        }
        return preg_match($pattern, $fileName) === 0;
    }

    /**
     * Find out if there is a custom file deny pattern configured.
     *
     * @return bool
     */
    public function customFileDenyPatternConfigured(): bool
    {
        return $this->fileDenyPattern !== self::DEFAULT_FILE_DENY_PATTERN;
    }

    /**
     * Checks if the given file deny pattern does not have parts that the default pattern should
     * recommend. Used in status overview.
     *
     * @return bool
     */
    public function missingImportantPatterns(): bool
    {
        $defaultParts = explode('|', self::DEFAULT_FILE_DENY_PATTERN);
        $givenParts = explode('|', $this->fileDenyPattern);
        $missingParts = array_diff($defaultParts, $givenParts);
        return !empty($missingParts);
    }
}
