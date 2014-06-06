<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Link;

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
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * A ViewHelper to create links from fields supported by the link wizard
 *
 * == Example ==
 *
 * {link} contains "19 _blank - "testtitle with whitespace" &X=y"
 *
 * <code title="minimal usage">
 * <f:link.typolink parameter="{link}">
 * Linktext
 * </f:link.typolink>
 * <output>
 * <a href="index.php?id=19&X=y" title="testtitle with whitespace" target="_blank">
 * Linktext
 * </a>
 * </output>
 * </code>
 *
 * <code title="Full parameter usage">
 * <f:link.typolink parameter="{link}" target="_blank" class="ico-class" title="some title" additionalParams="&u=b" additionalAttributes="{type:'button'}">
 * Linktext
 * </f:link.typolink>
 * </code>
 * <output>
 * <a href="index.php?id=19&X=y&u=b" title="some title" target="_blank" class="ico-class" type="button">
 * Linktext
 * </a>
 * </output>
 *
 */
class TypolinkViewHelper extends AbstractViewHelper {

	/**
	 * Render
	 *
	 * @param string $parameter stdWrap.typolink style parameter string
	 * @param string $target
	 * @param string $class
	 * @param string $title
	 * @param string $additionalParams
	 * @param array $additionalAttributes
	 *
	 * @return string
	 */
	public function render($parameter, $target = '', $class = '', $title = '', $additionalParams = '', $additionalAttributes = array()) {
		// Merge the $parameter with other arguments
		$typolinkParameter = $this->createTypolinkParameterArrayFromArguments($parameter, $target, $class, $title, $additionalParams);

		// array(param1 -> value1, param2 -> value2) --> "param1=value1 param2=>value2" for typolink.ATagParams
		$extraAttributes = array();
		foreach ($additionalAttributes as $attributeName => $attributeValue) {
			$extraAttributes[] = $attributeName . '="' . htmlspecialchars($attributeValue) . '"';
		}
		$aTagParams = implode(' ', $extraAttributes);

		// If no link has to be rendered, the inner content will be returned as such
		$content = $this->renderChildren();

		if ($parameter) {
			/** @var ContentObjectRenderer $contentObject */
			$contentObject = GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer');
			$contentObject->start(array(), '');
			$content = $contentObject->stdWrap(
				$content,
				array(
					'typolink.' => array(
						'parameter' => implode(' ', $typolinkParameter),
						'ATagParams' => $aTagParams,
					)
				)
			);
		}

		return $content;
	}

	/**
	 * Transforms ViewHelper arguments to typo3link.parameters.typoscript option as array.
	 *
	 * @param string $parameter Example: 19 _blank - "testtitle with whitespace" &X=y
	 * @param string $target
	 * @param string $class
	 * @param string $title
	 * @param string $additionalParams
	 *
	 * @return array Final merged typolink.parameter as array to be imploded with empty string later
	 */
	protected function createTypolinkParameterArrayFromArguments($parameter, $target = '', $class = '', $title = '', $additionalParams = '') {
		// Explode $parameter by whitespace and remove any " around resulting array values
		$parameterArray = GeneralUtility::unQuoteFilenames($parameter, TRUE);

		if (empty($parameterArray)) {
			return array();
		}

		// Extend to 4 elements
		$typolinkConfiguration = array_pad($parameterArray, 4, '-');

		// Override target if given in target argument
		if ($target) {
			$typolinkConfiguration[1] = $target;
		}

		// Combine classes if given in both "parameter" string and "class" argument
		if ($class) {
			$typolinkConfiguration[2] = $typolinkConfiguration[2] !== '-' ? $typolinkConfiguration[2] . ' ' : '';
			$typolinkConfiguration[2] .= $class;
		}

		// Override title if given in title argument
		if ($title) {
			$typolinkConfiguration[3] = $title;
		}

		// Combine additionalParams
		if ($additionalParams) {
			$typolinkConfiguration[4] .= $additionalParams;
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