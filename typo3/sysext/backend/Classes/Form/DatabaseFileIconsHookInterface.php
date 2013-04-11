<?php
namespace TYPO3\CMS\Backend\Form;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Andy Grunwald <andreas.grunwald@wmdb.de>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Interface for classes which hook into \TYPO3\CMS\Backend\Form\FormEngine
 * and do additional dbFileIcons processing
 *
 * @author Andy Grunwald <andreas.grunwald@wmdb.de>
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
	 * @param array $additionalParams Array with additional parameters which are be available at method call. Includes $mode, $allowed, $itemArray, $onFocus, $table, $field, $uid. For more information have a look at PHPDoc-Comment of \TYPO3\CMS\Backend\Form\FormEngine::dbFileIcons
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $parentObject Parent object
	 * @return void
	 */
	public function dbFileIcons_postProcess(array &$params, &$selector, &$thumbnails, array &$icons, &$rightbox, &$fName, array &$uidList, array $additionalParams, \TYPO3\CMS\Backend\Form\FormEngine $parentObject);

}

?>
