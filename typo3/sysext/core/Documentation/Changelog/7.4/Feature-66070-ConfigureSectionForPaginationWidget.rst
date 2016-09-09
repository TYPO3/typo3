
.. include:: ../../Includes.txt

========================================================
Feature: #66070 - Configure anchor for pagination widget
========================================================

See :issue:`66070`

Description
===========

This feature allows to add a key "section" to the configuration of a fluid pagination widget. The anchor gets appended
to every link of the pagination widget. The "widget.link" viewHelper used by the pagination widget already supports this.

Examples
--------

The following example will render the page browser having a section parameter "#archive" appended to every link

.. code-block:: html

	<f:widget.paginate objects="{plantpestWarnings}" as="paginatedWarnings" configuration="{section: 'archive', itemsPerPage: 10, insertAbove: 0, insertBelow: 1, maximumNumberOfLinks: 10}">
	[...]
	</f:widget.paginate>


Impact
======

If the "section" attribute does not get specified or no configuration is supplied at all then no section parameter
(#section) will get appended to the links and the pagination widget behaves as usual.
