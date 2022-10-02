.. include:: /Includes.rst.txt

.. _breaking-97605-1652214290:

============================================================================
Breaking: #97605 - Remove field resizeTextareas_MaxHeight from user settings
============================================================================

See :issue:`97605`

Description
===========

The field :php:`resizeTextareas_MaxHeight` with the label *Maximum height of text areas in pixels* has been removed.

The impact of the field is low and its removal simplifies the user settings module.

Impact
======

The height of textareas is the same for every user.

Affected installations
======================

Every TYPO3 installation.

Migration
=========

There is no migration available. If this feature is needed, the rendering of a field can be modified by a custom :php:`FormElement`.

.. index:: Backend, NotScanned, ext:backend
