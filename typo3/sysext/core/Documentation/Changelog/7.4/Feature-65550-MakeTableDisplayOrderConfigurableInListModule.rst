
.. include:: ../../Includes.txt

======================================================================
Feature: #65550 - Make table display order configurable in List module
======================================================================

See :issue:`65550`

Description
===========

The new `PageTSconfig` configuration option `mod.web_list.tableDisplayOrder` has been added
for the List module to allow flexible configuration of the order in which tables are displayed.
The keywords `before` and `after` can be used to specify an order relative to other table names.

Example:

.. code-block:: typoscript

	mod.web_list.tableDisplayOrder.<tableName> {
	  before = <tableA>, <tableB>, ...
	  after = <tableA>, <tableB>, ...
	}
