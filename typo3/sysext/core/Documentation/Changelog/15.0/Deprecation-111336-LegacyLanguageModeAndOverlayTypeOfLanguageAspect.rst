..  include:: /Includes.rst.txt

..  _deprecation-111336-1793772664:

==============================================================================
Deprecation: #111336 - Legacy language mode and overlay type of LanguageAspect
==============================================================================

See :issue:`111336`

Description
===========

The following methods of
:php:`\TYPO3\CMS\Core\Context\LanguageAspect` have been marked as deprecated
and will be removed in TYPO3 v16.0:

*   :php:`LanguageAspect->getLegacyLanguageMode()`
*   :php:`LanguageAspect->getLegacyOverlayType()`

The same applies to the corresponding aspect properties
:php:`legacyLanguageMode` and :php:`legacyOverlayType`, for example when
accessed via
:php:`$context->getPropertyFromAspect('language', 'legacyOverlayType')`.

Both methods were introduced in TYPO3 v9 to map the language aspect to the
former TypoScript options :typoscript:`config.sys_language_mode` and
:typoscript:`config.sys_language_overlay`, which have been removed since. For
a language aspect created from a site language, the legacy language mode is
always :php:`'content_fallback'`, so it does not carry any information.

For the same reason, the "page not found" reason
:php:`\TYPO3\CMS\Frontend\Page\PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE_STRICT_MODE`
(:php:`'language.strict'`) has been marked as deprecated and will be removed
in TYPO3 v16.0. TYPO3 used it only for the legacy language mode
:php:`'strict'`, which never applies to site languages, so this reason is
not passed to error handlers anymore.

Impact
======

Calling one of the methods, or accessing one of the aspect properties, triggers
a PHP deprecation warning. The methods and properties continue to work as
before until they are removed in TYPO3 v16.0.

Using the constant
:php-short:`\TYPO3\CMS\Frontend\Page\PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE_STRICT_MODE`
does not trigger a deprecation warning.

Affected installations
======================

TYPO3 installations with custom extensions that call
:php:`LanguageAspect->getLegacyLanguageMode()` or
:php:`LanguageAspect->getLegacyOverlayType()`, or that read the aspect
properties :php:`legacyLanguageMode` or :php:`legacyOverlayType`.

TYPO3 installations with custom error handlers or middlewares that check for
the reason
:php-short:`\TYPO3\CMS\Frontend\Page\PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE_STRICT_MODE`.

The extension scanner reports calls of the methods as a **weak match** and
usages of the constant as a **strong match**. Access to the aspect properties
is not detected.

Migration
=========

Remove checks for
:php-short:`\TYPO3\CMS\Frontend\Page\PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE_STRICT_MODE`.
A page that is not available in the requested language and its fallback
languages is reported with
:php-short:`\TYPO3\CMS\Frontend\Page\PageAccessFailureReasons::LANGUAGE_AND_FALLBACKS_NOT_AVAILABLE`,
or with
:php-short:`\TYPO3\CMS\Frontend\Page\PageAccessFailureReasons::LANGUAGE_NOT_AVAILABLE`
if the page is configured to be hidden when it is not translated.

Use :php:`LanguageAspect->getOverlayType()` with the constants
:php:`LanguageAspect::OVERLAYS_OFF`, :php:`LanguageAspect::OVERLAYS_MIXED`
and :php:`LanguageAspect::OVERLAYS_ON_WITH_FLOATING` instead of the legacy
overlay type, and :php:`LanguageAspect->doOverlays()` to check whether
records are overlaid at all.

Use :php:`LanguageAspect->getFallbackChain()` instead of the legacy language
mode to find out which languages are used as fallback.

..  code-block:: php
    :caption: Before (deprecated)

    if ($languageAspect->getLegacyOverlayType() === 'hideNonTranslated') {
        // ...
    }

..  code-block:: php
    :caption: After

    use TYPO3\CMS\Core\Context\LanguageAspect;

    if ($languageAspect->getOverlayType() === LanguageAspect::OVERLAYS_ON_WITH_FLOATING) {
        // ...
    }

..  index:: PHP-API, PartiallyScanned, ext:core, ext:frontend
