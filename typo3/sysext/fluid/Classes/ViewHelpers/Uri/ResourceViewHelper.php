<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Uri;

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
 * A view helper for creating URIs to resources.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <link href="{f:uri.resource(path:'css/stylesheet.css')}" rel="stylesheet" />
 * </code>
 * <output>
 * <link href="Resources/Packages/MyPackage/stylesheet.css" rel="stylesheet" />
 * (depending on current package)
 * </output>
 */
class ResourceViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Render the URI to the resource. The filename is used from child content.
	 *
	 * @param string $path The path and filename of the resource (relative to Public resource directory of the extension).
	 * @param string $extensionName Target extension name. If not set, the current extension name will be used
	 * @param bool $absolute If set, an absolute URI is rendered
	 * @return string The URI to the resource
	 * @api
	 */
	public function render($path, $extensionName = NULL, $absolute = FALSE) {
		if ($extensionName === NULL) {
			$extensionName = $this->controllerContext->getRequest()->getControllerExtensionName();
		}
		$uri = 'EXT:' . \TYPO3\CMS\Core\Utility\GeneralUtility::camelCaseToLowerCaseUnderscored($extensionName) . '/Resources/Public/' . $path;
		$uri = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($uri);
		$uri = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($uri);
		if (TYPO3_MODE === 'BE' && $absolute === FALSE && $uri !== FALSE) {
			$uri = '../' . $uri;
		}
		if ($absolute === TRUE) {
			$uri = $this->controllerContext->getRequest()->getBaseURI() . $uri;
		}
		return $uri;
	}

}
