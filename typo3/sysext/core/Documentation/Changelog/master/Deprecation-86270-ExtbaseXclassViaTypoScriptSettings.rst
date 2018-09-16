.. include:: ../../Includes.txt

==============================================================================
Deprecation: #86270 - config.tx_extbase.objects and plugin.tx_%plugin%.objects
==============================================================================

See :issue:`86270`

Description
===========

The :ts:`config.tx_extbase.objects` and :ts:`plugin.tx_%plugin%.objects` configuration options have been marked as deprecated.


Impact
======

Configuring class overrides using :ts:`config.tx_extbase.objects` or :ts:`plugin.tx_%plugin%.objects` will log a
deprecation warning.


Affected Installations
======================

All installations that make use of the TypoScript :ts:`config.tx_extbase.objects` or :ts:`plugin.tx_%plugin%.objects`
configuration options are affected.


Migration
=========

Use XCLASSes configured through :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects']` instead.
If you conditionally configured :ts:`config.tx_extbase.objects` or
:ts:`plugin.tx_%plugin%.objects`, then move that conditional logic into the XCLASS itself.

.. index:: TypoScript, NotScanned, ext:extbase
