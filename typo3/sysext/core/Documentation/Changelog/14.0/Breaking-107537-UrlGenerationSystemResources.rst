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

* TypoScript getData :typoscript:`path` returned a relative URL without cache busting
  and is now returning an absolute URI including cache busting information.

* Setting the `<f:uri.resource>` argument `useCacheBusting` to false has no effect any more,
  as it is now always enforced.

* Access to FAL storages other than the default storage using `$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir']`
  via relative paths for resources (e.g. CSS) is not possible any more.

* All generated URLs now contain a cache busting.

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
    :caption: Result

    "path" result before: typo3/sysext/core/Resources/Public/Icons/Extension.svg
    "path" result now: /typo3/sysext/core/Resources/Public/Icons/Extension.svg?1709051481

..  note::

    If further arguments were previously passed to the result of this resolving
    (like `?something=x`), these may now need to be appended with a `&` argument
    separator, depending on whether cache busting URIs use request parameters or
    filename patterns.

f:uri.resource view helper in Fluid
-----------------------------------

..  code-block:: html
    :caption: EXT:my_extension/Resources/Private/Template/MyTemplate.html
    :emphasize-lines: 3

    <f:uri.resource
        path="EXT:core/Resources/Public/Icons/Extension.svg"
        useCacheBusting="false"
    />

..  code-block:: text
    :caption: Comparison

    Before: /typo3/sysext/core/Resources/Public/Icons/Extension.svg
    Now: /typo3/sysext/core/Resources/Public/Icons/Extension.svg?1709051481

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

All installations using a resolved `path` of TypoScript `data`
will trigger a deprecation notice.

This will continue to work until removed in TYPO3 v15.0
(see details in :ref:`deprecation-107537-1760338410`), but produces
absolute cache busting URIs.

f:uri.resource view helper in Fluid
-----------------------------------

Cache busting can not be disabled any more when using this view helper.
Using the argument is deprecated and will be removed in TYPO3 v15.

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

All installations using `path` in TypoScript `data`.

f:uri.resource view helper in Fluid
-----------------------------------

All installations, that use `<f:uri.resource>` view helper with
`useCacheBusting="false"`

Additional query strings applied to the resource identifier
-----------------------------------------------------------

All installations, that use resource identifiers with a custom query string,
like `EXT:foo/Resources/Public/rte.css?v=123`

All generated URLs now contain cache busting
--------------------------------------------

All installations having third party code, that misuses generated URLs
to assume file system paths from them.

.. index:: Frontend, NotScanned, ext:frontend
