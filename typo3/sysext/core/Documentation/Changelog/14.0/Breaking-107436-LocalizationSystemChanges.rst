.. include:: /Includes.rst.txt

.. _breaking-107436-1736639846:

============================================================
Breaking: #107436 - Localization system architecture changes
============================================================

See :issue:`107436`

Description
===========

The TYPO3 localization system has been migrated to use Symfony Translation
components under the hood (see :ref:`feature-107436-1736639846`), resulting
in several breaking changes to the internals of localization API.

This functionality only affects internal handling of translation
files ("locallang" files). The public API of the localization system remains
unchanged.

**Method Signature Changes**

The :php:`\TYPO3\CMS\Core\Localization\LocalizationFactory::getParsedData()`
method has a modified signature and behavior:

.. code-block:: php

    // Before
    public function getParsedData($fileReference, $languageKey, $_ = null, $__ = null, $isLocalizationOverride = false)

    // After
    public function getParsedData(string $fileReference, string $languageKey): array

In addition, it only returns the parsed and combined localization data
instead of the "default" data as well, which can be loaded via
:php:`getParsedData($fileReference, 'en')`.

**Language Key Changes**

Internally, the localization system now replaces the internal fallback
language key from "default" to "en".
Note that this does not affect public API, where "default" can of course
still map to any configured language key other than "en".

**Configuration Changes**

Custom parser configuration is no longer supported. The global configuration
option :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser']` has been
removed and custom parsers configured through this option will be ignored.

Several configuration options have been moved or renamed:

- :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['requireApprovedLocalizations']` → :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['requireApprovedLocalizations']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['format']` → :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['format']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['lang']['availableLanguages']` → :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['availableLocales']`
- :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['locallangXMLOverride']` → :php:`$GLOBALS['TYPO3_CONF_VARS']['LANG']['resourceOverrides']`

Impact
======

Code calling :php:`LocalizationFactory::getParsedData()` with the old signature
or expecting a multi-dimensional array as a resulting language key will break:

- The method now requires strict type parameters
- The method now returns "en" instead of "default" for English translations
- Unused parameters have been removed

Extensions that register custom localization parsers via
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['lang']['parser']` will find their
parsers are no longer executed, potentially causing missing translations.

Extensions or installations using the moved configuration options will need
to update their configuration to use the new option paths. The old configuration
options are no longer recognized and will be ignored.

Affected installations
======================

Installations that:

- Call :php:`LocalizationFactory::getParsedData()` directly
- Have custom localization parsers registered via the removed configuration option
- Accessing any of the migrated configuration options above.

Migration
=========

**Method Call Updates**

Update calls to :php:`getParsedData()` to use the new signature, and ensure
types are matching:

.. code-block:: php

    // Before
    $data = $factory->getParsedData($fileReference, $languageKey, null, null, false)[$languageKey];

    // After
    $data = $factory->getParsedData($fileReference, $languageKey);

**Custom Parser Migration**

Replace custom parsers with Symfony Translation loaders. See the Feature RST
:ref:`feature-107436-1736639846` for detailed migration instructions to the new loader system.

.. index:: PHP-API, LocalConfiguration, NotScanned
