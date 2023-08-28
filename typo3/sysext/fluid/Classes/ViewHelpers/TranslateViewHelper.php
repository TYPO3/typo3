<?php

declare(strict_types=1);

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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\Localization\Locales;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface as ExtbaseRequestInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Fluid\Core\Rendering\RenderingContext;
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
final class TranslateViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Output is escaped already. We must not escape children, to avoid double encoding.
     *
     * @var bool
     */
    protected $escapeChildren = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('key', 'string', 'Translation Key');
        $this->registerArgument('id', 'string', 'Translation ID. Same as key.');
        $this->registerArgument('default', 'string', 'If the given locallang key could not be found, this value is used. If this argument is not set, child nodes will be used to render the default');
        $this->registerArgument('arguments', 'array', 'Arguments to be replaced in the resulting string');
        $this->registerArgument('extensionName', 'string', 'UpperCamelCased extension key (for example BlogExample)');
        $this->registerArgument('languageKey', 'string', 'Language key ("da" for example) or "default" to use. Also a Locale object is possible. If empty, use current locale from the request.');
        // @deprecated will be removed in TYPO3 v13.0. Deprecation is triggered in LocalizationUtility
        $this->registerArgument('alternativeLanguageKeys', 'array', 'Alternative language keys if no translation does exist. Ignored in non-extbase context. Deprecated, will be removed in TYPO3 v13.0');
    }

    /**
     * Return array element by key.
     *
     * @throws Exception
     * @throws \RuntimeException
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext): string
    {
        $key = $arguments['key'];
        $id = $arguments['id'];
        $default = (string)($arguments['default'] ?? $renderChildrenClosure() ?? '');
        $extensionName = $arguments['extensionName'];
        $translateArguments = $arguments['arguments'];

        // Use key if id is empty.
        if ($id === null) {
            $id = $key;
        }

        $id = (string)$id;
        if ($id === '') {
            throw new Exception('An argument "key" or "id" has to be provided', 1351584844);
        }

        $request = null;
        if ($renderingContext instanceof RenderingContext) {
            $request = $renderingContext->getRequest();
        }

        if (empty($extensionName)) {
            if ($request instanceof ExtbaseRequestInterface) {
                $extensionName = $request->getControllerExtensionName();
            } elseif (str_starts_with($id, 'LLL:EXT:')) {
                $extensionName = substr($id, 8, strpos($id, '/', 8) - 8);
            } elseif ($default) {
                return self::handleDefaultValue($default, $translateArguments);
            } else {
                // Throw exception in case neither an extension key nor a extbase request
                // are given, since the "short key" shouldn't be considered as a label.
                throw new \RuntimeException(
                    'ViewHelper f:translate in non-extbase context needs attribute "extensionName" to resolve'
                    . ' key="' . $id . '" without path. Either set attribute "extensionName" together with the short'
                    . ' key "yourKey" to result in a lookup "LLL:EXT:your_extension/Resources/Private/Language/locallang.xlf:yourKey",'
                    . ' or (better) use a full LLL reference like key="LLL:EXT:your_extension/Resources/Private/Language/yourFile.xlf:yourKey".'
                    . ' Alternatively, you can also define a default value.',
                    1639828178
                );
            }
        }
        try {
            $locale = self::getUsedLocale($arguments['languageKey'], $request);
            $value = LocalizationUtility::translate($id, $extensionName, $translateArguments, $locale, $arguments['alternativeLanguageKeys'] ?? null);
        } catch (\InvalidArgumentException) {
            // @todo: Switch to more specific Exceptions here - for instance those thrown when a package was not found, see #95957
            $value = null;
        }
        if ($value === null) {
            return self::handleDefaultValue($default, $translateArguments);
        }
        return $value;
    }

    /**
     * Ensure that a string is returned, if the underlying logic returns null, or cannot handle a translation
     */
    protected static function handleDefaultValue(string $default, ?array $translateArguments): string
    {
        if (!empty($translateArguments)) {
            return vsprintf($default, $translateArguments);
        }
        return $default;
    }

    protected static function getUsedLocale(Locale|string|null $languageKey, ?ServerRequestInterface $request): Locale|string|null
    {
        if ($languageKey !== null && $languageKey !== '') {
            return $languageKey;
        }
        if ($request) {
            return GeneralUtility::makeInstance(Locales::class)->createLocaleFromRequest($request);
        }
        return null;
    }
}
