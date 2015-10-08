<?php
namespace TYPO3\CMS\Frontend\ContentObject;

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
 * interface for classes which hook into \TYPO3\CMS\Frontend\ContentObject and do additional getData processing
 */
interface ContentObjectGetDataHookInterface
{
    /**
     * Extends the getData()-Method of \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer to process more/other commands
     *
     * @param string $getDataString Full content of getData-request e.g. "TSFE:id // field:title // field:uid
     * @param array $fields Current field-array
     * @param string $sectionValue Currently examined section value of the getData request e.g. "field:title
     * @param string $returnValue Current returnValue that was processed so far by getData
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $parentObject Parent content object
     * @return string Get data result
     */
    public function getDataExtension($getDataString, array $fields, $sectionValue, $returnValue, ContentObjectRenderer &$parentObject);
}
