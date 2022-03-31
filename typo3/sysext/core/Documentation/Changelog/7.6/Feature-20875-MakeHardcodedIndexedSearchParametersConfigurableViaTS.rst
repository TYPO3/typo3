
.. include:: /Includes.rst.txt

==============================================================================
Feature: #20875 - Make hardcoded indexed_search parameters configurable via TS
==============================================================================

See :issue:`20875`

Description
===========

The following TS properties can now be configured for indexed search

.. code-block:: typoscript

	[plugin.tx_indexedsearch.results. || plugin.tx_indexedsearch.settings.results.]
	titleCropAfter = 50
	titleCropSignifier = ...
	summaryCropAfter = 180
	summaryCropSignifier =
	hrefInSummaryCropAfter = 60
	hrefInSummaryCropSignifier = ...
	markupSW_summaryMax = 300
	markupSW_postPreLgd = 60
	markupSW_postPreLgd_offset = 5
	markupSW_divider = ...

Every TS property has the stdWrap property, too.


Impact
======

Default settings do not change old behaviour.


.. index:: TypoScript, ext:indexed_search
