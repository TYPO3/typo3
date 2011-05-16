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
 * This ViewHelper renders CObjects from the global TypoScript configuration.
 *
 * = Examples =
 *
 * <code title="Render lib object">
 * <f:cObject typoscriptObjectPath="lib.someLibObject" />
 * </code>
 * <output>
 * // rendered lib.someLibObject
 * </output>
 *
 * <code title="Specify cObject data & current value">
 * <f:cObject typoscriptObjectPath="lib.customHeader" data="{article}" current="{article.title}" />
 * </code>
 * <output>
 * // rendered lib.customHeader. data and current value will be available in TypoScript
 * </output>
 *
 * <code title="inline notation">
 * {article -> f:cObject(typoscriptObjectPath: 'lib.customHeader')}
 * </code>
 * <output>
 * // rendered lib.customHeader. data will be available in TypoScript
 * </output>
 *
 */
class Tx_Fluid_ViewHelpers_CObjectViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @var tslib_cObj
	 */
	protected $contentObject;

	/**
	 * @var array
	 */
	protected $typoScriptSetup;

	/**
	 * @var	t3lib_fe contains a backup of the current $GLOBALS['TSFE'] if used in BE mode
	 */
	protected $tsfeBackup;

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
		$this->contentObject = $this->configurationManager->getContentObject();
		$this->typoScriptSetup = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
	}

	/**
	 * Renders the TypoScript object in the given TypoScript setup path.
	 *
	 * @param string $typoscriptObjectPath the TypoScript setup path of the TypoScript object to render
	 * @param mixed $data the data to be used for rendering the cObject. Can be an object, array or string. If this argument is not set, child nodes will be used
	 * @param string $currentValueKey
	 * @return string the content of the rendered TypoScript object
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @author Niels Pardon <mail@niels-pardon.de>
	 */
	public function render($typoscriptObjectPath, $data = NULL, $currentValueKey = NULL) {
		if (TYPO3_MODE === 'BE') {
			$this->simulateFrontendEnvironment();
		}

		if ($data === NULL) {
			$data = $this->renderChildren();
		}
		$currentValue = NULL;
		if (is_object($data)) {
			$data = Tx_Extbase_Reflection_ObjectAccess::getGettablePropertyNames($data);
		} elseif (is_string($data) || is_numeric($data)) {
			$currentValue = (string) $data;
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
		$setup = $this->typoScriptSetup;
		foreach ($pathSegments as $segment) {
			if (!array_key_exists($segment . '.', $setup)) {
				throw new Tx_Fluid_Core_ViewHelper_Exception('TypoScript object path "' . htmlspecialchars($typoscriptObjectPath) . '" does not exist' , 1253191023);
			}
			$setup = $setup[$segment . '.'];
		}
		$content = $this->contentObject->cObjGetSingle($setup[$lastSegment], $setup[$lastSegment . '.']);

		if (TYPO3_MODE === 'BE') {
			$this->resetFrontendEnvironment();
		}

		return $content;
	}

	/**
	 * Sets the $TSFE->cObjectDepthCounter in Backend mode
	 * This somewhat hacky work around is currently needed because the cObjGetSingle() function of tslib_cObj relies on this setting
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	protected function simulateFrontendEnvironment() {
		$this->tsfeBackup = isset($GLOBALS['TSFE']) ? $GLOBALS['TSFE'] : NULL;
		$GLOBALS['TSFE'] = new stdClass();
		$GLOBALS['TSFE']->cObjectDepthCounter = 100;
	}

	/**
	 * Resets $GLOBALS['TSFE'] if it was previously changed by simulateFrontendEnvironment()
	 *
	 * @return void
	 * @author Bastian Waidelich <bastian@typo3.org>
	 * @see simulateFrontendEnvironment()
	 */
	protected function resetFrontendEnvironment() {
		$GLOBALS['TSFE'] = $this->tsfeBackup;
	}
}

?>
