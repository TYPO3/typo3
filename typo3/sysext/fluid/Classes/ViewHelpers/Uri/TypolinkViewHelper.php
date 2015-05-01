<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Uri;

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
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * A ViewHelper to create uris from fields supported by the link wizard
 *
 * == Example ==
 *
 * {link} contains "19 - - - &X=y"
 * Please note that due to the nature of typolink you have to provide a
 * full set of parameters if you use the parameter only. Target, class
 * and title will be discarded.
 *
 * <code title="minimal usage">
 * <f:uri.typolink parameter="{link}" />
 * <output>
 * index.php?id=19&X=y
 * </output>
 * </code>
 *
 * <code title="Full parameter usage">
 * <f:uri.typolink parameter="{link}" additionalParams="&u=b" />
 * </code>
 * <output>
 * index.php?id=19&X=y&u=b
 * </output>
 *
 */
class TypolinkViewHelper extends AbstractViewHelper implements CompilableInterface {

	/**
	 * Render
	 *
	 * @param string $parameter stdWrap.typolink style parameter string
	 * @param string $additionalParams
	 *
	 * @return string
	 */
	public function render($parameter, $additionalParams = '') {
		return self::renderStatic(
			array(
				'parameter' => $parameter,
				'additionalParams' => $additionalParams
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
		$parameter = $arguments['parameter'];
		$additionalParams = $arguments['additionalParams'];

		// Merge the $parameter with other arguments
		$typolinkParameter = self::createTypolinkParameterArrayFromArguments($parameter, $additionalParams);

		$content = '';

		if ($parameter) {
			$contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
			$content = $contentObject->typoLink_URL(
				array(
					'parameter' => implode(' ', $typolinkParameter),
				)
			);
		}

		return $content;
	}

	/**
	 * Transforms ViewHelper arguments to typo3link.parameters.typoscript option as array.
	 *
	 * @param string $parameter Example: 19 _blank - "testtitle with whitespace" &X=y
	 * @param string $additionalParameters
	 *
	 * @return array Final merged typolink.parameter as array to be imploded with empty string later
	 */
	static protected function createTypolinkParameterArrayFromArguments($parameter, $additionalParameters = '') {
		// Explode $parameter by whitespace and remove any " around resulting array values
		$parameterArray = GeneralUtility::unQuoteFilenames($parameter, TRUE);

		if (empty($parameterArray)) {
			return array();
		}

		// Extend to 4 elements
		$typolinkConfiguration = array_pad($parameterArray, 4, '-');

		// Combine additionalParameters
		if ($additionalParameters) {
			$typolinkConfiguration[4] .= $additionalParameters;
		}

		// Unset unused parameters again from the end, wrap all given values with "
		$reverseSortedParameters = array_reverse($typolinkConfiguration, TRUE);
		$aValueWasSet = FALSE;
		foreach ($reverseSortedParameters as $position => $value) {
			if ($value === '-' && !$aValueWasSet) {
				unset($typolinkConfiguration[$position]);
			} else {
				$aValueWasSet = TRUE;
				if ($value !== '-') {
					$typolinkConfiguration[$position] = '"' . $value . '"';
				}
			}
		}
		return $typolinkConfiguration;
	}
}
