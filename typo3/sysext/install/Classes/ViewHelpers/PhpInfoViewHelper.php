<?php
namespace TYPO3\CMS\Install\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Patrick Broens <patrick@patrickbroens.nl>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Utility class for phpinfo()
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class PhpInfoViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Disable the escaping interceptor because otherwise the child nodes would be escaped before this view helper
	 * can decode the text's entities.
	 *
	 * @var boolean
	 */
	protected $escapingInterceptorEnabled = FALSE;

	/**
	 * Render PHP info
	 *
	 * @return string
	 */
	public function render() {
		return $this->removeAllHtmlOutsideBody(
			$this->changeHtmlToHtml5(
				$this->getPhpInfo()
			)
		);
	}

	/**
	 * Get information about PHP's configuration
	 *
	 * @return string HTML page with the configuration options
	 */
	public function getPhpInfo() {
		ob_start();
		phpinfo();

		return ob_get_clean();
	}

	/**
	 * Remove all HTML outside the body tag from HTML string
	 *
	 * @param string $html Complete HTML markup for page
	 * @return string Content of the body tag
	 */
	protected function removeAllHtmlOutsideBody($html) {
		// Delete anything outside of the body tag and the body tag itself
		$html = preg_replace('/^.*?<body.*?>/is', '', $html);
		$html = preg_replace('/<\/body>.*?$/is', '', $html);

		return $html;
	}

	/**
	 * Change HTML markup to HTML5
	 *
	 * @param string $html HTML markup to be cleaned
	 * @return string
	 */
	protected function changeHtmlToHtml5($html) {
		// Delete obsolete attributes
		$html = preg_replace('#\s(cellpadding|border|width)="[^"]+"#', '', $html);

		// Replace font tag with span
		return str_replace(array('<font', '</font>'), array('<span', '</span>'), $html);
	}
}