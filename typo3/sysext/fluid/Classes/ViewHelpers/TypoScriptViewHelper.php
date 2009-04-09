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
 * This class is a TypoScript view helper for the Fluid templating engine.
 *
 * @package TYPO3
 * @subpackage Fluid
 * @version $Id:$
 */
class Tx_Fluid_ViewHelpers_TypoScriptViewHelper extends Tx_Fluid_Core_AbstractViewHelper {
	/**
	 * The constructor.
	 */
	public function __construct(array $arguments = array()) {
	}

	/**
	 * Initializes the arguments of this view helper.
	 *
	 * @author Niels Pardon <mail@niels-pardon.de>
	 */
	public function initializeArguments() {
		$this->registerArgument(
			'path', 'string', 'The path of the TypoScript object to render.', true
		);
	}

	/**
	 * Renders the TypoScript object in the given TypoScript setup path.
	 *
	 * @param string the TypoScript setup path of the TypoScript object to render
	 *
	 * @return string the content of the rendered TypoScript object
	 *
	 * @author Niels Pardon <mail@niels-pardon.de>
	 */
	public function render($path) {
		$data = $GLOBALS['TSFE']->tmpl->setup;

		$pathSegments = t3lib_div::trimExplode('.', $path);
		$lastSegment = array_pop($pathSegments);

		foreach ($pathSegments as $segment) {
			if (!array_key_exists($segment . '.', $data)) {
				$data = array();
				break;
			}

			$data =& $data[$segment . '.'];
		}

		return $GLOBALS['TSFE']->cObj->cObjGetSingle(
			$data[$lastSegment], $data[$lastSegment . '.']
		);
	}
}


?>