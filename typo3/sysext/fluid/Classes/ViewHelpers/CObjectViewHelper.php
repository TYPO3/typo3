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
 * @version $Id$
 */
class Tx_Fluid_ViewHelpers_CObjectViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @var tslib_cObj
	 */
	protected $contentObject;

	/**
	 * Constructor. Used to create an instance of tslib_cObj used by the render() method.
	 *
	 * @param tslib_cObj $contentObject injector for tslib_cObj (optional)
	 * @param array $typoscriptSetup global TypoScript setup (optional)
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function __construct($contentObject = NULL, array $typoscriptSetup = NULL) {
		$this->contentObject = $contentObject !== NULL ? $contentObject : t3lib_div::makeInstance('tslib_cObj');
		$this->typoscriptSetup = $typoscriptSetup !== NULL ? $typoscriptSetup : $GLOBALS['TSFE']->tmpl->setup;
	}

	/**
	 * Renders the TypoScript object in the given TypoScript setup path.
	 *
	 * @param string $typoscriptObjectPath the TypoScript setup path of the TypoScript object to render
	 * @param string $currentValueKey
	 * @return string the content of the rendered TypoScript object
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Niels Pardon <mail@niels-pardon.de>
	 */
	public function render($typoscriptObjectPath, $currentValueKey = NULL) {
		$data = $this->renderChildren();
		$currentValue = NULL;
		if (is_object($data)) {
			$data = Tx_Extbase_Reflection_ObjectAccess::getAccessibleProperties($data);
		} elseif (is_string($data)) {
			$currentValue = $data;
			$data = array($data);
		}
		$this->contentObject->start($data);
		if ($currentValue !== NULL) {
			$this->contentObject->setCurrentVal($currentValue);
		} elseif ($currentValueKey !== NULL && isset($data[$currentValueKey])) {
			$this->contentObject->setCurrentVal($data[$currentValueKey]);
		}

		$pathSegments = t3lib_div::trimExplode('.', $typoscriptObjectPath);
		$lastSegment = array_pop($pathSegments);
		$setup = $this->typoscriptSetup;
		foreach ($pathSegments as $segment) {
			if (!array_key_exists($segment . '.', $setup)) {
				return 'TypoScript object path "' . htmlspecialchars($typoscriptObjectPath) . '" does not exist';
			}
			$setup = $setup[$segment . '.'];
		}
		return $this->contentObject->cObjGetSingle($setup[$lastSegment], $setup[$lastSegment . '.']);
	}
}

?>