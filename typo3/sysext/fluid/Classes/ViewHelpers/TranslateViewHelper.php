<?php

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

namespace TYPO3\CMS\Fluid\ViewHelpers;

use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * Translate a key from locallang. The files are loaded from the folder
 * :file:`Resources/Private/Language/`.
 *
 * Examples
 * ========
 *
 * Translate key
 * -------------
 *
 * ::
 *
 *    <f:translate key="key1" />
 *
 * Value of key ``key1`` in the current website language. Alternatively id can
 * be used instead of key::
 *
 *    <f:translate id="key1" />
 *
 * This will output the same as above. If both id and key are set, id will take precedence.
 *
 * Keep HTML tags
 * --------------
 *
 * ::
 *
 *    <f:format.raw><f:translate key="htmlKey" /></f:format.raw>
 *
 * Value of key ``htmlKey`` in the current website language, no :php:`htmlspecialchars()` applied.
 *
 * Translate key from custom locallang file
 * ----------------------------------------
 *
 * ::
 *
 *    <f:translate key="key1" extensionName="MyExt"/>
 *
 * or
 *
 * ::
 *
 *    <f:translate key="LLL:EXT:myext/Resources/Private/Language/locallang.xlf:key1" />
 *
 * Value of key ``key1`` in the current website language.
 *
 * Inline notation with arguments and default value
 * ------------------------------------------------
 *
 * ::
 *
 *    {f:translate(key: 'someKey', arguments: {0: 'dog', 1: 'fox'}, default: 'default value')}
 *
 * Value of key ``someKey`` in the current website language
 * with the given arguments (“dog” and “fox”) assigned for the specified
 * ``%s`` conversions (:php:`sprintf()`) in the language file::
 *
 *    <trans-unit id="someKey" resname="someKey">
 *        <source>Some text about a %s and a %s.</source>
 *    </trans-unit>
 *
 * The output will be "Some text about a dog and a fox".
 *
 * If the key ``someKey`` is not found in the language file, the output is “default value”.
 *
 * Inline notation with extension name
 * -----------------------------------
 *
 * ::
 *
 *    {f:translate(key: 'someKey', extensionName: 'SomeExtensionName')}
 *
 * Value of key ``someKey`` in the current website language.
 * The locallang file of extension "some_extension_name" will be used.
 */
class TranslateViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

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
        $this->registerArgument('id', 'string', 'Translation ID. Same as key.');
        $this->registerArgument('default', 'string', 'If the given locallang key could not be found, this value is used. If this argument is not set, child nodes will be used to render the default');
        $this->registerArgument('arguments', 'array', 'Arguments to be replaced in the resulting string');
        $this->registerArgument('extensionName', 'string', 'UpperCamelCased extension key (for example BlogExample)');
        $this->registerArgument('languageKey', 'string', 'Language key ("dk" for example) or "default" to use for this translation. If this argument is empty, we use the current language');
        $this->registerArgument('alternativeLanguageKeys', 'array', 'Alternative language keys if no translation does exist');
    }

    /**
     * Return array element by key.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @throws Exception
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        $key = $arguments['key'];
        $id = $arguments['id'];
        $default = $arguments['default'];
        $extensionName = $arguments['extensionName'];
        $translateArguments = $arguments['arguments'];

        // Use key if id is empty.
        if ($id === null) {
            $id = $key;
        }

        if ((string)$id === '') {
            throw new Exception('An argument "key" or "id" has to be provided', 1351584844);
        }

        $request = $renderingContext->getRequest();
        $extensionName = $extensionName ?? $request->getControllerExtensionName();
        try {
            $value = static::translate($id, $extensionName, $translateArguments, $arguments['languageKey'], $arguments['alternativeLanguageKeys']);
        } catch (\InvalidArgumentException $e) {
            $value = null;
        }
        if ($value === null) {
            $value = $default ?? $renderChildrenClosure();
            if (!empty($translateArguments)) {
                $value = vsprintf($value, $translateArguments);
            }
        }
        return $value;
    }

    /**
     * Wrapper call to static LocalizationUtility
     *
     * @param string $id Translation Key
     * @param string $extensionName UpperCamelCased extension key (for example BlogExample)
     * @param array $arguments Arguments to be replaced in the resulting string
     * @param string $languageKey Language key to use for this translation
     * @param string[] $alternativeLanguageKeys Alternative language keys if no translation does exist
     *
     * @return string|null
     */
    protected static function translate($id, $extensionName, $arguments, $languageKey, $alternativeLanguageKeys)
    {
        return LocalizationUtility::translate($id, $extensionName, $arguments, $languageKey, $alternativeLanguageKeys);
    }
}
