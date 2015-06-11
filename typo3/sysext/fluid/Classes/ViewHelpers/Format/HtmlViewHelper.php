<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Format;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Renders a string by passing it to a TYPO3 parseFunc.
 * You can either specify a path to the TypoScript setting or set the parseFunc options directly.
 * By default lib.parseFunc_RTE is used to parse the string.
 *
 * == Examples ==
 *
 * <code title="Default parameters">
 * <f:format.html>foo <b>bar</b>. Some <LINK 1>link</LINK>.</f:format.html>
 * </code>
 * <output>
 * <p class="bodytext">foo <b>bar</b>. Some <a href="index.php?id=1" >link</a>.</p>
 * (depending on your TYPO3 setup)
 * </output>
 *
 * <code title="Custom parseFunc">
 * <f:format.html parseFuncTSPath="lib.parseFunc">foo <b>bar</b>. Some <LINK 1>link</LINK>.</f:format.html>
 * </code>
 * <output>
 * foo <b>bar</b>. Some <a href="index.php?id=1" >link</a>.
 * </output>
 *
 * <code title="Inline notation">
 * {someText -> f:format.html(parseFuncTSPath: 'lib.parseFunc')}
 * </code>
 * <output>
 * foo <b>bar</b>. Some <a href="index.php?id=1" >link</a>.
 * </output>
 *
 * @see http://typo3.org/documentation/document-library/references/doc_core_tsref/4.2.0/view/1/5/#id4198758
 */
class HtmlViewHelper extends AbstractViewHelper implements CompilableInterface {

	/**
	 * @var \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController contains a backup of the current $GLOBALS['TSFE'] if used in BE mode
	 */
	static protected $tsfeBackup;

	/**
	 * If the escaping interceptor should be disabled inside this ViewHelper, then set this value to FALSE.
	 * This is internal and NO part of the API. It is very likely to change.
	 *
	 * @var bool
	 * @internal
	 */
	protected $escapingInterceptorEnabled = FALSE;

	/**
	 * @param string $parseFuncTSPath path to TypoScript parseFunc setup.
	 * @return string the parsed string.
	 */
	public function render($parseFuncTSPath = 'lib.parseFunc_RTE') {
		return static::renderStatic(
			array(
				'parseFuncTSPath' => $parseFuncTSPath,
			),
			$this->buildRenderChildrenClosure(),
			$this->renderingContext
		);
	}

	/**
	 * @param array $arguments
	 * @param callable $renderChildrenClosure
	 * @param RenderingContextInterface $renderingContext
	 *
	 * @return string
	 */
	static public function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext) {
		$parseFuncTSPath = $arguments['parseFuncTSPath'];
		if (TYPO3_MODE === 'BE') {
			self::simulateFrontendEnvironment();
		}
		$value = $renderChildrenClosure();
		$contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
		$content = $contentObject->parseFunc($value, array(), '< ' . $parseFuncTSPath);
		if (TYPO3_MODE === 'BE') {
			self::resetFrontendEnvironment();
		}
		return $content;
	}

	/**
	 * Copies the specified parseFunc configuration to $GLOBALS['TSFE']->tmpl->setup in Backend mode
	 * This somewhat hacky work around is currently needed because the parseFunc() function of \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer relies on those variables to be set
	 *
	 * @return void
	 */
	static protected function simulateFrontendEnvironment() {
		self::$tsfeBackup = isset($GLOBALS['TSFE']) ? $GLOBALS['TSFE'] : NULL;
		$GLOBALS['TSFE'] = new \stdClass();
		$GLOBALS['TSFE']->tmpl = new \stdClass();
		$objectManager = GeneralUtility::makeInstance(ObjectManager::class);
		$configurationManager = $objectManager->get(ConfigurationManagerInterface::class);
		$GLOBALS['TSFE']->tmpl->setup = $configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
	}

	/**
	 * Resets $GLOBALS['TSFE'] if it was previously changed by simulateFrontendEnvironment()
	 *
	 * @return void
	 * @see simulateFrontendEnvironment()
	 */
	static protected function resetFrontendEnvironment() {
		$GLOBALS['TSFE'] = self::$tsfeBackup;
	}

}
