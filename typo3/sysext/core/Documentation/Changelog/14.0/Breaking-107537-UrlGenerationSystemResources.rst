.. include:: /Includes.rst.txt

.. _breaking-107537-1760339938:

=================================================================
Breaking: #107537 - Changes in URL generation of system resources
=================================================================

See :issue:`107537`

Description
===========

The following changes are considered breaking, although their impact is likely
very low.

* TypoScript getData :typoscript:`path` returned a relative URL
  and is now returning an absolute URL (prepended with `absRefPrefix`).

* Access to FAL storages other than the default storage using `$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']`
  via relative paths for resources (e.g. CSS) is not possible any more.

* All generated system resource URLs now contain cache busting.

* Additional query strings applied to the resource identifier will **not** disable cache busting.

getText "path" in TypoScript
----------------------------

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/TypoScript/setup.typoscript
    :emphasize-lines: 3

    page.20 = TEXT
    page.20 {
        data = path : EXT:core/Resources/Public/Icons/Extension.svg
    }

..  code-block:: text
    :caption: Result TYPO3 classic mode (note the leading "/")

    "path" result before: typo3/sysext/core/Resources/Public/Icons/Extension.svg
    "path" result now: /typo3/sysext/core/Resources/Public/Icons/Extension.svg

..  code-block:: text
    :caption: Result TYPO3 composer mode (note the leading "/")

    "path" result before: _assets/5f237792cbcdc97cfceade1e16ea33d7/Icons/Extension.svg
    "path" result now: /_assets/5f237792cbcdc97cfceade1e16ea33d7/Icons/Extension.svg

All generated URLs now contain cache busting
--------------------------------------------

All URLs now have cache busting applied. This means URLs that had no cache busting applied,
like Icon URLs will now have cache busting applied.

Additional query strings applied to the resource identifier
-----------------------------------------------------------

When adding a query string to a resource identifier, previously the cache busting
was disabled. Now the query string is respected, but cache busting is added as well.
It is recommended to drop the custom query string, when it was used for cache busting
in places TYPO3 did not include cache busting previously.

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/TypoScript/setup.typoscript
    :emphasize-lines: 3

    page.20 = TEXT
    page.20 {
        data = asset : EXT:core/Resources/Public/Icons/Extension.svg?v=1234
    }

..  code-block:: text
    :caption: Result

    result before: /typo3/sysext/core/Resources/Public/Icons/Extension.svg?v=1234
    result now: /typo3/sysext/core/Resources/Public/Icons/Extension.svg?v=1234&1709051481

Impact
======

getText "path" in TypoScript
----------------------------

All installations using :typoscript:`path` of TypoScript :typoscript:`data`,
will now have these usages resolved to absolute URLs, instead of relative.

All generated URLs now contain cache busting
--------------------------------------------

URLs slightly differ from previous TYPO3 versions, especially when cache busting
was not applied previously.

Additional query strings applied to the resource identifier
-----------------------------------------------------------

URLs differ from previous TYPO3 versions, as the cache busting is added additionally
to the custom query string.

Affected installations
======================

getText "path" in TypoScript
----------------------------

All installations using :typoscript:`path` in TypoScript :typoscript:`data`.

Additional query strings applied to the resource identifier
-----------------------------------------------------------

All installations, that use resource identifiers with a custom query string,
like `EXT:foo/Resources/Public/rte.css?v=123`

All generated URLs now contain cache busting
--------------------------------------------

All installations having third party code, that misuses generated URLs
to assume file system paths from them.

.. index:: Frontend, NotScanned, ext:frontend
