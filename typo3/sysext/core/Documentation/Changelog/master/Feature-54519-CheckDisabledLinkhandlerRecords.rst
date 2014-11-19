==============================================================
Feature: #54519 - Report links to disabled linkhandler records
==============================================================

Description
===========

A new configuration option is introduced for the linkhandler link checker
in the linkvalidator extension:

.. code-block:: typoscript

	mod.linkvalidator.linkhandler.reportHiddenRecords = 1

..

When this setting is enabled links will be considered invalid, when they
point to disabled records. By default only links to deleted records are
reported.

Impact
======

The `\TYPO3\CMS\Linkvalidator\Linktype::checkLink()` method is restructured
and will now determine if the linked record is deleted or hidden and report
an error depending on the `reportHiddenRecords` configuration.