.. include:: /Includes.rst.txt

===================================================================
Feature: #56213 - Allow sorting file list by file meta data "title"
===================================================================

See :issue:`56213`

Description
===========

The possibility to sort files by their meta data title in the "File Links" content element has been introduced.
The title attribute is part of the base metadata table and therefor available in all TYPO3 installations.

If you need further FAL fields to sort by, extend the TCA of tt_content field :php:`filelink_sorting` and add
metadata fields as options to choose from.

For example add the following in :file:`TCA/Overrides/tt_content.php`::

   $GLOBALS['TCA']['tt_content']['columns']['filelink_sorting']['config']['items'][] = ['Sort by alternative Text', 'alternative'];

Or use Page TSConfig :typoscript:`TCEFORM.tt_content.filelink_sorting.addItems.alternative = sort by "Alternative" metadata field`.


Impact
======

The filelinks content element has a new option for sorting "by file metadata title" which can be chosen in
the drop down when creating the element.

.. index:: Backend, TCA, ext:frontend
