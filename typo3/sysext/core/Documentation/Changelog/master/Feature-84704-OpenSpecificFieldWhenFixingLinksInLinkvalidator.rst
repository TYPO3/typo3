.. include:: ../../Includes.txt

========================================================================
Feature: #84704 - Open specific field when fixing links in Linkvalidator
========================================================================

See :issue:`84704`

Description
===========

When fixing links in Linkvalidator, you click on the edit icon
for the respective broken link which gets you to an edit form.

Before this patch, the edit form was opened for the entire record.

There you had too much information, and it was difficult to see,
what needed fixing.

With this patch only the respective table.field that contains
the broken link is opened in the edit form (e.g. tt_content.bodytext).


Impact
======

Only affects Linkvalidator. Editing broken links should be easier now.

.. index:: Backend, ext:linkvalidator