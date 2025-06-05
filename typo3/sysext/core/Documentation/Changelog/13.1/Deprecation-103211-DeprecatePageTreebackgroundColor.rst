.. include:: /Includes.rst.txt

.. _deprecation-103211-1709038752:

=========================================================
Deprecation: #103211 - Deprecate pageTree.backgroundColor
=========================================================

See :issue:`103211`

Description
===========

The user TSconfig option :typoscript:`options.pageTree.backgroundColor`
has been deprecated and will be removed in TYPO3 v14 due to its
lack of accessibility. It is being replaced with a
:ref:`new label system <feature-103211-1709036591>` for tree nodes.


Impact
======

During v13, :typoscript:`options.pageTree.backgroundColor` will be
migrated to the new label system. Since the use case is unknown,
the generated label will be "Color: <value>". This information
will be displayed on all affected nodes.


Affected installations
======================

All installations that use the user TSconfig option
:typoscript:`options.pageTree.backgroundColor` are affected.


Migration
=========

Before:

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/user.tsconfig

    options.pageTree.backgroundColor.<pageid> = #ff8700

After:

..  code-block:: typoscript
    :caption: EXT:my_extension/Configuration/user.tsconfig

    options.pageTree.label.<pageid> {
        label = Campaign A
        color = #ff8700
    }

.. index:: Backend, JavaScript, TSConfig, NotScanned, ext:backend
