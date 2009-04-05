<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id$
 */

/**
 * A view helper for escaping HTML. Any HTML character in the body of this tag will
 * be escaped to an HTML entity.
 *
 * Example:
 * <f:format.htmlEscape><p>This will be <em>escaped</em></p></f:format.htmlEscape>
 *
 * Output:
 * &lt;p&gt;This will be &lt;em&gt;escaped&lt;/em&gt;&lt;/p&gt;
 *
 * @package
 * @subpackage
 * @version $Id:$
 */
class Tx_Fluid_ViewHelpers_Format_HtmlEscapeViewHelper extends Tx_Fluid_Core_AbstractViewHelper {
	/**
	 * HTML escape the content of this tag.
	 *
	 * @return string The HTML escaped body.
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function render() {
		$value = $this->renderChildren();
		return htmlspecialchars($value);
	}
}


?>