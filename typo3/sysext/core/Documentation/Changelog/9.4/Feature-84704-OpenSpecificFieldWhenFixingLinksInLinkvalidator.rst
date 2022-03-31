.. include:: /Includes.rst.txt

========================================================================
Feature: #84704 - Open specific field when fixing links in Linkvalidator
========================================================================

See :issue:`84704`

Description
===========

When fixing links in Linkvalidator, a click on the edit icon
for the respective broken link opens an edit form.

The whole form opening provided too much information, where just the problematic field would have been enough.
Now only the required field is open for editing, with an additional option to switch to the whole form if necessary.


Impact
======

Only affects Linkvalidator. Editing broken links should be easier now.

.. index:: Backend, ext:linkvalidator
