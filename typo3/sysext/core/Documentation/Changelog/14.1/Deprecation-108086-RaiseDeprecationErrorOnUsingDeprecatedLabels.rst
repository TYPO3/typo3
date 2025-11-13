..  include:: /Includes.rst.txt

..  _deprecation-108086-1763028403:

=========================================================================
Deprecation: #108086 - Raise deprecation error on using deprecated labels
=========================================================================

See :issue:`108086`

Description
===========

Until now, localization labels marked as deprecated, for example by using the
:xml:`x-unused-since` attribute in XLIFF 1.2 or the :xml:`subState="deprecated"`
attribute in XLIFF 2.0, did not trigger a runtime warning. As a result,
integrators and developers had no automatic way to detect deprecated labels
that were still in use.

With this change, TYPO3 now triggers an :php:`E_USER_DEPRECATED` error when a
deprecated label is first written to the localization cache.

Impacted formats
================

XLIFF 1.2
----------

..  code-block:: xml

    <trans-unit id="deprecated_label" x-unused-since="4.5">
        <source>This label is deprecated</source>
    </trans-unit>

XLIFF 2.0
----------

..  code-block:: xml

    <unit id="label5">
        <segment subState="deprecated">
            <source>This is label #5 (deprecated in English)</source>
        </segment>
    </unit>

Custom loaders
--------------

When a label identifier ends with `.x-unused`, TYPO3 raises a deprecation
warning if the label is referenced, regardless of whether the reference includes
the `.x-unused` suffix.

Custom loaders can use this behaviour to also provide mechanisms to deprecate
labels.

Fallback behaviour
==================

*   If a label is deprecated in the fallback language but is overridden in the
    current locale without a deprecation marker, no deprecation warning is raised.
*   If a label is deprecated only in the current locale, TYPO3 falls back to the
    default language and does not raise a deprecation warning.

A deprecation warning is triggered the first time a deprecated label is written
to cache. Subsequent resolutions of the same label use the cached entry and do
not trigger additional warnings until the cache is cleared.

The following usages emit a deprecation warning when a deprecated label is
resolved.

LanguageService
---------------

..  code-block:: php

    $this->languageService->sL('EXT:core/Resources/Private/Language/locallang.xlf:someDeprecation');
    $this->languageService->sL('core.messages:someDeprecation');

Fluid ViewHelper
----------------

Usage of the `f:translate` ViewHelper in both Extbase and non-Extbase contexts:

..  code-block:: html

    <f:translate key="some_deprecation" domain="core.messages" />

    <f:translate key="core.messages:some_deprecation" />

    <f:translate key="EXT:core/Resources/Private/Language/locallang.xlf:some_deprecation" />

The Extension Scanner does not detect the usage of deprecated localization
labels. Developers must rely on runtime deprecation logs to identify these
occurrences.

Impact
======

Integrators and developers may encounter new deprecation warnings during runtime
or in the deprecation log when deprecated localization labels are used. The
warnings help identify and replace outdated labels before they are removed in a
future TYPO3 version.

Affected installations
======================

All TYPO3 installations that use localization labels marked as deprecated are
affected. This includes custom extensions, site packages, or integrations that
still reference deprecated labels from system or extension language files.

When a custom extension or project defines a label whose identifier ends with
`.x-unused`, that label is considered deprecated regardless of the loader
used. Such usage is technically possible but generally unlikely.


Migration
=========

1.  Review the deprecation log for warnings related to localization labels. Note
    that deprecations are only written the first time a label is used after
    deleting the cache.
2.  Replace usages of deprecated labels with non-deprecated ones where possible.
3.  If required, override deprecated labels in a custom locale without a
    deprecation marker.
4.  Remove or update labels marked with `x-unused-since` in XLIFF 1.2 or with
    `subState="deprecated"` in XLIFF 2.0 when they are no longer needed.
5.  Avoid defining labels with identifiers ending in `.x-unused`.

..  index:: ext:core, NotScanned
