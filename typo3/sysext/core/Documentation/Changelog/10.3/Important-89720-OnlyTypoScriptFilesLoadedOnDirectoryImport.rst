.. include:: /Includes.rst.txt

====================================================================
Important: #89720 - Only TypoScript files loaded on directory import
====================================================================

See :issue:`89720`

Description
===========

With :issue:`82812` the new :typoscript:`@import` syntax for importing TypoScript has been added.

Among others the change was documented to only load :file:`*.typoscript` files in case a directory is imported. However, this was not implemented as such and all files where imported instead.

The code has been fixed to only load :file:`*.typoscript` files on directory import. To load other files besides :file:`*.typoscript` a suitable file pattern must be added explicitly now:

.. code-block:: typoscript

    # Import TypoScript files with legacy ".txt" extension
    @import 'EXT:myproject/Configuration/TypoScript/Setup/*.txt'

.. index:: TypoScript, ext:core
