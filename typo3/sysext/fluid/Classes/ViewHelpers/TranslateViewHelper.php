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
use TYPO3\CMS\Core\Localization\TranslationDomainMapper;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\RequestInterface as ExtbaseRequestInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Exception;

/**
 * ViewHelper to provide a translation for language keys ("locallang"/"LLL").
 * By default, the files are loaded from the folder `Resources/Private/Language/`.
 * Placeholder substitution (like PHP's `sprintf()`) can be evaluated when provided as
 * `arguments` attribute.
 *
 * ```
 *   <f:translate key="LLL:EXT:myext/Resources/Private/Language/locallang.xlf:key1" />
 *   <f:translate key="someKey" arguments="{0: 'dog', 'fox'}" />
 * ```
 *
 * @see https://php.net/sprintf
 * @see https://docs.typo3.org/permalink/t3viewhelper:typo3-fluid-translate
 */
final class TranslateViewHelper extends AbstractViewHelper
{
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
        $this->registerArgument('domain', 'string', 'Translation Domain to be used for the ID/Key. Takes precedence over "extensionName". Should also be used over "extensionName".');
        $this->registerArgument('languageKey', 'string', 'Language key ("da" for example) or "default" to use. Also a Locale object is possible. If empty, use current locale from the request.');
    }

    /**
     * Return array element by key.
     *
     * @throws Exception
     * @throws \RuntimeException
     */
    public function render(): string
    {
        $key = $this->arguments['key'];
        $id = $this->arguments['id'];
        $default = (string)($this->arguments['default'] ?? $this->renderChildren() ?? '');
        $domain = $this->arguments['domain'];
        $extensionName = $this->arguments['extensionName'];
        $translateArguments = $this->arguments['arguments'];
        // Use key if id is empty.
        if ($id === null) {
            $id = $key;
        }
        $id = (string)$id;
        if ($id === '') {
            throw new Exception('An argument "key" or "id" has to be provided', 1351584844);
        }
        $request = null;
        if ($this->renderingContext->hasAttribute(ServerRequestInterface::class)) {
            $request = $this->renderingContext->getAttribute(ServerRequestInterface::class);
        }
        // If a domain is given, it takes precedence over extensionName
        if (!empty($domain)) {
            $extensionName = $domain;
        } elseif (empty($extensionName)) {
            if (str_starts_with($id, 'LLL:EXT:')) {
                $extensionName = substr($id, 8, strpos($id, '/', 8) - 8);
            } elseif (str_starts_with($id, 'LLL:')) {
                // Implicit domain usage, let's keep it as is
                [$prefix, $domain, $id] = explode(':', $id, 3);
                $extensionName = $domain;
            } elseif (str_contains($id, ':')) {
                // Check if the domain name is actually valid.
                [
                    $possibleDomain,
                    $possibleId
                ] = explode(':', $id, 2);
                $domainMapper = GeneralUtility::makeInstance(TranslationDomainMapper::class);
                if ($domainMapper->isValidDomainName($possibleDomain) && $domainMapper->mapDomainToFileName($possibleDomain) !== $possibleDomain) {
                    $extensionName = $possibleDomain;
                    $id = $possibleId;
                }
            } elseif ($request instanceof ExtbaseRequestInterface) {
                $extensionName = $request->getControllerExtensionName();
            } else {
                if ($default) {
                    return self::handleDefaultValue($default, $translateArguments);
                }
            }
        }

        if (empty($extensionName)) {
            // Throw exception in case neither an extension key nor a extbase request
            // are given, since the "short key" shouldn't be considered as a label.
            throw new \RuntimeException(
                'ViewHelper f:translate in non-extbase context needs attribute "domain" or "extensionName" to resolve'
                . ' key="' . $id . '" without path. Either set attribute "domain" or "extensionName" together with the short'
                . ' key "yourKey" to result'
                . ' or (better) use a full LLL reference like key="LLL:your_extension.name:yourKey".'
                . ' Alternatively, you can also define a default value.',
                1639828178
            );
        }

        try {
            $locale = self::getUsedLocale($this->arguments['languageKey'], $request);
            $value = LocalizationUtility::translate($id, $extensionName, $translateArguments, $locale, $request);
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
    private static function handleDefaultValue(string $default, ?array $translateArguments): string
    {
        if (!empty($translateArguments)) {
            return vsprintf($default, $translateArguments);
        }
        return $default;
    }

    private static function getUsedLocale(Locale|string|null $languageKey, ?ServerRequestInterface $request): Locale|string|null
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
