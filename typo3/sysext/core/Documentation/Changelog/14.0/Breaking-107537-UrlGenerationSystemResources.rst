..  include:: /Includes.rst.txt

..  _breaking-107537-1760339938:

=================================================================
Breaking: #107537 - Changes in URL generation of system resources
=================================================================

See :issue:`107537`

Description
===========

The following changes are considered breaking, although their impact is
expected to be very low.

*   TypoScript getData function :typoscript:`path` previously returned a
    relative URL and now returns an absolute URL (prepended with
    :typoscript:`absRefPrefix`).

*   Access to FAL storages via relative path (`fileadmin/templates/main.css`)
    is limited to the default storage defined in :php:`$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']`.

*   All generated system resource URLs now include cache busting.

*   Adding custom query strings to resource identifiers no longer disables cache
    busting — both are now applied.

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
    :caption: Result (TYPO3 classic mode – note the leading "/")

    "path" result before: typo3/sysext/core/Resources/Public/Icons/Extension.svg
    "path" result now: /typo3/sysext/core/Resources/Public/Icons/Extension.svg

..  code-block:: text
    :caption: Result (TYPO3 Composer mode – note the leading "/")

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

All generated resource URLs now include cache busting.
For example, icon URLs that previously had no cache busting will now contain a
cache-busting query string.

..  _breaking-107537-querystrings:

Additional query strings applied to the resource identifier
-----------------------------------------------------------

When adding custom query strings to resource identifiers, TYPO3 previously
disabled cache busting.

Now, both the custom query string and the cache-busting parameter are applied.
If custom query strings were used as manual cache busters, you can now remove
them safely.

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

All usages of :typoscript:`path` in TypoScript :typoscript:`data` now resolve
to absolute URLs instead of relative ones.

..  _breaking-107537-impact-relative-fal-resource:

Relative path to FAL Storage
----------------------------

In installations referencing resources in additional local FAL storages using
a relative path syntax, an exception is thrown.

..  _breaking-107537-impact-cachebusting:

All generated URLs now contain cache busting
--------------------------------------------

Generated URLs differ slightly from previous TYPO3 versions, especially when
cache busting was not applied before.

..  _breaking-107537-impact-querystrings:

Additional query strings applied to the resource identifier
-----------------------------------------------------------

URLs now include both the original query string and the cache-busting
parameter, resulting in different output compared to earlier TYPO3 versions.

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

All installations using resource identifiers with custom query strings, for
example:

`EXT:foo/Resources/Public/rte.css?v=123`

Migration
=========

Relative path to FAL Storage
----------------------------

Either convert the relative path to a FAL resource like so:

`FAL:1:/path/to/file.css`

Alternatively it is possible to convert it to a resource URI:

`URI:/my-other-fileadmin/path/to/file.css`

The first will add cache busting, the latter will use the URI as is.

..  index:: Frontend, NotScanned, ext:frontend
