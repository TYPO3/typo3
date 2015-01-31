<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

/*
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

use TYPO3\CMS\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException;
use TYPO3\CMS\Fluid\Core\ViewHelper\Facets\CompilableInterface;

/**
 * Translate a key from locallang. The files are loaded from the folder
 * "Resources/Private/Language/".
 *
 * == Examples ==
 *
 * <code title="Translate key">
 * <f:translate key="key1" />
 * </code>
 * <output>
 * value of key "key1" in the current website language
 * </output>
 *
 * <code title="Keep HTML tags">
 * <f:translate key="htmlKey" htmlEscape="false" />
 * </code>
 * <output>
 * value of key "htmlKey" in the current website language, no htmlspecialchars applied
 * </output>
 *
 * <code title="Translate key from custom locallang file">
 * <f:translate key="LLL:EXT:myext/Resources/Private/Language/locallang.xlf:key1" />
 * </code>
 * <output>
 * value of key "key1" in the current website language
 * </output>
 *
 * <code title="Inline notation with arguments and default value">
 * {f:translate(key: 'argumentsKey', arguments: {0: 'dog', 1: 'fox'}, default: 'default value')}
 * </code>
 * <output>
 * value of key "argumentsKey" in the current website language
 * with "%1" and "%2" are replaced by "dog" and "fox" (printf)
 * if the key is not found, the output is "default value"
 * </output>
 *
 * <code title="Inline notation with extension name">
 * {f:translate(key: 'someKey', extensionName: 'SomeExtensionName')}
 * </code>
 * <output>
 * value of key "someKey" in the current website language
 * the locallang file of extension "some_extension_name" will be used
 * </output>
 *
 * <code title="Translate id as in TYPO3 Flow">
 * <f:translate id="key1" />
 * </code>
 * <output>
 * value of id "key1" in the current website language
 * </output>
 */
class TranslateViewHelper extends AbstractViewHelper implements CompilableInterface {

	/**
	 * Render translation
	 *
	 * @param string $key Translation Key
	 * @param string $id Translation Key compatible to TYPO3 Flow
	 * @param string $default If the given locallang key could not be found, this value is used. If this argument is not set, child nodes will be used to render the default
	 * @param bool $htmlEscape TRUE if the result should be htmlescaped. This won't have an effect for the default value
	 * @param array $arguments Arguments to be replaced in the resulting string
	 * @param string $extensionName UpperCamelCased extension key (for example BlogExample)
	 * @return string The translated key or tag body if key doesn't exist
	 */
	public function render($key = NULL, $id = NULL, $default = NULL, $htmlEscape = NULL, array $arguments = array(), $extensionName = NULL) {
		return self::renderStatic(
			array(
				'key' => $key,
				'id' => $id,
				'default' => $default,
				'htmlEscape' => $htmlEscape,
				'arguments' => $arguments,
				'extensionName' => $extensionName,
			),
			$this->buildRenderChildrenClosure(),
			$this->renderingContext
		);
	}

	/**
	 * Return array element by key.
	 *
	 * @param array $arguments
	 * @param \Closure $renderChildrenClosure
	 * @param RenderingContextInterface $renderingContext
	 * @throws InvalidVariableException
	 * @return string
	 */
	static public function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext) {
		$key = $arguments['key'];
		$id = $arguments['id'];
		$default = $arguments['default'];
		$htmlEscape = $arguments['htmlEscape'];
		$extensionName = $arguments['extensionName'];
		$arguments = $arguments['arguments'];

		// Wrapper including a compatibility layer for TYPO3 Flow Translation
		if ($id === NULL) {
			$id = $key;
		}

		if ((string)$id === '') {
			throw new InvalidVariableException('An argument "key" or "id" has to be provided', 1351584844);
		}

		$request = $renderingContext->getControllerContext()->getRequest();
		$extensionName = $extensionName === NULL ? $request->getControllerExtensionName() : $extensionName;
		$value = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($id, $extensionName, $arguments);
		if ($value === NULL) {
			$value = $default !== NULL ? $default : $renderChildrenClosure();
			if (!empty($arguments)) {
				$value = vsprintf($value, $arguments);
			}
		} elseif ($htmlEscape) {
			$value = htmlspecialchars($value);
		}
		return $value;
	}

}
