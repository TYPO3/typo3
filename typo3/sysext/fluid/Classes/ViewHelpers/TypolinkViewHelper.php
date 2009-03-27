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
 *
 *
 * @package
 * @subpackage
 * @version $Id:$
 */
class Tx_Fluid_ViewHelpers_TypolinkViewHelper extends Tx_Fluid_Core_TagBasedViewHelper {
	/**
	 * an instance of tslib_cObj
	 *
	 * @var	tslib_cObj
	 */
	protected $contentObject = null;

	/**
	 * constructor for class tx_community_viewhelper_Link
	 */
	public function __construct(array $arguments = array()) {
		if (is_null($this->contentObject)) {
			$this->contentObject = t3lib_div::makeInstance('tslib_cObj');
		}
	}
	/**
	 * Arguments initialization
	 *
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function initializeArguments() {
		$this->registerUniversalTagAttributes();
		$this->registerTagAttribute('target', 'Target of link', FALSE);
		$this->registerTagAttribute('rel', 'Specifies the relationship between the current document and the linked document', FALSE);
	}

	/**
	 * Render.
	 *
	 * @param string $page Target page. See TypoLink destination
	 * @param string $anchor Anchor
	 * @param boolean $useCacheHash If true, cHash is appended to URL
	 * @param array $arguments Arguments
	 * @return string Rendered string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function render($page = '', $anchor = '', $useCacheHash = TRUE, $arguments = array()) {

		if ($page === '') {
			$page = $GLOBALS['TSFE']->id;
		}

		$typolinkConfiguration = array(
			'parameter' => $page,
			'ATagParams' => $this->renderTagAttributes()
		);

		if (count($arguments)) {
			 $typolinkConfiguration['additionalParams'] = '&' . http_build_query($arguments);
		}
		if ($anchor) {
			$typolinkConfiguration['section'] = $anchor;
		}
		if ($useCacheHash) {
			$typolinkConfiguration['useCacheHash'] = 1;
		} else {
			$typolinkConfiguration['useCacheHash'] = 0;
		}

		$link = $this->contentObject->typoLink(
			$this->renderChildren(),
			$typolinkConfiguration
		);

		return $link;
	}
}


?>