<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Be\Security;

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
 * This view helper implements an ifHasRole/else condition for BE users/groups.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <f:be.security.ifHasRole role="Administrator">
 * This is being shown in case the current BE user belongs to a BE usergroup (aka role) titled "Administrator" (case sensitive)
 * </f:be.security.ifHasRole>
 * </code>
 * <output>
 * Everything inside the <f:ifHasRole> tag is being displayed if the logged in BE user belongs to the specified role.
 * </output>
 *
 * <code title="Using the usergroup uid as role identifier">
 * <f:be.security.ifHasRole role="1">
 * This is being shown in case the current BE user belongs to a BE usergroup (aka role) with the uid "1"
 * </f:be.security.ifHasRole>
 * </code>
 * <output>
 * Everything inside the <f:ifHasRole> tag is being displayed if the logged in BE user belongs to the specified role.
 * </output>
 *
 * <code title="IfRole / then / else">
 * <f:be.security.ifHasRole role="Administrator">
 * <f:then>
 * This is being shown in case you have the role.
 * </f:then>
 * <f:else>
 * This is being displayed in case you do not have the role.
 * </f:else>
 * </f:be.security.ifHasRole>
 * </code>
 * <output>
 * Everything inside the "then" tag is displayed if the logged in BE user belongs to the specified role.
 * Otherwise, everything inside the "else"-tag is displayed.
 * </output>
 *
 * @api
 */
class IfHasRoleViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper {

	/**
	 * renders <f:then> child if the current logged in BE user belongs to the specified role (aka usergroup)
	 * otherwise renders <f:else> child.
	 *
	 * @param string $role The usergroup (either the usergroup uid or its title)
	 * @return string the rendered string
	 * @api
	 */
	public function render($role) {
		if ($this->backendUserHasRole($role)) {
			return $this->renderThenChild();
		} else {
			return $this->renderElseChild();
		}
	}

	/**
	 * Determines whether the currently logged in BE user belongs to the specified usergroup
	 *
	 * @param string $role The usergroup (either the usergroup uid or its title)
	 * @return bool TRUE if the currently logged in BE user belongs to $role
	 */
	protected function backendUserHasRole($role) {
		if (!is_array($GLOBALS['BE_USER']->userGroups)) {
			return FALSE;
		}
		if (is_numeric($role)) {
			foreach ($GLOBALS['BE_USER']->userGroups as $userGroup) {
				if ((int)$userGroup['uid'] === (int)$role) {
					return TRUE;
				}
			}
		} else {
			foreach ($GLOBALS['BE_USER']->userGroups as $userGroup) {
				if ($userGroup['title'] === $role) {
					return TRUE;
				}
			}
		}
		return FALSE;
	}

}
