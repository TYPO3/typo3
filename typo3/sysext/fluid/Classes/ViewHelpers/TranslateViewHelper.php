<?php
namespace TYPO3\CMS\Fluid\ViewHelpers;

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
 * <f:translate key="LLL:EXT:myext/Resources/Private/Language/locallang.xml:key1" />
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
class TranslateViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Initializes arguments for Translate ViewHelper
	 *
	 * @return void
	 */
	public function initializeArguments() {
		/** @deprecated since 6.0 and will be removed in 6.2 */
		$this->registerArgument('key', 'string', 'Translation Key');
		$this->registerArgument('id', 'string', 'Translation Key compatible to TYPO3 Flow');
		$this->registerArgument('default', 'string', 'if the given locallang key could not be found, this value is used. If this argument is not set, child nodes will be used to render the default');
		$this->registerArgument('htmlEscape', 'boolean', 'TRUE if the result should be htmlescaped. This won\'t have an effect for the default value');
		$this->registerArgument('arguments', 'array', 'Arguments to be replaced in the resulting string');
		$this->registerArgument('extensionName', 'string', 'UpperCamelCased extension key (for example BlogExample)');
	}

	/**
	 * Wrapper function including a compatibility layer for TYPO3 Flow Translation
	 *
	 * @throws \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException
	 *
	 * @return string The translated key or tag body if key doesn't exist
	 */
	public function render() {
		$id = $this->hasArgument('id') ? $this->arguments['id'] : $this->arguments['key'];

		if (strlen($id) > 0) {
			return $this->renderTranslation($id);
		} else {
			throw new \TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException('An argument "key" or "id" has to be provided', 1351584844);
		}
	}

	/**
	 * Translate a given key or use the tag body as default.
	 *
	 * @param string $id The locallang id
	 * @return string The translated key or tag body if key doesn't exist
	 */
	protected function renderTranslation($id) {
		$request = $this->controllerContext->getRequest();
		$extensionName = $this->arguments['extensionName'] === NULL ? $request->getControllerExtensionName() : $this->arguments['extensionName'];
		$value = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($id, $extensionName, $this->arguments['arguments']);
		if ($value === NULL) {
			$value = $this->arguments['default'] !== NULL ? $this->arguments['default'] : $this->renderChildren();
			if (is_array($this->arguments['arguments'])) {
				$value = vsprintf($value, $this->arguments['arguments']);
			}
		} elseif ($this->arguments['htmlEscape']) {
			$value = htmlspecialchars($value);
		}
		return $value;
	}
}

?>
