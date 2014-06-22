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
 * Contains USER_INT class object.
 *
 * @author Xavier Perseguers <typo3@perseguers.ch>
 * @author Steffen Kamper <steffen@typo3.org>
 */
class UserInternalContentObject extends \TYPO3\CMS\Frontend\ContentObject\AbstractContentObject {

	/**
	 * Rendering the cObject, USER_INT
	 *
	 * @param array $conf Array of TypoScript properties
	 * @return string Output
	 */
	public function render($conf = array()) {
		$this->cObj->setUserObjectType(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::OBJECTTYPE_USER_INT);
		$substKey = 'INT_SCRIPT.' . $GLOBALS['TSFE']->uniqueHash();
		$content = '<!--' . $substKey . '-->';
		$includeLibs = isset($conf['includeLibs.']) ? $this->cObj->stdWrap($conf['includeLibs'], $conf['includeLibs.']) : $conf['includeLibs'];
		$GLOBALS['TSFE']->config['INTincScript'][$substKey] = array(
			'file' => $includeLibs,
			'conf' => $conf,
			'cObj' => serialize($this->cObj),
			'type' => 'FUNC'
		);
		$this->cObj->setUserObjectType(FALSE);
		return $content;
	}

}
