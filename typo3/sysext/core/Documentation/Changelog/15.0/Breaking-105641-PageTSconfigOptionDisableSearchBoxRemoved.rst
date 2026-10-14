..  include:: /Includes.rst.txt

..  _breaking-105641-1791964585:

=================================================================
Breaking: #105641 - Page TSconfig option disableSearchBox removed
=================================================================

See :issue:`105641`

Description
===========

The page TSconfig option :typoscript:`mod.web_list.disableSearchBox` has been
removed in favor of the new option :typoscript:`mod.web_list.enableSearchBox`,
see :ref:`feature-105641-1791964585`.

Impact
======

Setting :typoscript:`mod.web_list.disableSearchBox = 1` has no effect anymore.
The search box of the :guilabel:`Content > Records` module can be enabled by
editors again via the :guilabel:`View` menu of the module.

No deprecation notice or other hint is shown for the obsolete option.

Affected installations
======================

Installations using :typoscript:`mod.web_list.disableSearchBox` in their page
TSconfig.

Migration
=========

Replace the option with :typoscript:`mod.web_list.enableSearchBox = deactivated`:

..  code-block:: typoscript
    :caption: EXT:my_sitepackage/Configuration/page.tsconfig

    # Before
    mod.web_list.disableSearchBox = 1

    # After
    mod.web_list.enableSearchBox = deactivated

..  index:: Backend, TSConfig, NotScanned, ext:backend
