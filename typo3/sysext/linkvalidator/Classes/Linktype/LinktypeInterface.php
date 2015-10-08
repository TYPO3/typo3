<?php
namespace TYPO3\CMS\Linkvalidator\Linktype;

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
 * This class provides interface implementation.
 */
interface LinktypeInterface
{
    /**
     * Checks a given link for validity
     *
     * @param string $url Url to check
     * @param array $softRefEntry The soft reference entry which builds the context of that url
     * @param \TYPO3\CMS\Linkvalidator\LinkAnalyzer $reference Parent instance
     * @return string Validation error message or success code
     */
    public function checkLink($url, $softRefEntry, $reference);

    /**
     * Base type fetching method, based on the type that softRefParserObj returns.
     *
     * @param array $value Reference properties
     * @param string $type Current type
     * @param string $key Validator hook name
     * @return string Fetched type
     */
    public function fetchType($value, $type, $key);

    /**
     * Get the value of the private property errorParams.
     *
     * @return array All parameters needed for the rendering of the error message
     */
    public function getErrorParams();

    /**
     * Construct a valid Url for browser output
     *
     * @param array $row Broken link record
     * @return string Parsed broken url
     */
    public function getBrokenUrl($row);

    /**
     * Generate the localized error message from the error params saved from the parsing
     *
     * @param array $errorParams All parameters needed for the rendering of the error message
     * @return string Validation error message
     */
    public function getErrorMessage($errorParams);
}
