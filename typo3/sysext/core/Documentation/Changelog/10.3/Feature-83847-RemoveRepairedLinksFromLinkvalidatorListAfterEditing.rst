.. include:: /Includes.rst.txt

=============================================================================
Feature: #83847 - Remove repaired links from Linkvalidator list after editing
=============================================================================

See :issue:`83847`

Description
===========

In the list of broken links provided by Linkvalidator, it is possible to click
on the edit icon for a broken link in order to edit the record directly.

If the record was edited, the list of broken links may no longer be up to date.

There are now 2 possibilities, depending on how :php:`actionAfterEditRecord`
is configured:

recheck (default):
   The field is rechecked. (Warning: an RTE field may contain a number
   of links, rechecking may lead to delays.)


setNeedsRecheck:
   The entries in the list are marked as needing a recheck

Prior to this feature, fixed broken links were not removed from the list, which made fixing
several links at a time confusing and tedious because you either had to
remember which links were already fixed or switch back and forth between
the *Report* and the *Check Links* tab to recheck for broken links.


Impact
======

This feature improves the workflow of fixing broken links.

If the recheck option is selected, this may lead to some delays when
rechecking for broken links, especially if external links are involved.

.. index:: Backend, ext:linkvalidator
