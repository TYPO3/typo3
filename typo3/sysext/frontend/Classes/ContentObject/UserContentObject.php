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
 * Contains USER class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class UserContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * Rendering the cObject, USER
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
		if (!is_array($conf) || empty($conf)) {
			$GLOBALS['TT']->setTSlogMessage('USER without configuration.', 2);
			return '';
		}
		$content = '';
		if ($this->cObj->getUserObjectType() === FALSE) {
			// Come here only if we are not called from $TSFE->INTincScript_process()!
			$this->cObj->setUserObjectType(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::OBJECTTYPE_USER);
		}
		$this->cObj->includeLibs($conf);
		$tempContent = $this->cObj->callUserFunction($conf['userFunc'], $conf, '');
		if ($this->cObj->doConvertToUserIntObject) {
			$this->cObj->doConvertToUserIntObject = FALSE;
			$content = $this->cObj->USER($conf, 'INT');
		} else {
			$content .= $tempContent;
		}
		if (isset($conf['stdWrap.'])) {
			$content = $this->cObj->stdWrap($content, $conf['stdWrap.']);
		}
		$this->cObj->setUserObjectType(FALSE);
		return $content;
	}

}
