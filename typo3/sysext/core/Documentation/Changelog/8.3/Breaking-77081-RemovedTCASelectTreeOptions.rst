
.. include:: ../../Includes.txt

===================================================================================
Breaking: #77081 - Removed TCA tree options: width, allowRecursiveMode, autoSizeMax
===================================================================================

See :issue:`77081`

Description
===========

The following three TCA configuration options have been removed from the FormEngine TCA Tree
functionality which is e.g. used within the FormEngine for the selection of categories.

TCA Column Config

* [config][treeConfig][appearance][allowRecursiveMode]

The option hasn't been working for a while and the documentation vs. implementation
was off - see :issue:`77074`

* [config][treeConfig][appearance][width]

* [config][autoSizeMax]

The options have no influence on the rendering of FormEngine select field configured
with :php:`'renderType' => 'selectTree'` anymore.

The Recursive selection button (the green arrow button located on the category tree toolbar) was
not widely used, mostly due that nobody expected the green "refresh" icon was related to recursive
selection.

When implemented 4 years ago, the purpose of this button was to ease handling of
"record storage page". But now the "recursive" select box can be used for this usage.

The option `autosizemax` has been dropped as the `size` can be used as maximum height.

Impact
======

The recursive selection mode button is not available any longer.

The options `width` and `autoSizeMax` have no impact on the tree rendering.

The TCA Tree now fills the full width of the parent container.

Instead of using the option `autoSizeMax` the configuration is now using the `size` parameter as maximal
height of the TCA tree.


Affected Installations
======================

Any TYPO3 installation using a TCA Tree within FormEngine with one of the options above configured.


Migration
=========

Use the `size` option and tune it to higher value, if it was used in combination
with `autoSizeMax`.

.. index:: TCA, Backend
