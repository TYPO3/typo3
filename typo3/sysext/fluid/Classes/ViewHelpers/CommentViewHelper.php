<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * This ViewHelper prevents rendering of any content inside the tag
 * Note: Contents of the comment will still be **parsed** thus throwing an
 * Exception if it contains syntax errors. You can put child nodes in
 * CDATA tags to avoid this.
 *
 * = Examples =
 *
 * <code title="Commenting out fluid code">
 * Before
 * <f:comment>
 *   This is completely hidden.
 *   <f:debug>This does not get parsed</f:debug>
 * </f:comment>
 * After
 * </code>
 * <output>
 * Before
 * After
 * </output>
 *
 * <code title="Prevent parsing">
 * <f:comment><![CDATA[
 *  <f:some.invalid.syntax />
 * ]]></f:comment>
 * </code>
 * <output>
 * </output>
 *
 * @api
 */
class CommentViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var boolean
	 */
	protected $escapingInterceptorEnabled = FALSE;

	/**
	 * Comments out the tag content
	 *
	 * @return string
	 * @api
	 */
	public function render() {
	}
}

?>