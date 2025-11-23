..  include:: /Includes.rst.txt

..  _breaking-107436-1736639846:

============================================================
Breaking: #107436 - Localization system architecture changes
============================================================

See :issue:`107436`

Description
===========

The TYPO3 localization system has been migrated to use the Symfony Translation
components internally (see :ref:`feature-107436-1736639846`), which introduces
several breaking changes to the internal API and configuration handling.

This change affects only the internal processing of translation ("locallang")
files. The public API of the localization system remains unchanged.

**Method Signature Changes**

The method
:php:`\TYPO3\CMS\Core\Localization\LocalizationFactory::getParsedData()`
has a modified signature and behavior:

**Before:**

..  code-block:: php

    public function getParsedData(
        $fileReference,
        $languageKey,
        $_ = null,
        $__ = null,
        $isLocalizationOverride = false
    )

**After:**

..  code-block:: php

    public function getParsedData(string $fileReference, string $languageKey): array

It now only returns the parsed and combined localization data instead of both
the default and the localized data. To obtain the default (English) data, call
:php:`getParsedData($fileReference, 'en')`.

**Language Key Changes**

Internally, the fallback language key has been changed from `default` to `en`.
This does not affect public API usage, where `default` can still represent any
configured language key other than English.

**Configuration Changes**

Custom parser configuration is no longer supported.
The global configuration option
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser']` has been removed,
and any custom parser registered through it will be ignored.

Several configuration options have been moved or renamed:

*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser']`
*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['requireApprovedLocalizations']`
    → :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['requireApprovedLocalizations']`
*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']`
    → :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['format']`
*   :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lang']['availableLanguages']`
    → :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['availableLocales']`
*   :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']`
    → :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']`

Impact
======

Code calling
:php-short:`\TYPO3\CMS\Core\Localization\LocalizationFactory::getParsedData()`
with the old signature or expecting a multi-dimensional array structure will
break:

*   The method now enforces strict parameter types.
*   The method returns `en` instead of `default` for English translations.
*   Unused parameters have been removed.

Extensions that register custom localization parsers using
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser']` will no longer have
their parsers executed, potentially leading to missing translations.

Instances using the moved configuration options must update their configuration
to the new paths. The old options are no longer recognized and will be ignored.

Affected installations
======================

Installations that:

*   Call :php:`LocalizationFactory::getParsedData()` directly.
*   Register custom localization parsers via the removed configuration option.
*   Use any of the renamed or moved configuration options listed above.

Migration
=========

**Method Call Updates**

Update all calls to :php:`getParsedData()` to use the new signature and ensure
parameter types are correct:

**Before:**

..  code-block:: php
    :caption: Updated getParsedData() usage

    $data = $factory->getParsedData(
        $fileReference,
        $languageKey,
        null,
        null,
        false
    )[$languageKey];

**After:**

..  code-block:: php

    $data = $factory->getParsedData($fileReference, $languageKey);

**Custom Parser Migration**

Replace any custom localization parser with a Symfony Translation loader.
See :ref:`feature-107436-1736639846` for detailed migration instructions to the
new loader system.

..  index:: PHP-API, LocalConfiguration, NotScanned
