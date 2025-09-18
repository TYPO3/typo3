.. include:: /Includes.rst.txt

.. _deprecation-107537-1760338410:

================================================
Deprecation: #107537 - TypoScript getData "path"
================================================

See :issue:`107537`

Description
===========

TypoScript getData has two ways to get a URL for a system resource,
`asset` and `path`. However, the result used to differ:

*   `path` returned a relative URL without cache busting
*   `asset` returned an absolute URI with cache busting.

Both are now returning the same URI (with cache busting) and `path` is deprecated,
but continues to work until TYPO3 v15, where this option will be removed.

getText "asset" and "path" in TypoScript
----------------------------------------

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/TypoScript/setup.typoscript
    :emphasize-lines: 3

    page.20 = TEXT
    page.20 {
        data = asset : EXT:core/Resources/Public/Icons/Extension.svg
    }

    page.30 = TEXT
    page.30 {
        data = path : EXT:core/Resources/Public/Icons/Extension.svg
    }

..  code-block:: text
    :caption: Result from now on

    /typo3/sysext/core/Resources/Public/Icons/Extension.svg?1709051481
    /typo3/sysext/core/Resources/Public/Icons/Extension.svg?1709051481

Impact
======

All installations using `path` in TypoScript `data`
will trigger a deprecation notice.

This will continue to work until removed in TYPO3 v15.0, but produce
absolute cache busting URLs.


Affected installations
======================

All installations using `path` in TypoScript `data`.

.. index:: Frontend, NotScanned, ext:frontend
