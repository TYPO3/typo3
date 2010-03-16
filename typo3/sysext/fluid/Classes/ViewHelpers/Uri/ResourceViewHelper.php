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
 * A view helper for creating URIs to resources.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <link href="{f:uri.resource(path:'css/stylesheet.css')}" rel="stylesheet" />
 * </code>
 *
 * Output:
 * <link href="Resources/Packages/MyPackage/stylesheet.css" rel="stylesheet" />
 * (depending on current package)
 *
 * @version $Id: ResourceViewHelper.php 1734 2009-11-25 21:53:57Z stucki $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Uri_ResourceViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * Render the URI to the resource. The filename is used from child content.
	 *
	 * @param string $path The path and filename of the resource (relative to Public resource directory of the extension).
	 * @param string $extensionName Target extension name. If not set, the current extension name will be used
	 * @param boolean $absolute If set, an absolute URI is rendered
	 * @return string The URI to the resource
	 * @api
	 */
	public function render($path, $extensionName = NULL, $absolute = FALSE) {
		if ($extensionName === NULL) {
			$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		}
		$uri = 'EXT:' . t3lib_div::camelCaseToLowerCaseUnderscored($extensionName) . '/Resources/Public/' . $path;
		$uri = t3lib_div::getFileAbsFileName($uri);
		$uri = substr($uri, strlen(PATH_site));

		if (TYPO3_MODE === 'BE' && $absolute === FALSE) {
			$uri = '../' . $uri;
		}

		if ($absolute === TRUE) {
			$uri = $this->controllerContext->getRequest()->getBaseURI() . $uri;
		}

		return $uri;
	}
}
?>
