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
 * This view helper implements an ifHasRole/else condition for FE users/groups.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <f:security.ifHasRole role="Administrator">
 * This is being shown in case the current FE user belongs to a FE usergroup (aka role) titled "Administrator" (case sensitive)
 * </f:security.ifHasRole>
 * </code>
 * <output>
 * Everything inside the <f:ifHasRole> tag is being displayed if the logged in FE user belongs to the specified role.
 * </output>
 *
 * <code title="Using the usergroup uid as role identifier">
 * <f:security.ifHasRole role="1">
 * This is being shown in case the current FE user belongs to a FE usergroup (aka role) with the uid "1"
 * </f:security.ifHasRole>
 * </code>
 * <output>
 * Everything inside the <f:ifHasRole> tag is being displayed if the logged in FE user belongs to the specified role.
 * </output>
 *
 * <code title="IfRole / then / else">
 * <f:security.ifHasRole role="Administrator">
 * <f:then>
 * This is being shown in case you have the role.
 * </f:then>
 * <f:else>
 * This is being displayed in case you do not have the role.
 * </f:else>
 * </f:security.ifHasRole>
 * </code>
 * <output>
 * Everything inside the "then" tag is displayed if the logged in FE user belongs to the specified role.
 * Otherwise, everything inside the "else"-tag is displayed.
 * </output>
 *
 * @api
 */
class IfHasRoleViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper {

	/**
	 * renders <f:then> child if the current logged in FE user belongs to the specified role (aka usergroup)
	 * otherwise renders <f:else> child.
	 *
	 * @param string $role The usergroup (either the usergroup uid or its title)
	 * @return string the rendered string
	 * @api
	 */
	public function render($role) {
		if ($this->frontendUserHasRole($role)) {
			return $this->renderThenChild();
		} else {
			return $this->renderElseChild();
		}
	}

	/**
	 * Determines whether the currently logged in FE user belongs to the specified usergroup
	 *
	 * @param string $role The usergroup (either the usergroup uid or its title)
	 * @return boolean TRUE if the currently logged in FE user belongs to $role
	 */
	protected function frontendUserHasRole($role) {
		if (!isset($GLOBALS['TSFE']) || !$GLOBALS['TSFE']->loginUser) {
			return FALSE;
		}
		if (is_numeric($role)) {
			return is_array($GLOBALS['TSFE']->fe_user->groupData['uid']) && in_array($role, $GLOBALS['TSFE']->fe_user->groupData['uid']);
		} else {
			return is_array($GLOBALS['TSFE']->fe_user->groupData['title']) && in_array($role, $GLOBALS['TSFE']->fe_user->groupData['title']);
		}
	}
}
