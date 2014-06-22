<?php
namespace TYPO3\CMS\Frontend\ContentObject;

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
 * Contains TEXT class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class TextContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * Rendering the cObject, TEXT
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
		if (!is_array($conf)) {
			return '';
		}
		$content = '';
		if (isset($conf['value'])) {
			$content = $conf['value'];
			unset($conf['value']);
		}
		if (isset($conf['value.'])) {
			$content = $this->cObj->stdWrap($content, $conf['value.']);
			unset($conf['value.']);
		}
		if (count($conf)) {
			$content = $this->cObj->stdWrap($content, $conf);
		}
		return $content;
	}

}
