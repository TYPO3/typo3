<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Security;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is backported from the TYPO3 Flow package "TYPO3.Fluid".
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
 * This view helper implements an ifAuthenticated/else condition for FE users/groups.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <f:security.ifAuthenticated>
 * This is being shown whenever a FE user is logged in
 * </f:security.ifAuthenticated>
 * </code>
 * <output>
 * Everything inside the <f:ifAuthenticated> tag is being displayed if you are authenticated with any FE user account.
 * </output>
 *
 * <code title="IfAuthenticated / then / else">
 * <f:security.ifAuthenticated>
 * <f:then>
 * This is being shown in case you have access.
 * </f:then>
 * <f:else>
 * This is being displayed in case you do not have access.
 * </f:else>
 * </f:security.ifAuthenticated>
 * </code>
 * <output>
 * Everything inside the "then" tag is displayed if you have access.
 * Otherwise, everything inside the "else"-tag is displayed.
 * </output>
 *
 * @api
 */
class IfAuthenticatedViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper {

	/**
	 * Renders <f:then> child if any FE user is currently authenticated, otherwise renders <f:else> child.
	 *
	 * @return string the rendered string
	 * @api
	 */
	public function render() {
		if (isset($GLOBALS['TSFE']) && $GLOBALS['TSFE']->loginUser) {
			return $this->renderThenChild();
		}
		return $this->renderElseChild();
	}

}
