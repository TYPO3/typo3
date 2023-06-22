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

namespace TYPO3\CMS\Linkvalidator\Linktype;

use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Linkvalidator\LinkAnalyzer;

/**
 * This class provides Check File Links plugin implementation
 */
class FileLinktype extends AbstractLinktype
{
    protected string $identifier = 'file';

    /**
     * Type fetching method, based on the type that softRefParserObj returns
     *
     * @param array $value Reference properties
     * @param string $type Current type
     * @param string $key Validator hook name
     * @return string fetched type
     */
    public function fetchType(array $value, string $type, string $key): string
    {
        if (str_starts_with(strtolower((string)$value['tokenValue']), 'file:')) {
            $type = 'file';
        }
        return $type;
    }

    /**
     * Checks a given URL + /path/filename.ext for validity
     *
     * @param string $url Url to check
     * @param array $softRefEntry The soft reference entry which builds the context of the url
     * @param \TYPO3\CMS\Linkvalidator\LinkAnalyzer $reference Parent instance
     * @return bool TRUE on success or FALSE on error
     */
    public function checkLink(string $url, array $softRefEntry, LinkAnalyzer $reference): bool
    {
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        try {
            $file = $resourceFactory->retrieveFileOrFolderObject($url);
        } catch (FileDoesNotExistException|FolderDoesNotExistException $e) {
            return false;
        }

        return ($file !== null) ? !$file->isMissing() : false;
    }

    /**
     * Generate the localized error message from the error params saved from the parsing
     *
     * @param array $errorParams All parameters needed for the rendering of the error message
     * @return string Validation error message
     */
    public function getErrorMessage(array $errorParams): string
    {
        return $this->getLanguageService()->sL('LLL:EXT:linkvalidator/Resources/Private/Language/Module/locallang.xlf:list.report.filenotexisting');
    }

    /**
     * Construct a valid Url for browser output
     *
     * @param array $row Broken link record
     * @return string Parsed broken url
     */
    public function getBrokenUrl(array $row): string
    {
        return $GLOBALS['TYPO3_REQUEST']->getAttribute('normalizedParams')->getSiteUrl() . $row['url'];
    }
}
