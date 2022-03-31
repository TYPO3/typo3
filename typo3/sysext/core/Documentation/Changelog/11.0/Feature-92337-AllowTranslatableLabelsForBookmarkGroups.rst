.. include:: /Includes.rst.txt

===============================================================
Feature: #92337 - Allow translatable labels for bookmark groups
===============================================================

See :issue:`92337`

Description
===========

The user TSconfig option :typoscript:`options.bookmarkGroups` allows to configure the bookmark
groups that can be accessed by the user. In addition to that, it's also possible
to define custom labels for each group as simple :php:`string`. Extended TSconfig
syntax now allows the LLL prefix for the use of language labels.


Example
=======

.. code-block:: typoscript

   options.bookmarkGroups.2 = LLL:EXT:sitepackage/Resources/Private/Language/locallang_be.xlf:bookmarkGroups.2


Impact
======

It is now possible to use custom language labels for bookmark groups.

.. index:: Backend, TSConfig, ext:backend
