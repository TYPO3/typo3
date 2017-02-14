<?php
namespace TYPO3\CMS\Backend\Form;

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
 * Interface for classes which hook into \TYPO3\CMS\Backend\Form\FormEngine
 * and do additional dbFileIcons processing
 *
 * @deprecated and no longer called since TYPO3 v8, will be removed in TYPO3 v9
 */
interface DatabaseFileIconsHookInterface
{
    /**
     * Modifies the parameters for selector box form-field for the db/file/select elements (multiple)
     *
     * @param array $params An array of additional parameters, eg: "size", "info", "headers" (array with "selector" and "items"), "noBrowser", "thumbnails
     * @param string $selector Alternative selector box.
     * @param string $thumbnails Thumbnail view of images. Only filled if there are images only. This images will be shown under the selectorbox.
     * @param array $icons Defined icons next to the selector box.
     * @param string $rightbox Thumbnail view of images. Only filled if there are other types as images. This images will be shown right next to the selectorbox.
     * @param string $fName Form element name
     * @param array $uidList The array of item-uids. Have a look at \TYPO3\CMS\Backend\Form\FormEngine::dbFileIcons parameter "$itemArray
     * @param array $additionalParams Array with additional parameters which are be available at method call. Includes $mode, $allowed, $itemArray, $onFocus, $table, $field, $uid.
     * @param object $parentObject Parent object
     */
    public function dbFileIcons_postProcess(array &$params, &$selector, &$thumbnails, array &$icons, &$rightbox, &$fName, array &$uidList, array $additionalParams, $parentObject);
}
