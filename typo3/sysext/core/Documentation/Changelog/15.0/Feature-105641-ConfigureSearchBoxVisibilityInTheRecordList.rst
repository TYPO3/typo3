..  include:: /Includes.rst.txt

..  _feature-105641-1791964585:

=====================================================================
Feature: #105641 - Configure search box visibility in the record list
=====================================================================

See :issue:`105641`

Description
===========

The search box in the :guilabel:`Content > Records` module can now be
configured with the new page TSconfig option
:typoscript:`mod.web_list.enableSearchBox`, following the existing
:typoscript:`mod.web_list.enableClipBoard` option.

The following values are available:

`activated`
    The search box is always shown. The toggle in the :guilabel:`View`
    menu of the module is hidden.

`selectable`
    The search box can be shown and hidden by each user via the
    :guilabel:`View` menu. This is the default behavior.

`deactivated`
    The search box is never shown and the toggle is hidden.

The new option replaces :typoscript:`mod.web_list.disableSearchBox`, see
:ref:`breaking-105641-1791964585`.

As before, the search box is always shown when a search term is submitted,
for example when following the "Show all" link of the backend search.

Example
-------

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/page.tsconfig

    mod.web_list.enableSearchBox = activated

Impact
======

Integrators can show the search box of the record list permanently for
their editors, without each editor having to enable it.

..  index:: Backend, TSConfig, ext:backend
