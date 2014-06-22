<?php
namespace TYPO3\CMS\Documentation\ViewHelpers\Be\Security;

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
 * This view helper checks whether a given BE user is admin or not.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * <doc:be.security.ifAdmin>
 * You see this is you're an admin.
 * </doc:be.security.ifAdmin>
 * </code>
 * <output>
 * You see this is you're an admin. (if an admin user, of course)
 * </output>
 *
 * <code title="Usage with then / else">
 * <doc:be.security.ifAdmin>
 * <f:then>
 * You see this is you're an admin.
 * </f:then>
 * <f:else>
 * You see this is you're not an admin.
 * </f:else>
 * </doc:be.security.ifAdmin>
 * </code>
 * <output>
 * Content of the "then" tag if an admin, content of the "else" tag otherwise.
 * </output>
 *
 * @api
 */
class IfAdminViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractConditionViewHelper {

	/**
	 * Renders <f:then> child if the current logged in BE user is an admin,
	 * otherwise renders <f:else> child.
	 *
	 * @return string the rendered string
	 * @api
	 */
	public function render() {
		if ($GLOBALS['BE_USER']->isAdmin()) {
			return $this->renderThenChild();
		} else {
			return $this->renderElseChild();
		}
	}
}
