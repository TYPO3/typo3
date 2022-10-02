.. include:: /Includes.rst.txt

.. _breaking-96550:

=================================================================
Breaking: #96550 - TYPO3_CONF_VARS['SYS']['USdateFormat'] removed
=================================================================

See :issue:`96550`

Description
===========

The TYPO3 configuration had a boolean toggle
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']` that
changed the date rendering from "day-month-year" to "month-day-year"
in a couple of places in the backend - most prominently when editing records.

This configuration conflicts with option
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy']`, which is a broader approach
to configure a system wide date rendering format, especially in combination with option
:php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm']`.

To streamline date and time rendering in the backend and to eventually implement
a user-based and timezone-aware solution, option :php:`USdateFormat` has been
removed in favor of :php:`ddmmyy` from the configuration and is ignored now.

Impact
======

Backend users of instances with this option set to :php:`true` will experience
swapped day and month rendering when editing records in the backend.

The option is removed automatically from :file:`LocalConfiguration.php`
when upgrading to TYPO3 v12.

Affected Installations
======================

Instances having :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']` set to
default :php:`false` see no difference and are not affected. Instances with this
option set to :php:`true` are affected.

The extension scanner will find matching candidates in case the option is used
in extensions.

Migration
=========

Extensions accessing :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']`
should assume :php:`false` to keep compatibility with previous TYPO3 versions,
and should phase out the option usage.

.. index:: Backend, LocalConfiguration, FullyScanned, ext:backend
