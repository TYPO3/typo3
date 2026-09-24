..  include:: /Includes.rst.txt

..  _important-88597-1790251200:

=====================================================================
Important: #88597 - Indexed search honors blind.freeIndexUid setting
=====================================================================

See :issue:`88597`

Description
===========

The TypoScript setting
:typoscript:`plugin.tx_indexedsearch.settings.blind.freeIndexUid` is
documented with a default of :typoscript:`1`, which hides the "Category"
selector in the advanced search. The setting was not evaluated by the search
controller, so the selector was always displayed.

The setting is now honored. As a consequence, the "Category" selector is no
longer displayed by default. Integrators who want to display it need to set:

..  code-block:: typoscript

    plugin.tx_indexedsearch.settings.blind.freeIndexUid = 0

In addition, the "Extended resume" checkbox now has a label and is no longer
hidden by :typoscript:`blind.group`, and the advanced search can be enabled by
default via :typoscript:`plugin.tx_indexedsearch.settings.defaultOptions.extendedSearch = 1`.

..  index:: Frontend, TypoScript, NotScanned, ext:indexed_search
