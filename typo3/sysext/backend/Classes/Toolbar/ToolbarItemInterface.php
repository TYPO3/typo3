<?php
namespace TYPO3\CMS\Backend\Toolbar;

/**
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
 * Interface for classes which extend the backend by adding items to the top toolbar
 *
 * @TODO: This interface is FIRST DRAFT and still WILL CHANGE
 * @see https://forge.typo3.org/issues/62928
 */
interface ToolbarItemInterface {

	/**
	 * Constructor that receives a back reference to the backend
	 *
	 * @param \TYPO3\CMS\Backend\Controller\BackendController TYPO3 backend object reference
	 */
	public function __construct(\TYPO3\CMS\Backend\Controller\BackendController &$backendReference = NULL);

	/**
	 * Checks whether the user has access to this toolbar item
	 *
	 * @return boolean TRUE if user has access, FALSE if not
	 */
	public function checkAccess();

	/**
	 * Renders the toolbar item
	 *
	 * @return string The toolbar item rendered as HTML string
	 */
	public function render();

	/**
	 * Return attribute id name
	 *
	 * @return string The name of the ID attribute
	 */
	public function getIdAttribute();

	/**
	 * Returns extra classes
	 *
	 * @return array
	 */
	public function getExtraClasses();

	/**
	 * TRUE if this toolbar item has a collapsible drop down
	 *
	 * @return bool
	 */
//	public function hasDropDrown();
// TODO put that back in action and fix all classes by 2014-11-15
	/**
	 * Returns additional attributes for the list item in the toolbar
	 *
	 * This should not contain the "class" or "id" attribute.
	 * Use the methods for setting these attributes
	 *
	 * @return string List item HTML attibutes
	 */
	public function getAdditionalAttributes();

	/**
	 * Returns an integer between 0 and 100 to determine
	 * the position of this item relative to others
	 *
	 * By default, extensions should return 50 to be sorted between main core
	 * items and other items that should be on the very right.
	 *
	 * @return integer 0 .. 100
	 */
	public function getIndex();

}
