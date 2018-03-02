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

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\Exception\InvalidVariableException;
use TYPO3Fluid\Fluid\Core\Compiler\TemplateCompiler;
use TYPO3Fluid\Fluid\Core\Parser\SyntaxTree\ViewHelperNode;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

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
 * <f:format.raw><f:translate key="htmlKey" /></f:format.raw>
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
class TranslateViewHelper extends AbstractViewHelper
{
    /**
     * Output is escaped already. We must not escape children, to avoid double encoding.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    /**
     * Initialize arguments.
     *
     * @throws \TYPO3Fluid\Fluid\Core\ViewHelper\Exception
     */
    public function initializeArguments()
    {
        $this->registerArgument('key', 'string', 'Translation Key');
        $this->registerArgument('id', 'string', 'Translation Key compatible to TYPO3 Flow');
        $this->registerArgument('default', 'string', 'If the given locallang key could not be found, this value is used. If this argument is not set, child nodes will be used to render the default');
        $this->registerArgument('arguments', 'array', 'Arguments to be replaced in the resulting string');
        $this->registerArgument('extensionName', 'string', 'UpperCamelCased extension key (for example BlogExample)');
        $this->registerArgument('languageKey', 'string', 'Language key ("dk" for example) or "default" to use for this translation. If this argument is empty, we use the current language');
        $this->registerArgument('alternativeLanguageKeys', 'array', 'Alternative language keys if no translation does exist');
    }

    /**
     * @param string $argumentsName
     * @param string $closureName
     * @param string $initializationPhpCode
     * @param ViewHelperNode $node
     * @param TemplateCompiler $compiler
     * @return string
     */
    public function compile($argumentsName, $closureName, &$initializationPhpCode, ViewHelperNode $node, TemplateCompiler $compiler)
    {
        return sprintf(
            '\\%1$s::translate(%2$s[\'key\'] ?? %2$s[\'id\'], %2$s[\'extensionName\'] ?? $renderingContext->getControllerContext()->getRequest()->getControllerExtensionName(), %2$s[\'arguments\'], %2$s[\'languageKey\'], %2$s[\'alternativeLanguageKeys\']) ?? %2$s[\'default\'] ?? %3$s()',
            LocalizationUtility::class,
            $argumentsName,
            $closureName
        );
    }

    /**
     * @return string|null
     */
    public function render()
    {
        $key = $this->arguments['key'];
        $id = $this->arguments['id'];
        $default = $this->arguments['default'];
        $extensionName = $this->arguments['extensionName'];
        $arguments = $this->arguments['arguments'];
        $languageKey = $this->arguments['languageKey'];
        $alternativeLanguageKeys = $this->arguments['alternativeLanguageKeys'];

        if (empty($id) && empty($key)) {
            throw new InvalidVariableException('Either "key" or "id" must be provided for f:translate', 1351584844);
        }

        // Wrapper including a compatibility layer for TYPO3 Flow Translation
        if ($id === null) {
            $id = $key;
        }

        $request = $this->renderingContext->getControllerContext()->getRequest();
        try {
            $value = $this->translate(
                $key ?? $id,
                $extensionName ?? $request->getControllerExtensionName(),
                $arguments,
                $languageKey,
                $alternativeLanguageKeys
            );
        } catch (\InvalidArgumentException $e) {
            $value = null;
        }
        if ($value === null) {
            $value = $default !== null ? $default : $this->renderChildren();
        }
        return $value;
    }

    /**
     * Wrapper call to static LocalizationUtility
     *
     * @param string $id Translation Key compatible to TYPO3 Flow
     * @param string $extensionName UpperCamelCased extension key (for example BlogExample)
     * @param array $arguments Arguments to be replaced in the resulting string
     * @param string $languageKey Language key to use for this translation
     * @param string[] $alternativeLanguageKeys Alternative language keys if no translation does exist
     *
     * @return string|null
     */
    protected function translate($id, $extensionName, $arguments, $languageKey, $alternativeLanguageKeys)
    {
        return LocalizationUtility::translate($id, $extensionName, $arguments, $languageKey, $alternativeLanguageKeys);
    }
}
