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

* Access to FAL storages via relative path (`fileadmin/templates/main.css`)
  is limited to the default storage defined in :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']`.

* All generated system resource URLs now contain cache busting.

* Additional query strings applied to the resource identifier will **not** disable cache busting.

..  _breaking-107537-gettext-path:

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

..  _breaking-107537-relative-fal-resource:

Relative path to FAL Storage
----------------------------

Referencing resources via relative path does only work for the default FAL storage
defined in :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']`.
This means if you have other local FAL storages configured, you need to use the FAL resource syntax
(e.g. `FAL:42:/path/to/file.css`) to reference files in such storage instead of using relative paths
(e.g. `my-other-fileadmin/path/to/file.css`).
It is generally recommended to use explicit resource identifiers (App resources or FAL resources),
instead of relative paths.

..  _breaking-107537-cachebusting:

All generated URLs now contain cache busting
--------------------------------------------

All URLs now have cache busting applied. This means URLs that had no cache busting applied,
like Icon URLs will now have cache busting applied.

..  _breaking-107537-querystrings:

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

..  _breaking-107537-impact-gettext-path:

getText "path" in TypoScript
----------------------------

All installations using :typoscript:`path` of TypoScript :typoscript:`data`,
will now have these usages resolved to absolute URLs, instead of relative.

..  _breaking-107537-impact-relative-fal-resource:

Relative path to FAL Storage
----------------------------

In installations referencing resources in additional local FAL storages using
a relative path syntax, an exception is thrown.

..  _breaking-107537-impact-cachebusting:

All generated URLs now contain cache busting
--------------------------------------------

URLs slightly differ from previous TYPO3 versions, especially when cache busting
was not applied previously.

..  _breaking-107537-impact-querystrings:

Additional query strings applied to the resource identifier
-----------------------------------------------------------

URLs differ from previous TYPO3 versions, as the cache busting is added additionally
to the custom query string.

Affected installations
======================

..  _breaking-107537-affected-gettext-path:

getText "path" in TypoScript
----------------------------

All installations using :typoscript:`path` in TypoScript :typoscript:`data`.

..  _breaking-107537-affected-relative-fal-resource:

Relative path to FAL Storage
----------------------------

All installations referencing resources in additional local FAL storages using
a relative path syntax (e.g. `my-other-fileadmin/path/to/file.css`).

..  _breaking-107537-affected-cachebusting:

All generated URLs now contain cache busting
--------------------------------------------

All installations having third party code, that misuses generated URLs
to assume file system paths from them.

..  _breaking-107537-affected-querystrings:

Additional query strings applied to the resource identifier
-----------------------------------------------------------

All installations, that use resource identifiers with a custom query string,
like `EXT:foo/Resources/Public/rte.css?v=123`

Migration
=========

Relative path to FAL Storage
----------------------------

Either convert the relative path to a FAL resource like so:

`FAL:1:/path/to/file.css`

Alternatively it is possible to convert it to a resource URI:

`URI:/my-other-fileadmin/path/to/file.css`

The first will add cache busting, the latter will use the URI as is.

.. index:: Frontend, NotScanned, ext:frontend
