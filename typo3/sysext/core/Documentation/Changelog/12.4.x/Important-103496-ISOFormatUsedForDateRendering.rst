.. include:: /Includes.rst.txt

.. _important-103496-1711623416:

=======================================================
Important: #103496 - ISO format used for date rendering
=======================================================

See :issue:`103496`

Description
===========

The default format for date rendering configured in :php:`$GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy']` has changed.

The former arbitrary :php:`'d-m-y'` format was replaced with the standard ISO 8601 :php:`'Y-m-d'` format.

Examples of dates where the :php:`'d-m-y'` format led to unclear dates:

* A 2-digit year could also be a day in a month: `21-04-23` could be understood as `2021-04-23` instead of `2023-04-21`.
* The century of years could not be distinguished: `21-04-71` could be `2071-04-21` or `1971-04-21`

This affects date display in various locations so code relying on the previous format (e.g. acceptance tests) must be updated accordingly.

.. index:: Backend, CLI, Frontend, TCA, ext:core
