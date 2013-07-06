<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Philipp Gampe <philipp.gampe@typo3.org>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * This ViewHelper renders data via the getText from the TypoScript configuration.
 *
 * = Examples =
 *
 * <code title="Render getText">
 * <f:getText>page:title</f:getText>
 * </code>
 * <output>
 * The page title of the current page.
 * </output>
 *
 * <code title="Render getText with data argument">
 * <f:getText data="getEnv:HTTP_HOST" />
 * </code>
 * <output>
 * The hostname used in the current request.
 * </output>
 *
 * <code title="inline notation">
 * {article -> f:getText}
 * </code>
 * <output>
 * parsed output from getText
 * </output>
 */
class GetTextViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Disable the escaping interceptor because otherwise the child nodes would be escaped before this view helper
	 * can decode the text's entities.
	 *
	 * @var boolean
	 */
	protected $escapingInterceptorEnabled = FALSE;

	/**
	 * @var array
	 */
	protected $typoScriptSetup;

	/**
	 * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController contains a backup of the current $GLOBALS['TSFE'] if used in BE mode
	 */
	protected $tsfeBackup;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
		$this->typoScriptSetup = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
	}

	/**
	 * Renders the TypoScript object in the given TypoScript setup path.
	 *
	 * @param string $data The getText values to render
	 * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception
	 * @return string The content returned by getText
	 */
	public function render($data = NULL) {
		if (TYPO3_MODE === 'BE') {
			$this->simulateFrontendEnvironment();
		}
		if ($data === NULL) {
			$data = $this->renderChildren();
		}
		if (is_object($data)) {
			$data = \TYPO3\CMS\Extbase\Reflection\ObjectAccess::getGettableProperties($data);
		}
		if (is_string($data) || is_numeric($data)) {
			$data = (string) ($data);
		} else {
			throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception('Value must be a string.', 1373150811);
		}
		$contentObject = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
		$contentObject->start('');
		$setup = $this->typoScriptSetup;
		$content = $contentObject->getData($data, NULL);
		if (TYPO3_MODE === 'BE') {
			$this->resetFrontendEnvironment();
		}
		return $content;
	}

	/**
	 * Sets the $TSFE->cObjectDepthCounter in Backend mode
	 * This somewhat hacky work around is currently needed because the cObjGetSingle() function of \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer relies on this setting
	 *
	 * @return void
	 */
	protected function simulateFrontendEnvironment() {
		$this->tsfeBackup = isset($GLOBALS['TSFE']) ? $GLOBALS['TSFE'] : NULL;
		$GLOBALS['TSFE'] = new \stdClass();
		$GLOBALS['TSFE']->cObjectDepthCounter = 100;
	}

	/**
	 * Resets $GLOBALS['TSFE'] if it was previously changed by simulateFrontendEnvironment()
	 *
	 * @return void
	 * @see simulateFrontendEnvironment()
	 */
	protected function resetFrontendEnvironment() {
		$GLOBALS['TSFE'] = $this->tsfeBackup;
	}
}

?>