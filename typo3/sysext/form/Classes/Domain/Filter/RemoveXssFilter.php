<?php
namespace TYPO3\CMS\Form\Domain\Filter;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Remove Cross Site Scripting filter
 */
class RemoveXssFilter extends AbstractFilter implements FilterInterface
{
    /**
     * Return filtered value
     * Removes potential XSS code from the input string.
     *
     * Using an external class by Travis Puderbaugh <kallahar@quickwired.com>
     *
     * @param string $value Unfiltered value
     * @return string The filtered value
     */
    public function filter($value)
    {
        $value = stripslashes($value);
        $value = html_entity_decode($value, ENT_QUOTES);
        $filteredValue = GeneralUtility::removeXSS($value);
        return $filteredValue;
    }
}
