
.. include:: /Includes.rst.txt

==============================================================
Feature: #54519 - Report links to disabled linkhandler records
==============================================================

See :issue:`54519`

Description
===========

A new configuration option has been introduced for the linkhandler link checker
in the linkvalidator extension:

.. code-block:: typoscript

	mod.linkvalidator.linkhandler.reportHiddenRecords = 1

..

When enabled links will be considered invalid when they point to disabled records.
By default only links to deleted records are reported.

Impact
======

The `\TYPO3\CMS\Linkvalidator\Linktype::checkLink()` method has been restructured
and will now determine if the linked record is deleted or hidden and report
a error depending on the `reportHiddenRecords` configuration.


.. index:: PHP-API, Backend, ext:linkvalidator
