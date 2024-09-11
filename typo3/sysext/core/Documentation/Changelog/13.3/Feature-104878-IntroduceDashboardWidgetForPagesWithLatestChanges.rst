.. include:: /Includes.rst.txt

.. _feature-104878-1725993353:

===========================================================================
Feature: #104878 - Introduce dashboard widget for pages with latest changes
===========================================================================

See :issue:`104878`

Description
===========

To make it easier for TYPO3 users to view the latest changed pages in their
TYPO3 system, TYPO3 now offers a dashboard widget that lists the latest
changed pages.

Widget Options:
- `limit` The limit of pages, displayed in the widget, default is 10
- `historyLimit` The maximum number of history records to check, default 1000

Impact
======

TYPO3 users who have access to the :guilabel:`Dashboard` module and are
granted access to the new widgets can now add and use this widget.

.. index:: Backend, ext:dashboard