.. include:: ../../Includes.txt

================================================================
Deprecation: #70526 - Location of formatToPageTypeMapping option
================================================================

See :issue:`70526`

Description
===========

Since its introduction, the option :ts:`formatToPageTypeMapping` had to be configured in :ts:`settings.view.formatToPageTypeMapping` instead of :ts:`view.formatToPageTypeMapping`. This has been marked as deprecated.


Impact
======

Defining :ts:`settings.view.formatToPageTypeMapping` will trigger a deprecation log entry.


Affected Installations
======================

Installations containing plugins that define :ts:`settings.view.formatToPageTypeMapping` instead of :ts:`view.formatToPageTypeMapping`.


Migration
=========

Move

.. code-block:: typoscript

    plugin.tx_myextension.settings.view.formatToPageTypeMapping

to

.. code-block:: typoscript

    plugin.tx_myextension.view.formatToPageTypeMapping

.. index:: Frontend, TypoScript, NotScanned
