<?php

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

namespace TYPO3\CMS\Frontend\ContentObject;

/**
 * interface for classes which hook into \TYPO3\CMS\Frontend\ContentObject and do additional stdWrap processing
 */
interface ContentObjectStdWrapHookInterface
{
    /**
     * Hook for modifying $content before core's stdWrap does anything
     *
     * @param string $content Input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
     * @param array $configuration TypoScript stdWrap properties
     * @param ContentObjectRenderer $parentObject Parent content object
     * @return string Further processed $content
     */
    public function stdWrapPreProcess($content, array $configuration, ContentObjectRenderer &$parentObject);

    /**
     * Hook for modifying $content after core's stdWrap has processed setContentToCurrent, setCurrent, lang, data, field, current, cObject, numRows, filelist and/or preUserFunc
     *
     * @param string $content Input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
     * @param array $configuration TypoScript stdWrap properties
     * @param ContentObjectRenderer $parentObject Parent content object
     * @return string Further processed $content
     */
    public function stdWrapOverride($content, array $configuration, ContentObjectRenderer &$parentObject);

    /**
     * Hook for modifying $content after core's stdWrap has processed override, preIfEmptyListNum, ifEmpty, ifBlank, listNum, trim and/or more (nested) stdWraps
     *
     * @param string $content Input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
     * @param array $configuration TypoScript "stdWrap properties".
     * @param ContentObjectRenderer $parentObject Parent content object
     * @return string Further processed $content
     */
    public function stdWrapProcess($content, array $configuration, ContentObjectRenderer &$parentObject);

    /**
     * Hook for modifying $content after core's stdWrap has processed anything but debug
     *
     * @param string $content Input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
     * @param array $configuration TypoScript stdWrap properties
     * @param ContentObjectRenderer $parentObject Parent content object
     * @return string Further processed $content
     */
    public function stdWrapPostProcess($content, array $configuration, ContentObjectRenderer &$parentObject);
}
